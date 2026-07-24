<?php

namespace App\Http\Controllers\Empleado;

use App\Http\Controllers\Controller;
use App\Models\Asistencia;
use App\Models\DocenteHorarioPivot;
use App\Models\Horario;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $user = Auth::user();

        $totalMes = Asistencia::where('usuario_id', $user->id)
            ->whereMonth('fecha', now()->month)
            ->selectRaw("
                SUM(CASE WHEN estado = 'Presente' THEN 1 ELSE 0 END) AS presentes,
                SUM(CASE WHEN estado = 'Tarde' THEN 1 ELSE 0 END) AS tardes,
                SUM(CASE WHEN estado = 'Inasistente' THEN 1 ELSE 0 END) AS inasistentes
            ")
            ->first();

        return view('empleado.dashboard', compact('user', 'totalMes'));
    }

    public function horario(): View
    {
        $user = Auth::user();

        $dias = Horario::DIAS;

        $asignaciones = DocenteHorarioPivot::with('horario', 'salon', 'coordinacion', 'cargaAcademica')
            ->where('user_id', $user->id)
            ->get();

        $agrupado = [];
        foreach ($asignaciones as $a) {
            if ($a->horario) {
                $agrupado[$a->horario->dia_semana][] = $a;
            }
        }

        foreach ($agrupado as $dia => &$lista) {
            usort($lista, fn($a, $b) => strcmp($a->horario->hora_inicio, $b->horario->hora_inicio));
        }

        return view('empleado.horario', compact('user', 'dias', 'agrupado'));
    }

    public function estadisticas(): View
    {
        $user = Auth::user();

        $ultimos30Dias = collect(range(29, 0))->map(fn($i) => now()->subDays($i)->toDateString());

        $asistenciasPorDia = Asistencia::where('usuario_id', $user->id)
            ->whereBetween('fecha', [$ultimos30Dias->first(), $ultimos30Dias->last()])
            ->selectRaw("
                fecha,
                SUM(CASE WHEN estado = 'Presente' THEN 1 ELSE 0 END) AS presentes,
                SUM(CASE WHEN estado = 'Tarde' THEN 1 ELSE 0 END) AS tardes,
                SUM(CASE WHEN estado = 'Inasistente' THEN 1 ELSE 0 END) AS inasistentes
            ")
            ->groupBy('fecha')
            ->get()
            ->keyBy('fecha');

        $chartLabels = $ultimos30Dias->map(fn($f) => Carbon::parse($f)->translatedFormat('d M'))->values();
        $chartPresentes = $ultimos30Dias->map(fn($f) => $asistenciasPorDia[$f]->presentes ?? 0)->values();
        $chartTardes = $ultimos30Dias->map(fn($f) => $asistenciasPorDia[$f]->tardes ?? 0)->values();
        $chartInasistentes = $ultimos30Dias->map(fn($f) => $asistenciasPorDia[$f]->inasistentes ?? 0)->values();

        $totalMes = Asistencia::where('usuario_id', $user->id)
            ->whereMonth('fecha', now()->month)
            ->selectRaw("
                SUM(CASE WHEN estado = 'Presente' THEN 1 ELSE 0 END) AS presentes,
                SUM(CASE WHEN estado = 'Tarde' THEN 1 ELSE 0 END) AS tardes,
                SUM(CASE WHEN estado = 'Inasistente' THEN 1 ELSE 0 END) AS inasistentes,
                SUM(CASE WHEN estado = 'Apoyo Sabado' THEN 1 ELSE 0 END) AS apoyos
            ")
            ->first();

        return view('empleado.estadisticas', compact(
            'user', 'chartLabels', 'chartPresentes', 'chartTardes', 'chartInasistentes', 'totalMes'
        ));
    }
}
