<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Asistencia extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'asistencias';

    protected $fillable = [
        'usuario_id',
        'fecha',
        'horario_id',
        'salon_id',
        'departamento_id',
        'bloque_inicio',
        'bloque_fin',
        'hora_entrada',
        'hora_salida',
        'estado',
        'justificado',
        'texto_justificacion',
        'archivo_justificacion',
    ];

    protected function casts(): array
    {
        return [
            'fecha'        => 'date:Y-m-d',
            'bloque_inicio' => 'string',
            'bloque_fin'   => 'string',
            'hora_entrada' => 'string',
            'hora_salida'  => 'string',
        ];
    }

    public function usuario()
    {
        return $this->belongsTo(User::class);
    }

    public function horario()
    {
        return $this->belongsTo(Horario::class);
    }

    public function salon()
    {
        return $this->belongsTo(Salon::class);
    }

    public function departamento()
    {
        return $this->belongsTo(Departamento::class);
    }
}
