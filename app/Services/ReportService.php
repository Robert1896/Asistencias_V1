<?php

namespace App\Services;

use App\Models\Asistencia;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ReportService
{
    /**
     * Calcula el reporte de productividad para todos los usuarios en un período dado.
     *
     * Para cada usuario activo calcula días asistidos, horas reales trabajadas,
     * horas teóricas (según horario administrativo o carga académica docente)
     * y el porcentaje de productividad. También genera un resumen diario de
    * asistencias con conteo de presentes, tardes, inasistentes y apoyos de sábado.
     *
     * @param string $fechaInicio Fecha de inicio del período (YYYY-MM-DD).
     * @param string $fechaFin    Fecha de fin del período (YYYY-MM-DD).
     * @param string $tipo        Tipo de reporte: 'general', 'docentes' o 'administrativos'.
     * @return array{reporte: Collection, resumenDiario: Collection}
     */
    public function calcularProductividad(string $fechaInicio, string $fechaFin, string $tipo): array
    {
        $query = User::with([
                'rol',
                'asistencias' => fn($q) => $q->whereBetween('fecha', [$fechaInicio, $fechaFin]),
                'horarioAdministrativo',
                'cargaAcademica',
            ])
            ->where('estatus', 'activo');

        if ($tipo === 'docentes') {
            $query->where('rol_id', 3);
        } elseif ($tipo === 'administrativos') {
            $query->where('rol_id', 2);
        }

        $usuarios = $query->orderBy('nombre_completo')->get();

        $reporte = $usuarios->map(function ($user) use ($fechaInicio, $fechaFin) {
            return $this->calcularProductoInterno($user, $fechaInicio, $fechaFin);
        });

        $resumenDiario = Asistencia::whereBetween('fecha', [$fechaInicio, $fechaFin])
            ->selectRaw("
                fecha,
                COUNT(*) AS total_registros,
                SUM(CASE WHEN estado = 'Presente' THEN 1 ELSE 0 END) AS presentes,
                SUM(CASE WHEN estado = 'Tarde' THEN 1 ELSE 0 END) AS tardes,
                SUM(CASE WHEN estado = 'Inasistente' THEN 1 ELSE 0 END) AS inasistentes,
                SUM(CASE WHEN estado = 'Apoyo Sabado' THEN 1 ELSE 0 END) AS apoyos_sabado
            ")
            ->groupBy('fecha')
            ->orderByDesc('fecha')
            ->limit(30)
            ->get();

        return [
            'reporte'      => $reporte,
            'resumenDiario' => $resumenDiario,
        ];
    }

    /**
     * Calcula la productividad individual de un empleado específico en un período.
     *
     * Retorna un objeto con las estadísticas de asistencia del usuario:
     * días asistidos, horas reales, horas teóricas, porcentaje de productividad,
     * y conteo por estado (presente, tarde, inasistente, apoyo sábado, justificados).
     *
     * @param int    $userId      ID del usuario/empleado.
     * @param string $fechaInicio Fecha de inicio del período (YYYY-MM-DD).
     * @param string $fechaFin    Fecha de fin del período (YYYY-MM-DD).
     * @return object Objeto con: id, nombre, rol_nombre, dias_asistidos, horas_reales,
     *               horas_teoricas, productividad, presentes, tardes, inasistentes,
     *               apoyos_sabado, justificados.
     */
    public function calcularProductoEmpleado(int $userId, string $fechaInicio, string $fechaFin): object
    {
        $user = User::with([
                'rol',
                'asistencias' => fn($q) => $q->whereBetween('fecha', [$fechaInicio, $fechaFin]),
                'horarioAdministrativo',
                'cargaAcademica',
            ])->findOrFail($userId);

        $asistencias = $user->asistencias;

        $estadisticas = $this->calcularProductoInterno($user, $fechaInicio, $fechaFin);

        $estadisticas->presentes      = $asistencias->where('estado', 'Presente')->count();
        $estadisticas->tardes         = $asistencias->where('estado', 'Tarde')->count();
        $estadisticas->inasistentes   = $asistencias->where('estado', 'Inasistente')->count();
        $estadisticas->apoyos_sabado  = $asistencias->where('estado', 'Apoyo Sabado')->count();
        $estadisticas->justificados   = $asistencias->where('justificado', 'Si')->count();

        return $estadisticas;
    }

    /**
     * Calcula las métricas internas de productividad para un usuario en un período.
     *
     * Determina las horas teóricas según el rol:
     * - Docentes: horas_semanales de cargaAcademica * (días del período / 7)
     * - Administrativos: horas diarias del horario * (días del período / 5)
     *
     * La productividad se calcula como (horas_reales / horas_teoricas) * 100.
     *
     * @param User   $user        El usuario a evaluar.
     * @param string $fechaInicio Fecha de inicio del período (YYYY-MM-DD).
     * @param string $fechaFin    Fecha de fin del período (YYYY-MM-DD).
     * @return object Objeto con: id, nombre, rol_nombre, dias_asistidos, horas_reales,
     *               horas_teoricas, productividad.
     */
    private function calcularProductoInterno(User $user, string $fechaInicio, string $fechaFin): object
    {
        $asistencias = $user->asistencias;

        $diasAsistidos = $asistencias->count();
        $horasReales   = $asistencias->sum(function ($a) {
            if ($a->hora_entrada && $a->hora_salida) {
                return Carbon::parse($a->hora_entrada)->diffInHours(Carbon::parse($a->hora_salida));
            }
            return 0;
        });

        $diasPeriodo = Carbon::parse($fechaInicio)->diffInDays(Carbon::parse($fechaFin)) + 1;

        if ($user->rol_id === 3) {
            $horasTeoricasSemana = $user->cargaAcademica->sum('horas_semanales');
            $horasTeoricas = round($horasTeoricasSemana * ($diasPeriodo / 7), 1);
        } elseif ($user->rol_id === 2 && $user->horarioAdministrativo) {
            $h = $user->horarioAdministrativo;
            $horasDiarias = Carbon::parse($h->hora_entrada)->diffInHours(Carbon::parse($h->hora_salida));
            $horasTeoricas = round($horasDiarias * ($diasPeriodo / 5), 1);
        } else {
            $horasTeoricas = 0;
        }

        $productividad = $horasTeoricas > 0
            ? round(($horasReales / $horasTeoricas) * 100, 1)
            : 0;

        return (object) [
            'id'              => $user->id,
            'nombre'          => $user->nombre_completo,
            'rol_nombre'      => $user->rol->nombre,
            'dias_asistidos'  => $diasAsistidos,
            'horas_reales'    => $horasReales,
            'horas_teoricas'  => $horasTeoricas,
            'productividad'   => $productividad,
        ];
    }
}
