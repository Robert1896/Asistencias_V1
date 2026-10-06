<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class SesionAbsoluta
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->hasSession() && Auth::check()) {
            $maximoMinutos = (int) config('session.absolute_timeout', 480);
            $inicio = $request->session()->get('sesion_iniciada_en');

            if ($inicio === null) {
                $request->session()->put('sesion_iniciada_en', now()->timestamp);
            } elseif ((now()->timestamp - (int) $inicio) > ($maximoMinutos * 60)) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')
                    ->with('error', 'Tu sesión ha expirado por tiempo máximo de uso. Inicia sesión nuevamente.');
            }
        }

        return $next($request);
    }
}
