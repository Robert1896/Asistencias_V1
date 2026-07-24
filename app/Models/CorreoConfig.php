<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class CorreoConfig extends Model
{
    protected $table = 'correo_config';

    protected $fillable = [
        'host',
        'puerto',
        'encriptacion',
        'usuario',
        'contrasena',
        'direccion_from',
        'nombre_from',
        'activo',
    ];

    protected $hidden = [
        'contrasena',
    ];

    protected function casts(): array
    {
        return [
            'puerto'   => 'integer',
            'activo'   => 'boolean',
        ];
    }

    public static function obtener(): ?self
    {
        return Cache::remember('correo_config_activa', 300, function () {
            return static::where('activo', true)->first();
        });
    }

    public static function boot(): void
    {
        parent::boot();

        static::saved(function () {
            Cache::forget('correo_config_activa');
        });

        static::deleted(function () {
            Cache::forget('correo_config_activa');
        });
    }
}
