<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Coordinacion extends Model
{
    protected $table = 'coordinaciones';

    protected $fillable = ['nombre', 'descripcion'];

    public function docentes()
    {
        return $this->belongsToMany(User::class, 'docente_horario')
            ->withPivot(['horario_id', 'tipo_actividad'])
            ->withTimestamps();
    }
}
