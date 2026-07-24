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
     */
    public static function todosLosBloques(): array
    {
        return self::query()
            ->selectRaw('MIN(id) as id, hora_inicio, hora_fin')
            ->groupBy('hora_inicio', 'hora_fin')
            ->orderBy('hora_inicio')
            ->orderBy('hora_fin')
            ->get()
            ->toArray();
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
}
