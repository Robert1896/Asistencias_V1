<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $login = $request->input('login', '');
        $ipKey = 'login-ip:' . $request->ip();

        // Requisito #17: bloquear a los 3 intentos fallidos por cuenta.
        if (RateLimiter::tooManyAttempts('login:' . Str::lower($login), 3)) {
            $seconds = RateLimiter::availableIn('login:' . Str::lower($login));

            ActivityLog::log('login_bloqueado', "Intentos excesivos para: {$login}", 'auth', ['login' => $login]);

            throw ValidationException::withMessages([
                'login' => ["Demasiados intentos. La cuenta quedó bloqueada temporalmente; intenta de nuevo en {$seconds} segundos."],
            ]);
        }

        // Límite por IP: evita barrido de muchos correos desde un mismo origen (CWE-307)
        if (RateLimiter::tooManyAttempts($ipKey, 30)) {
            $seconds = RateLimiter::availableIn($ipKey);

            ActivityLog::log('login_bloqueado_ip', "Intentos excesivos desde IP: {$request->ip()}", 'auth', ['ip' => $request->ip()]);

            throw ValidationException::withMessages([
                'login' => ["Demasiados intentos desde esta IP. Intenta de nuevo en {$seconds} segundos."],
            ]);
        }

        $credentials = $request->validate([
            'login'    => 'required|string',
            'password' => 'required|string|min:' . \App\Support\PasswordPolicy::LOGIN_MIN_LENGTH,
        ]);

        $isNumeric = ctype_digit($credentials['login']);

        $user = $isNumeric
            ? User::where('cedula', $credentials['login'])->where('estatus', 'activo')->first()
            : User::where('email', $credentials['login'])->where('estatus', 'activo')->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            RateLimiter::hit('login:' . Str::lower($login), 900);
            RateLimiter::hit($ipKey, 900);

            ActivityLog::log('login_fallido', "Credenciales inválidas para: {$login}", 'auth', ['login' => $login]);

            throw ValidationException::withMessages([
                'login' => ['Credenciales inválidas o usuario inactivo.'],
            ]);
        }

        RateLimiter::clear('login:' . Str::lower($login));

        // Requisito #15: segundo factor. Si está habilitado, no se inicia sesión
        // todavía; se envía un código al correo y se pide verificarlo.
        if ($user->two_factor_enabled) {
            $minutos = $user->generarCodigoDosFactores();

            $request->session()->put('login_2fa_id', $user->id);
            $request->session()->put('login_2fa_remember', $request->boolean('remember'));
            $request->session()->put('login_2fa_at', now()->timestamp);

            ActivityLog::log('login_2fa_enviado', "Código 2FA enviado a: {$user->email}", 'auth', [
                'user_id' => $user->id,
                'ip'      => $request->ip(),
            ]);

            return redirect()->route('login.twofactor')
                ->with('success', "Enviamos un código de verificación a tu correo. Es válido por {$minutos} minutos.");
        }

        Auth::login($user, $request->boolean('remember'));

        $request->session()->regenerate();
        $request->session()->put('sesion_iniciada_en', now()->timestamp);

        try {
            ActivityLog::log('login', "Inicio de sesión exitoso: " . ($user->cedula ?? $user->email), 'auth', [
                'email'     => $user->email,
                'user_id'   => $user->id,
                'ip'        => $request->ip(),
                'user_agent'=> $request->userAgent(),
            ]);
        } catch (\Throwable $e) {
        }

        return redirect()->intended(route('dashboard'));
    }

    public function showTwoFactorForm(Request $request)
    {
        if (!$request->session()->has('login_2fa_id')) {
            return redirect()->route('login');
        }

        return view('auth.two_factor');
    }

    public function verifyTwoFactor(Request $request)
    {
        $request->validate([
            'codigo' => 'required|string|digits:6',
        ]);

        $pendingId = $request->session()->get('login_2fa_id');

        if (!$pendingId) {
            return redirect()->route('login')->withErrors(['login' => 'La verificación expiró. Inicia sesión nuevamente.']);
        }

        // Requisito #17 aplicado también al código: 3 intentos.
        $throttleKey = 'login-2fa:' . $pendingId;
        if (RateLimiter::tooManyAttempts($throttleKey, 3)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            $request->session()->forget(['login_2fa_id', 'login_2fa_remember', 'login_2fa_at']);

            return redirect()->route('login')->withErrors([
                'login' => "Demasiados intentos de verificación. Intenta de nuevo en {$seconds} segundos.",
            ]);
        }

        $user = User::find($pendingId);

        if (!$user || !$user->codigoDosFactoresValido($request->input('codigo'))) {
            RateLimiter::hit($throttleKey, 900);

            ActivityLog::log('login_2fa_fallido', "Código 2FA inválido para: {$pendingId}", 'auth', ['user_id' => $pendingId]);

            return back()->withErrors(['codigo' => 'El código no es válido o ha expirado.']);
        }

        RateLimiter::clear($throttleKey);
        $user->limpiarCodigoDosFactores();

        $remember = (bool) $request->session()->pull('login_2fa_remember', false);
        $request->session()->forget(['login_2fa_id', 'login_2fa_at']);

        Auth::login($user, $remember);
        $request->session()->regenerate();
        $request->session()->put('sesion_iniciada_en', now()->timestamp);

        ActivityLog::log('login', "Inicio de sesión exitoso (2FA): " . ($user->cedula ?? $user->email), 'auth', [
            'user_id' => $user->id,
            'ip'      => $request->ip(),
            'metodo'  => 'two_factor',
        ]);

        return redirect()->intended(route('dashboard'));
    }

    public function resendTwoFactor(Request $request)
    {
        $pendingId = $request->session()->get('login_2fa_id');

        if (!$pendingId) {
            return redirect()->route('login');
        }

        $throttleKey = 'login-2fa-resend:' . $pendingId;
        if (RateLimiter::tooManyAttempts($throttleKey, 3)) {
            return back()->withErrors(['codigo' => 'Espera un momento antes de solicitar otro código.']);
        }
        RateLimiter::hit($throttleKey, 300);

        $user = User::find($pendingId);
        if ($user) {
            $minutos = $user->generarCodigoDosFactores();
            ActivityLog::log('login_2fa_reenviado', "Código 2FA reenviado a: {$user->email}", 'auth', ['user_id' => $user->id]);

            return back()->with('success', "Enviamos un nuevo código. Es válido por {$minutos} minutos.");
        }

        return redirect()->route('login');
    }

    public function cancelTwoFactor(Request $request)
    {
        $request->session()->forget(['login_2fa_id', 'login_2fa_remember', 'login_2fa_at']);

        return redirect()->route('login');
    }

    public function logout(Request $request)
    {
        $user = Auth::user();
        $userId = $user?->id;
        $descripcion = $user ? "Cierre de sesión: {$user->email}" : "Cierre de sesión";

        try {
            ActivityLog::log('logout', $descripcion, 'auth', [
                'email'   => $user?->email,
                'user_id' => $userId,
            ], $userId);
        } catch (\Throwable $e) {
            // Ignorar errores al registrar en bitácora para no bloquear el logout
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }

    public function showForgotForm()
    {
        return view('auth.forgot');
    }

    public function sendResetLink(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        Password::sendResetLink($request->only('email'));

        // Respuesta idéntica exista o no el correo: evita enumeración de usuarios (CWE-204)
        return back()->with('success', 'Si el correo está registrado en el sistema, recibirás un enlace para restablecer tu contraseña.');
    }

    public function showResetForm(string $token)
    {
        return view('auth.reset', ['token' => $token]);
    }

    public function reset(Request $request)
    {
        $request->validate([
            'token'    => 'required',
            'email'    => 'required|email',
            'password' => 'required|' . \App\Support\PasswordPolicy::ruleString() . '|confirmed',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->password = $password;
                $user->save();
                // Fase C: mata recordatorios y demás sesiones del usuario (CWE-613)
                $user->invalidarOtrasSesiones();
            }
        );

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('login')->with('success', __($status))
            : back()->withErrors(['email' => __($status)]);
    }
}
