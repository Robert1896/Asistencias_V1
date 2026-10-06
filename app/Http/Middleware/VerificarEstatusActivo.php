<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Fase B (CWE-613): una cuenta con estatus != 'activo' no puede operar.
 * Cierra la sesión web completa (cookie + token + CSRF) y redirige al login.
 * La autorización Gate::before también deniega cuentas inactivas por si
 * este middleware no cubre alguna vía (p. ej. rutas sin sesión web).
 */
class VerificarEstatusActivo
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user !== null && $user->estatus !== 'activo') {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('login')
                ->with('error', 'Tu cuenta ha sido desactivada. Contacta al administrador.');
        }

        return $next($request);
    }
}
