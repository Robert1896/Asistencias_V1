<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CargaAcademicaDocente extends Model
{
    protected $table = 'carga_academica_docente';

    protected $fillable = [
        'usuario_id', 'materia', 'tipo', 'horas_semanales',
    ];

    public function usuario()
    {
        return $this->belongsTo(User::class);
    }
}
