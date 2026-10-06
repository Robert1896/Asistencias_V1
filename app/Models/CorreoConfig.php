<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

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

    /**
     * Fase C: contraseña SMTP descifrada. Si existe en texto plano (registro
     * heredado), la migra a cifrado en reposo y la devuelve tal cual.
     */
    public function descifrarContrasena(): string
    {
        $valor = (string) $this->contrasena;

        if ($valor === '') {
            return '';
        }

        try {
            return Crypt::decryptString($valor);
        } catch (\Throwable $e) {
            try {
                $this->forceFill(['contrasena' => Crypt::encryptString($valor)])->saveQuietly();
            } catch (\Throwable $ignorada) {
            }

            return $valor;
        }
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
