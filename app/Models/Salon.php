<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Salon extends Model
{
    use HasFactory;

    protected $table = 'salones';

    protected $fillable = [
        'nombre', 'codigo', 'codigo_qr', 'capacidad', 'ubicacion', 'descripcion',
    ];

    protected static function booted(): void
    {
        static::creating(function ($salon) {
            if (empty($salon->codigo_qr)) {
                $salon->codigo_qr = Str::uuid()->toString();
            }
        });
    }

    // ─── Docentes que ocupan este salón (a través de docente_horario) ───
    public function asignaciones()
    {
        return $this->hasMany(DocenteHorarioPivot::class, 'salon_id');
    }

    public function horarios()
    {
        return $this->belongsToMany(Horario::class, 'docente_horario', 'salon_id', 'horario_id')
            ->withPivot(['docente_id', 'tipo_actividad', 'coordinacion_id'])
            ->withTimestamps();
    }

    public function asistencias()
    {
        return $this->hasMany(Asistencia::class, 'salon_id');
    }

    // ─── Ocupación semanal para la cuadrícula ───
    public function ocupacionSemanal()
    {
        return $this->asignaciones()
            ->with('docente', 'horario')
            ->get()
            ->groupBy(fn ($p) => $p->horario->dia_semana);
    }
}
