<?php

namespace App\Http\Controllers;

use App\Http\Requests\JustificarRequest;
use App\Models\ActivityLog;
use App\Models\Asistencia;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class JustificacionController extends Controller
{
    public function index(): View
    {
        $user = Auth::user();

        $asistencias = Asistencia::where('usuario_id', $user->id)
            ->whereIn('estado', ['Inasistente', 'Tarde'])
            ->where('justificado', 'No')
            ->orderByDesc('fecha')
            ->get();

        $historial = Asistencia::where('usuario_id', $user->id)
            ->where('justificado', 'Si')
            ->orderByDesc('fecha')
            ->limit(10)
            ->get();

        return view('empleado.justificaciones', compact('asistencias', 'historial'));
    }

    public function solicitar(JustificarRequest $request, Asistencia $asistencia): RedirectResponse
    {
        $user = Auth::user();

        if ($asistencia->usuario_id !== $user->id) {
            return back()->with('error', 'No tienes permiso para justificar esta asistencia.');
        }

        $data = $request->validated();

        $filePath = null;
        if ($request->hasFile('archivo')) {
            $filePath = $request->file('archivo')->store('justificaciones', 'public');
        }

        $asistencia->update([
            'justificado'            => 'Si',
            'texto_justificacion'    => strip_tags($data['texto_justificacion']),
            'archivo_justificacion'  => $filePath,
        ]);

        ActivityLog::log('justificacion_solicitar', "Justificación enviada para asistencia ID: {$asistencia->id}", 'asistencias', ['asistencia_id' => $asistencia->id]);

        Log::info('Justificación enviada', [
            'user_id'       => $user->id,
            'asistencia_id' => $asistencia->id,
            'fecha'         => $asistencia->fecha,
        ]);

        return redirect()->route('justificaciones')->with('success', 'Justificación enviada correctamente.');
    }
}
