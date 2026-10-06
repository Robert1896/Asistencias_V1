<?php

namespace App\Http\Controllers;

use App\Http\Requests\JustificarRequest;
use App\Models\ActivityLog;
use App\Models\Asistencia;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
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
            $filePath = $request->file('archivo')->store('justificaciones', 'local');
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

    public function archivo(Asistencia $asistencia)
    {
        $user = Auth::user();

        if ($asistencia->usuario_id !== $user->id && !Gate::allows('asistencias_ver')) {
            abort(403, 'No autorizado para ver este documento.');
        }

        $path = $asistencia->archivo_justificacion;
        abort_unless($path, 404);

        if (Storage::disk('local')->missing($path) && Storage::disk('public')->exists($path)) {
            Storage::disk('local')->put($path, Storage::disk('public')->get($path));
            Storage::disk('public')->delete($path);
        }

        abort_unless(Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->download($path);
    }
}
