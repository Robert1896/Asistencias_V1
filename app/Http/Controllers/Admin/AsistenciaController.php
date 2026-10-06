<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\JustificarAdminRequest;
use App\Models\ActivityLog;
use App\Models\Asistencia;
use App\Models\AuditoriaAsistencia;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class AsistenciaController extends Controller
{
    public function index(Request $request): View
    {
        $query = Asistencia::with('usuario.rol');

        $fechaInicio = $request->input('fecha_inicio', now()->startOfMonth()->toDateString());
        $fechaFin = $request->input('fecha_fin', now()->toDateString());
        $estado = $request->input('estado');

        $query->where('fecha', '>=', $fechaInicio);
        $query->where('fecha', '<=', $fechaFin);

        if ($estado) {
            $query->where('estado', $estado);
        }

        $asistencias = $query->orderByDesc('fecha')->orderByDesc('id')->paginate(15);

        $statsQuery = Asistencia::where('fecha', '>=', $fechaInicio)->where('fecha', '<=', $fechaFin);
        $totalAsistencias = $statsQuery->count();
        $presentes = (clone $statsQuery)->where('estado', 'Presente')->count();
        $tardes = (clone $statsQuery)->where('estado', 'Tarde')->count();
        $inasistentes = (clone $statsQuery)->where('estado', 'Inasistente')->count();

        $diasRango = Carbon::parse($fechaInicio)->diffInDays(Carbon::parse($fechaFin)) + 1;
        $chartCollection = (clone $statsQuery)
            ->selectRaw('fecha, estado, COUNT(*) as total')
            ->groupBy('fecha', 'estado')
            ->get()
            ->groupBy('fecha');

        $chartLabels = collect();
        $chartPresentes = collect();
        $chartTardes = collect();
        $chartInasistentes = collect();

        for ($i = 0; $i < min($diasRango, 31); $i++) {
            $fecha = Carbon::parse($fechaInicio)->addDays($i)->toDateString();
            $chartLabels->push(Carbon::parse($fecha)->translatedFormat('d M'));
            $diaData = $chartCollection->get($fecha, collect());
            $chartPresentes->push($diaData->where('estado', 'Presente')->sum('total'));
            $chartTardes->push($diaData->where('estado', 'Tarde')->sum('total'));
            $chartInasistentes->push($diaData->where('estado', 'Inasistente')->sum('total'));
        }

        $esAsistente = auth()->user()->rol && auth()->user()->rol->nombre === 'Asistente';

        return view('admin.asistencias', compact(
            'asistencias', 'totalAsistencias', 'presentes', 'tardes', 'inasistentes',
            'chartLabels', 'chartPresentes', 'chartTardes', 'chartInasistentes', 'esAsistente'
        ));
    }

    public function justificar(JustificarAdminRequest $request, Asistencia $asistencia): RedirectResponse
    {
        $data = $request->validated();

        $antes = [
            'justificado'         => $asistencia->justificado,
            'texto_justificacion' => $asistencia->texto_justificacion,
        ];

        if ($data['justificado'] === 'Si') {
            $filePath = null;
            if ($request->hasFile('archivo')) {
                $filePath = $request->file('archivo')->store('justificaciones', 'local');
            }

            $asistencia->update([
                'justificado'           => 'Si',
                'texto_justificacion'   => strip_tags($data['texto_justificacion'] ?? null),
                'archivo_justificacion' => $filePath,
            ]);
        } else {
            if ($asistencia->archivo_justificacion) {
                Storage::disk('local')->delete($asistencia->archivo_justificacion);
            }

            $asistencia->update([
                'justificado'           => 'No',
                'texto_justificacion'   => null,
                'archivo_justificacion' => null,
            ]);
        }

        $despues = [
            'justificado'         => $asistencia->justificado,
            'texto_justificacion' => $asistencia->texto_justificacion,
        ];

        AuditoriaAsistencia::registrar(
            $asistencia->id,
            auth()->id(),
            $data['justificado'] === 'Si' ? 'justificar' : 'desjustificar',
            $antes,
            $despues,
            $data['texto_justificacion'] ?? null
        );

        $mensaje = $data['justificado'] === 'Si'
            ? 'Asistencia justificada correctamente.'
            : 'Justificación removida.';

        $accion = $data['justificado'] === 'Si' ? 'asistencia_justificar' : 'asistencia_desjustificar';
        $desc = $data['justificado'] === 'Si'
            ? "Justificó asistencia de {$asistencia->usuario->nombre_completo} del {$asistencia->fecha}"
            : "Removió justificación de {$asistencia->usuario->nombre_completo} del {$asistencia->fecha}";
        ActivityLog::log($accion, $desc, 'asistencias', [
            'usuario_id' => $asistencia->usuario_id,
            'fecha' => $asistencia->fecha,
        ]);

        return redirect()->route('admin.asistencias')->with('success', $mensaje);
    }
}
