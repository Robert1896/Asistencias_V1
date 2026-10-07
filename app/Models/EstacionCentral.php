<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EstacionCentral extends Model
{
    public const MODOS = ['ninguno', 'red', 'gps', 'ambos'];

    protected $table = 'estacion_central';

    protected $fillable = [
        'nombre',
        'modo',
        'latitud',
        'longitud',
        'radio_metros',
        'ip_permitida',
        'activo',
        'registrado_por',
    ];

    protected function casts(): array
    {
        return [
            'latitud'      => 'float',
            'longitud'     => 'float',
            'radio_metros' => 'integer',
            'activo'       => 'boolean',
        ];
    }

    public static function obtener(): self
    {
        return static::first() ?? new static([
            'nombre'       => 'Estación Central',
            'modo'         => 'ninguno',
            'radio_metros' => 150,
            'activo'       => false,
        ]);
    }

    public function verificaRed(): bool
    {
        return in_array($this->modo, ['red', 'ambos'], true);
    }

    public function verificaGps(): bool
    {
        return in_array($this->modo, ['gps', 'ambos'], true);
    }
}
