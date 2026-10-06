<?php

namespace App\Http\Middleware;

use App\Models\ActivityLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class RegistrarAccesoDenegado
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($response->getStatusCode() === 403) {
            $ruta = $request->method() . ' ' . $request->path();

            ActivityLog::log('acceso_denegado', "403 en {$ruta}", 'seguridad', [
                'ruta' => $ruta,
                'ip'   => $request->ip(),
            ]);

            Log::warning('Acceso denegado (403)', [
                'ruta'    => $ruta,
                'user_id' => $request->user()?->getAuthIdentifier(),
                'ip'      => $request->ip(),
            ]);
        }

        return $response;
    }
}
