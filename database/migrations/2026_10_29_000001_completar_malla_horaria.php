<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Completa la malla horaria: todos los días deben tener todos los rangos de
 * hora para que el grid de "Horarios del Personal Docente" nunca muestre una
 * celda sin bloque.
 *
 * Incluye el bloque 12:30–13:05 (antes un hueco de receso) y replica en los
 * seis días cualquier rango personalizado que ya exista en la base.
 */
return new class extends Migration
{
    private const MALLA = [
        ['07:00:00', '07:45:00'],
        ['07:45:00', '08:30:00'],
        ['08:35:00', '09:20:00'],
        ['09:20:00', '10:05:00'],
        ['10:10:00', '10:55:00'],
        ['10:55:00', '11:40:00'],
        ['11:45:00', '12:30:00'],
        ['12:30:00', '13:05:00'],
        ['13:05:00', '13:50:00'],
        ['13:50:00', '14:35:00'],
        ['14:40:00', '15:25:00'],
        ['15:25:00', '16:10:00'],
        ['16:15:00', '17:00:00'],
        ['17:00:00', '17:45:00'],
    ];

    private const DIAS = [
        1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles',
        4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado',
    ];

    public function up(): void
    {
        if (!Schema::hasTable('horarios')) {
            return;
        }

        // Rangos objetivo: malla oficial + rangos personalizados existentes.
        $rangos = [];
        foreach (self::MALLA as [$inicio, $fin]) {
            $rangos[$inicio . '-' . $fin] = [$inicio, $fin];
        }

        foreach (DB::table('horarios')->select('hora_inicio', 'hora_fin')->distinct()->get() as $fila) {
            $inicio = $this->normalizar($fila->hora_inicio);
            $fin    = $this->normalizar($fila->hora_fin);

            if ($inicio === '' || $fin === '' || $inicio >= $fin) {
                continue;
            }

            $rangos[$inicio . '-' . $fin] = [$inicio, $fin];
        }
        ksort($rangos);

        // Mapa de lo que ya existe (incluye bloques con soft delete). Si hay
        // duplicados, se conserva el activo para no restaurar una fila que
        // duplicaría a la que ya está en uso.
        $existentes = [];
        foreach (DB::table('horarios')->get(['id', 'dia_semana', 'hora_inicio', 'hora_fin', 'deleted_at']) as $fila) {
            $llave = $fila->dia_semana . '|' . $this->normalizar($fila->hora_inicio) . '|' . $this->normalizar($fila->hora_fin);

            if (!isset($existentes[$llave]) || ($existentes[$llave]->deleted_at !== null && $fila->deleted_at === null)) {
                $existentes[$llave] = $fila;
            }
        }

        $ahora      = now();
        $nuevos     = [];
        $restaurar  = [];

        foreach (self::DIAS as $dia => $nombreDia) {
            foreach ($rangos as [$inicio, $fin]) {
                $llave = $dia . '|' . $inicio . '|' . $fin;

                if (isset($existentes[$llave])) {
                    if ($existentes[$llave]->deleted_at !== null) {
                        $restaurar[] = $existentes[$llave]->id;
                    }
                    continue;
                }

                $nuevos[] = [
                    'dia_semana'  => $dia,
                    'hora_inicio' => $inicio,
                    'hora_fin'    => $fin,
                    'nombre'      => $nombreDia . ' ' . substr($inicio, 0, 5) . '–' . substr($fin, 0, 5),
                    'created_at'  => $ahora,
                    'updated_at'  => $ahora,
                ];
            }
        }

        if (!empty($nuevos)) {
            foreach (array_chunk($nuevos, 100) as $lote) {
                DB::table('horarios')->insert($lote);
            }
        }

        if (!empty($restaurar)) {
            foreach (array_chunk($restaurar, 100) as $lote) {
                DB::table('horarios')->whereIn('id', $lote)->update([
                    'deleted_at' => null,
                    'updated_at' => $ahora,
                ]);
            }
        }
    }

    public function down(): void
    {
        // Solo se retira el bloque 12:30–13:05, y únicamente si nadie lo usa.
        if (!Schema::hasTable('horarios')) {
            return;
        }

        $ids = DB::table('horarios')
            ->where('hora_inicio', '12:30:00')
            ->where('hora_fin', '13:05:00')
            ->pluck('id');

        foreach ($ids as $id) {
            $asignado = Schema::hasTable('docente_horario')
                && DB::table('docente_horario')->where('horario_id', $id)->exists();

            $conAsistencia = Schema::hasTable('asistencias')
                && Schema::hasColumn('asistencias', 'horario_id')
                && DB::table('asistencias')->where('horario_id', $id)->exists();

            if (!$asignado && !$conAsistencia) {
                DB::table('horarios')->where('id', $id)->delete();
            }
        }
    }

    private function normalizar(?string $hora): string
    {
        $hora = trim((string) $hora);

        if ($hora !== '' && preg_match('/^\d{2}:\d{2}$/', $hora)) {
            return $hora . ':00';
        }

        return $hora;
    }
};
