<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Horario extends Model
{
    use HasFactory, SoftDeletes;
    public const DIAS = [
        1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles',
        4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado',
    ];

    /**
     * Malla horaria oficial. Todos los días deben tener estas filas para que
     * el grid de asignación nunca deje una celda sin bloque (el hueco de
     * 12:30–13:05 es un bloque asignable más, no un receso).
     */
    public const MALLA_BASE = [
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

    protected $fillable = [
        'dia_semana', 'hora_inicio', 'hora_fin', 'nombre',
    ];

    protected function casts(): array
    {
        return [
            'hora_inicio' => 'string',
            'hora_fin'    => 'string',
        ];
    }

    public function docentes(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'docente_horario')
            ->withPivot(['tipo_actividad', 'coordinacion_id', 'salon_id'])
            ->withTimestamps();
    }

    /**
     * Todos los rangos horarios únicos ordenados.
     *
     * Se agrupa en PHP sobre la hora normalizada porque SQLite guarda
     * 'HH:MM' y MySQL 'HH:MM:SS' para la misma columna.
     */
    public static function todosLosBloques(): array
    {
        $bloques = [];

        foreach (self::query()->get(['id', 'hora_inicio', 'hora_fin']) as $b) {
            $inicio = self::normalizarHora($b->hora_inicio);
            $fin    = self::normalizarHora($b->hora_fin);

            if ($inicio === '' || $fin === '') {
                continue;
            }

            $llave = $inicio . '-' . $fin;

            if (!isset($bloques[$llave])) {
                $bloques[$llave] = [
                    'id'          => $b->id,
                    'hora_inicio' => $inicio,
                    'hora_fin'    => $fin,
                ];
            }
        }

        ksort($bloques);

        return array_values($bloques);
    }

    public function getDuracionEnHorasAttribute(): float
    {
        $inicio = strtotime($this->hora_inicio);
        $fin    = strtotime($this->hora_fin);
        return ($fin - $inicio) / 3600;
    }

    public static function agrupadosPorDia(): array
    {
        $dias = self::DIAS;
        $bloques = self::orderBy('dia_semana')->orderBy('hora_inicio')->get();
        $agrupados = [];
        foreach ($bloques as $b) {
            $agrupados[$b->dia_semana][] = $b;
        }
        return ['dias' => $dias, 'bloques' => $agrupados];
    }

    /**
     * Normaliza una hora a HH:MM:SS para comparar sin depender del formato
     * con que la devuelva el motor de base de datos.
     */
    public static function normalizarHora(?string $hora): string
    {
        $hora = trim((string) $hora);

        if ($hora === '') {
            return '';
        }

        if (preg_match('/^\d{2}:\d{2}$/', $hora)) {
            return $hora . ':00';
        }

        return $hora;
    }

    /**
     * Rangos que deben existir en los seis días: la malla oficial más
     * cualquier rango personalizado que ya esté en la base (incluye los
     * eliminados, para poder restaurarlos en lugar de duplicarlos).
     *
     * @return array<int, array{0:string,1:string}>
     */
    public static function rangosObjetivo(): array
    {
        $rangos = [];

        foreach (self::MALLA_BASE as [$inicio, $fin]) {
            $rangos[$inicio . '-' . $fin] = [$inicio, $fin];
        }

        $existentes = self::withTrashed()
            ->select('hora_inicio', 'hora_fin')
            ->distinct()
            ->get();

        foreach ($existentes as $r) {
            $inicio = self::normalizarHora($r->hora_inicio);
            $fin    = self::normalizarHora($r->hora_fin);

            if ($inicio === '' || $fin === '' || $inicio >= $fin) {
                continue;
            }

            $rangos[$inicio . '-' . $fin] = [$inicio, $fin];
        }

        ksort($rangos);

        return array_values($rangos);
    }

    /**
     * Busca el bloque de un día por rango, comparando horas normalizadas.
     */
    public static function buscarBloque(int $dia, string $inicio, string $fin, bool $incluirEliminados = false): ?self
    {
        $inicio = self::normalizarHora($inicio);
        $fin    = self::normalizarHora($fin);

        $query = $incluirEliminados ? self::withTrashed() : self::query();

        return $query->where('dia_semana', $dia)
            ->get()
            ->first(fn ($b) => self::normalizarHora($b->hora_inicio) === $inicio
                && self::normalizarHora($b->hora_fin) === $fin);
    }

    /**
     * Crea (o restaura) el bloque de un día concreto. Devuelve true si hubo
     * que crearlo o restaurarlo.
     */
    public static function asegurarBloque(int $dia, string $inicio, string $fin): bool
    {
        $inicio = self::normalizarHora($inicio);
        $fin    = self::normalizarHora($fin);

        if (!isset(self::DIAS[$dia]) || $inicio === '' || $fin === '') {
            return false;
        }

        // La hora puede venir como 'HH:MM' (SQLite) o 'HH:MM:SS' (MySQL), así
        // que se compara sobre el valor normalizado y no en SQL.
        $bloque = self::buscarBloque($dia, $inicio, $fin, true);

        if ($bloque) {
            if ($bloque->trashed()) {
                $bloque->restore();
                return true;
            }

            return false;
        }

        self::create([
            'dia_semana'  => $dia,
            'hora_inicio' => $inicio,
            'hora_fin'    => $fin,
            'nombre'      => self::DIAS[$dia] . ' ' . substr($inicio, 0, 5) . '–' . substr($fin, 0, 5),
        ]);

        return true;
    }

    /**
     * Asegura un rango en los seis días (usado por la importación, que puede
     * introducir horas personalizadas).
     */
    public static function asegurarRango(string $inicio, string $fin): void
    {
        foreach (array_keys(self::DIAS) as $dia) {
            self::asegurarBloque($dia, $inicio, $fin);
        }
    }

    /**
     * Garantiza que todos los días tengan todos los rangos objetivo.
     * Idempotente: solo escribe lo que falta. Devuelve cuántos bloques
     * se crearon o restauraron.
     */
    public static function sincronizarMalla(): int
    {
        $indice = [];
        foreach (self::withTrashed()->get() as $bloque) {
            $llave = self::llaveBloque(
                (int) $bloque->dia_semana,
                self::normalizarHora($bloque->hora_inicio),
                self::normalizarHora($bloque->hora_fin)
            );

            // Si hubiera duplicados (uno activo y otro borrado), se conserva
            // el activo para no "restaurar" y terminar con dos filas iguales.
            if (!isset($indice[$llave]) || ($indice[$llave]->trashed() && !$bloque->trashed())) {
                $indice[$llave] = $bloque;
            }
        }

        $cambios = 0;

        foreach (self::rangosObjetivo() as [$inicio, $fin]) {
            foreach (array_keys(self::DIAS) as $dia) {
                $llave = self::llaveBloque($dia, $inicio, $fin);

                if (isset($indice[$llave])) {
                    if ($indice[$llave]->trashed()) {
                        $indice[$llave]->restore();
                        $cambios++;
                    }
                    continue;
                }

                $indice[$llave] = self::create([
                    'dia_semana'  => $dia,
                    'hora_inicio' => $inicio,
                    'hora_fin'    => $fin,
                    'nombre'      => self::DIAS[$dia] . ' ' . substr($inicio, 0, 5) . '–' . substr($fin, 0, 5),
                ]);

                $cambios++;
            }
        }

        return $cambios;
    }

    private static function llaveBloque(int $dia, string $inicio, string $fin): string
    {
        return $dia . '|' . $inicio . '|' . $fin;
    }
}
