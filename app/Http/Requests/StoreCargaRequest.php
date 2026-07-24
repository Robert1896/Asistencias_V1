<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @property int $usuario_id
 * @property string $materia
 * @property string $tipo
 * @property float $horas_semanales
 */
class StoreCargaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'usuario_id'      => 'required|integer|exists:users,id',
            'materia'         => 'required|string|max:120',
            'tipo'            => 'required|in:Clase,Coordinacion,Laboratorio,Apoyo',
            'horas_semanales' => 'required|numeric|min:0.5|max:40',
        ];
    }

    public function messages(): array
    {
        return [
            'usuario_id.required' => 'El usuario es obligatorio.',
            'usuario_id.exists'   => 'El usuario seleccionado no existe.',
            'materia.required'    => 'La materia es obligatoria.',
            'materia.max'         => 'La materia no puede exceder 120 caracteres.',
            'tipo.required'       => 'El tipo de actividad es obligatorio.',
            'tipo.in'             => 'El tipo de actividad debe ser: Clase, Coordinación, Laboratorio o Apoyo.',
            'horas_semanales.required' => 'Las horas semanales son obligatorias.',
            'horas_semanales.numeric'  => 'Las horas semanales deben ser un valor numérico.',
            'horas_semanales.min'      => 'Las horas semanales deben ser al menos 0.5.',
            'horas_semanales.max'      => 'Las horas semanales no pueden exceder 40.',
        ];
    }
}
