<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\AsistenciaResource;
use App\Models\Asistencia;
use Illuminate\Http\Request;

class AsistenciaApiController extends Controller
{
    public function index(Request $request): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $asistencias = Asistencia::with('usuario')
            ->when($request->fecha, fn($q, $f) => $q->where('fecha', $f))
            ->when($request->estado, fn($q, $e) => $q->where('estado', $e))
            ->orderByDesc('fecha')
            ->paginate(min((int) $request->get('per_page', 15), 100));

        return AsistenciaResource::collection($asistencias);
    }

    public function show(Asistencia $asistencia): AsistenciaResource
    {
        $asistencia->load('usuario');
        return new AsistenciaResource($asistencia);
    }
}
