<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @property string $nombre
 * @property string $codigo
 * @property int $capacidad
 * @property string|null $ubicacion
 * @property string|null $descripcion
 */
class StoreSalonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre'      => 'required|string|max:80|unique:salones,nombre',
            'codigo'      => 'required|string|max:30|unique:salones,codigo',
            'capacidad'   => 'required|integer|min:1|max:1000',
            'ubicacion'   => 'nullable|string|max:150',
            'descripcion' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre del salón es obligatorio.',
            'nombre.unique'   => 'Ya existe un salón con ese nombre.',
            'codigo.required' => 'El código del salón es obligatorio.',
            'codigo.unique'   => 'Ya existe un salón con ese código.',
            'capacidad.required' => 'La capacidad es obligatoria.',
            'capacidad.integer'  => 'La capacidad debe ser un número entero.',
            'capacidad.min'      => 'La capacidad debe ser al menos 1.',
        ];
    }
}
