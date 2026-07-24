<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DiaFeriado extends Model
{
    use HasFactory;

    protected $table = 'dias_feriados';

    protected $fillable = [
        'fecha',
        'nombre',
        'recurrente',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date:Y-m-d',
            'recurrente' => 'boolean',
        ];
    }

    public static function esFeriado($fecha = null): bool
    {
        $fecha = $fecha ?? today();

        if (is_string($fecha)) {
            $fecha = \Carbon\Carbon::parse($fecha);
        }

        return static::where('fecha', $fecha->toDateString())
            ->orWhere(function ($query) use ($fecha) {
                $query->where('recurrente', true)
                    ->whereMonth('fecha', $fecha->month)
                    ->whereDay('fecha', $fecha->day);
            })
            ->exists();
    }
}
