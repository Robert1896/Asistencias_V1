<?php

namespace App\Http\Controllers;

use App\Models\Asistencia;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        if ($user->rol && $user->rol->nombre === 'Docente') {
            return redirect()->route('empleado.dashboard');
        }

        if ($user->rol && ($user->rol->nombre === 'Asistente' || $user->rol->nombre === 'Técnico' || (int)$user->rol_id === 5)) {
            return $this->asistente($user);
        }

        // Fase B (M12): estadísticas globales solo con permiso asistencias_ver
        // (Gate::before da bypass al rol 1 y deniega cuentas inactivas)
        if (!Gate::allows('asistencias_ver')) {
            return Gate::allows('estacion_operar')
                ? redirect()->route('estacion.index')
                : redirect()->route('profile.edit');
        }

        $stats = Cache::remember('dashboard_stats', 300, function () {
            $totalUsuarios  = User::where('estatus', 'activo')->count();
            $asistenciasHoy = Asistencia::where('fecha', today())->count();
            $tardesHoy      = Asistencia::where('fecha', today())
                ->where('estado', 'Tarde')->count();
            $inasistentesHoy = Asistencia::where('fecha', today())
                ->where('estado', 'Inasistente')->count();

            return compact('totalUsuarios', 'asistenciasHoy', 'tardesHoy', 'inasistentesHoy');
        });

        $ultimos7Dias = collect(range(6, 0))->map(fn($i) => now()->subDays($i)->toDateString());

        $asistenciasPorDia = Cache::remember('dashboard_chart_' . today()->toDateString(), 300, function () use ($ultimos7Dias) {
            return Asistencia::whereBetween('fecha', [$ultimos7Dias->first(), $ultimos7Dias->last()])
                ->selectRaw("
                    fecha,
                    SUM(CASE WHEN estado = 'Presente' THEN 1 ELSE 0 END) AS presentes,
                    SUM(CASE WHEN estado = 'Tarde' THEN 1 ELSE 0 END) AS tardes,
                    SUM(CASE WHEN estado = 'Inasistente' THEN 1 ELSE 0 END) AS inasistentes,
                    SUM(CASE WHEN estado = 'Apoyo Sabado' THEN 1 ELSE 0 END) AS apoyos
                ")
                ->groupBy('fecha')
                ->get()
                ->keyBy('fecha');
        });

        $chartLabels = $ultimos7Dias->map(fn($f) => Carbon::parse($f)->translatedFormat('d M'))->values();
        $chartPresentes = $ultimos7Dias->map(fn($f) => $asistenciasPorDia[$f]->presentes ?? 0)->values();
        $chartTardes = $ultimos7Dias->map(fn($f) => $asistenciasPorDia[$f]->tardes ?? 0)->values();
        $chartInasistentes = $ultimos7Dias->map(fn($f) => $asistenciasPorDia[$f]->inasistentes ?? 0)->values();

        $totalMes = Cache::remember('dashboard_month_' . now()->month, 300, function () {
            return Asistencia::whereMonth('fecha', now()->month)
                ->selectRaw("
                    SUM(CASE WHEN estado = 'Presente' THEN 1 ELSE 0 END) AS presentes,
                    SUM(CASE WHEN estado = 'Tarde' THEN 1 ELSE 0 END) AS tardes,
                    SUM(CASE WHEN estado = 'Inasistente' THEN 1 ELSE 0 END) AS inasistentes,
                    SUM(CASE WHEN estado = 'Apoyo Sabado' THEN 1 ELSE 0 END) AS apoyos
                ")->first();
        });

        return view('dashboard.index', array_merge($stats, compact(
            'user', 'chartLabels', 'chartPresentes', 'chartTardes', 'chartInasistentes', 'totalMes'
        )));
    }

    private function asistente(User $user)
    {
        $hoy = today();

        $presentesHoy = Asistencia::where('fecha', $hoy)->where('estado', 'Presente')->count();
        $tardesHoy = Asistencia::where('fecha', $hoy)->where('estado', 'Tarde')->count();
        $inasistentesHoy = Asistencia::where('fecha', $hoy)->where('estado', 'Inasistente')->count();
        $asistenciasHoy = Asistencia::where('fecha', $hoy)->count();

        $ultimosRegistros = Asistencia::where('fecha', $hoy)
            ->with('usuario')
            ->latest('hora_entrada')
            ->limit(5)
            ->get();

        $ultimos7Dias = collect(range(6, 0))->map(fn($i) => now()->subDays($i)->toDateString());
        $asistenciasPorDia = Cache::remember('dashboard_chart_asistente_' . today()->toDateString(), 300, function () use ($ultimos7Dias) {
            return Asistencia::whereBetween('fecha', [$ultimos7Dias->first(), $ultimos7Dias->last()])
                ->selectRaw("
                    fecha,
                    SUM(CASE WHEN estado = 'Presente' THEN 1 ELSE 0 END) AS presentes,
                    SUM(CASE WHEN estado = 'Tarde' THEN 1 ELSE 0 END) AS tardes,
                    SUM(CASE WHEN estado = 'Inasistente' THEN 1 ELSE 0 END) AS inasistentes,
                    SUM(CASE WHEN estado = 'Apoyo Sabado' THEN 1 ELSE 0 END) AS apoyos
                ")
                ->groupBy('fecha')
                ->get()
                ->keyBy('fecha');
        });

        $chartLabels = $ultimos7Dias->map(fn($f) => Carbon::parse($f)->translatedFormat('d M'))->values();
        $chartPresentes = $ultimos7Dias->map(fn($f) => $asistenciasPorDia[$f]->presentes ?? 0)->values();
        $chartTardes = $ultimos7Dias->map(fn($f) => $asistenciasPorDia[$f]->tardes ?? 0)->values();
        $chartInasistentes = $ultimos7Dias->map(fn($f) => $asistenciasPorDia[$f]->inasistentes ?? 0)->values();

        $totalMes = Cache::remember('dashboard_month_asistente_' . now()->month, 300, function () {
            return Asistencia::whereMonth('fecha', now()->month)
                ->selectRaw("
                    SUM(CASE WHEN estado = 'Presente' THEN 1 ELSE 0 END) AS presentes,
                    SUM(CASE WHEN estado = 'Tarde' THEN 1 ELSE 0 END) AS tardes,
                    SUM(CASE WHEN estado = 'Inasistente' THEN 1 ELSE 0 END) AS inasistentes,
                    SUM(CASE WHEN estado = 'Apoyo Sabado' THEN 1 ELSE 0 END) AS apoyos
                ")->first();
        });

        return view('dashboard.asistente', compact(
            'user', 'asistenciasHoy', 'tardesHoy', 'inasistentesHoy', 'presentesHoy',
            'ultimosRegistros', 'chartLabels', 'chartPresentes', 'chartTardes', 'chartInasistentes', 'totalMes'
        ));
    }
}
