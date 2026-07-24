<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class DocenteHorarioPivot extends Pivot
{
    protected $table = 'docente_horario';

    protected $fillable = [
        'user_id', 'horario_id', 'tipo_actividad', 'coordinacion_id',
        'salon_id', 'carga_academica_id', 'seccion',
    ];

    protected $casts = [
        'user_id'            => 'integer',
        'horario_id'         => 'integer',
        'coordinacion_id'    => 'integer',
        'salon_id'           => 'integer',
        'carga_academica_id' => 'integer',
    ];

    public function docente()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function horario()
    {
        return $this->belongsTo(Horario::class, 'horario_id');
    }

    public function salon()
    {
        return $this->belongsTo(Salon::class, 'salon_id');
    }

    public function coordinacion()
    {
        return $this->belongsTo(Coordinacion::class, 'coordinacion_id');
    }

    public function cargaAcademica()
    {
        return $this->belongsTo(CargaAcademicaDocente::class, 'carga_academica_id');
    }
}
