<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SessionActivityController extends Controller
{
    public function ping(Request $request): JsonResponse
    {
        if (! $request->user()) {
            return response()->json(['message' => 'No autenticado'], 401);
        }

        $rolId = (int) $request->user()->rol_id;
        if (in_array($rolId, [2, 3, 4], true)) {
            $request->session()->put('last_activity', now()->timestamp);
        }

        return response()->json(['ok' => true]);
    }
}
