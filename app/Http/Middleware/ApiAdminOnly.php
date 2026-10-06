<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiAdminOnly
{
    private const ROLES_CON_ACCESO = [1, 2];

    public function handle(Request $request, Closure $next, string ...$permisos): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'No autorizado.'], 403);
        }

        if ($user->estatus !== 'activo') {
            return response()->json(['message' => 'No autorizado.'], 403);
        }

        if ($user->rol_id === 1) {
            return $next($request);
        }

        if (!in_array($user->rol_id, self::ROLES_CON_ACCESO, true)) {
            return response()->json(['message' => 'No autorizado.'], 403);
        }

        if (empty($permisos)) {
            return response()->json(['message' => 'No autorizado.'], 403);
        }

        foreach ($permisos as $permiso) {
            if ($user->tienePermiso($permiso)) {
                return $next($request);
            }
        }

        return response()->json(['message' => 'No autorizado.'], 403);
    }
}