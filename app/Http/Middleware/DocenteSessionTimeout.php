<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class DocenteSessionTimeout
{
    private const TIMEOUT_MINUTES = 3;

    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check() && Auth::user()->rol_id === 3) {
            $lastActivity = session('last_activity');

            if ($lastActivity && (now()->timestamp - $lastActivity) > (self::TIMEOUT_MINUTES * 60)) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')
                    ->with('error', 'Tu sesión ha expirado por inactividad. Por favor, inicia sesión nuevamente.');
            }

            session(['last_activity' => now()->timestamp]);
        }

        return $next($request);
    }
}
