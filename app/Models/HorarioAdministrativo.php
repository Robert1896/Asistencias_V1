<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HorarioAdministrativo extends Model
{
    use HasFactory;

    protected $table = 'horarios_administrativos';

    protected $fillable = [
        'usuario_id', 'turno', 'hora_entrada', 'hora_salida',
        'lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado',
    ];

    public function usuario()
    {
        return $this->belongsTo(User::class);
    }
}
