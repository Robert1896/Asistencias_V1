<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @property string $host
 * @property int $puerto
 * @property string $encriptacion
 * @property string $usuario
 * @property string $contrasena
 * @property string $direccion_from
 * @property string $nombre_from
 * @property bool $activo
 */
class UpdateCorreoConfigRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'host'           => 'required|string|max:120',
            'puerto'         => 'required|integer|min:1|max:65535',
            'encriptacion'   => 'required|in:tls,ssl,none',
            'usuario'        => 'required|string|max:120',
            'contrasena'     => 'required|string|max:255',
            'direccion_from' => 'required|email|max:120',
            'nombre_from'    => 'required|string|max:100',
            'activo'         => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'host.required'       => 'El host SMTP es obligatorio.',
            'puerto.required'     => 'El puerto es obligatorio.',
            'puerto.integer'      => 'El puerto debe ser un número entero.',
            'puerto.min'          => 'El puerto debe ser mayor a 0.',
            'puerto.max'          => 'El puerto no puede exceder 65535.',
            'encriptacion.required' => 'La encriptación es obligatoria.',
            'encriptacion.in'     => 'La encriptación debe ser: tls, ssl o none.',
            'usuario.required'    => 'El usuario SMTP es obligatorio.',
            'contrasena.required' => 'La contraseña SMTP es obligatoria.',
            'direccion_from.required' => 'La dirección de origen es obligatoria.',
            'direccion_from.email'    => 'La dirección de origen no es válida.',
            'nombre_from.required'    => 'El nombre de origen es obligatorio.',
        ];
    }
}
