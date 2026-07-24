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

        if (RateLimiter::tooManyAttempts('login:' . Str::lower($login), 5)) {
            $seconds = RateLimiter::availableIn('login:' . Str::lower($login));

            ActivityLog::log('login_bloqueado', "Intentos excesivos para: {$login}", 'auth', ['login' => $login]);

            throw ValidationException::withMessages([
                'login' => ["Demasiados intentos. Intenta de nuevo en {$seconds} segundos."],
            ]);
        }

        $credentials = $request->validate([
            'login'    => 'required|string',
            'password' => 'required|string|min:6',
        ]);

        $isNumeric = ctype_digit($credentials['login']);

        $user = $isNumeric
            ? User::where('cedula', $credentials['login'])->where('estatus', 'activo')->first()
            : User::where('email', $credentials['login'])->where('estatus', 'activo')->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            RateLimiter::hit('login:' . Str::lower($login), 900);

            ActivityLog::log('login_fallido', "Credenciales inválidas para: {$login}", 'auth', ['login' => $login]);

            throw ValidationException::withMessages([
                'login' => ['Credenciales inválidas o usuario inactivo.'],
            ]);
        }

        RateLimiter::clear('login:' . Str::lower($login));

        Auth::login($user, $request->boolean('remember'));

        $request->session()->regenerate();

        ActivityLog::log('login', "Inicio de sesión exitoso: " . ($user->cedula ?? $user->email), 'auth', [
            'login'   => $login,
            'user_id' => $user->id,
            'ip'      => $request->ip(),
        ]);

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request)
    {
        $user = Auth::user();
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($user) {
            ActivityLog::log('logout', "Cierre de sesión: {$user->email}", 'auth', [
                'email'   => $user->email,
                'user_id' => $user->id,
            ]);
        }

        return redirect('/login');
    }

    public function showForgotForm()
    {
        return view('auth.forgot');
    }

    public function sendResetLink(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $status = Password::sendResetLink($request->only('email'));

        return $status === Password::RESET_LINK_SENT
            ? back()->with('success', __($status))
            : back()->withErrors(['email' => __($status)]);
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
            'password' => 'required|string|min:8|confirmed|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->password = $password;
                $user->save();
            }
        );

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('login')->with('success', __($status))
            : back()->withErrors(['email' => __($status)]);
    }
}
