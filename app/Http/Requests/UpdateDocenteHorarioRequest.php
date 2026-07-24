<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @property array|null $bloques
 */
class UpdateDocenteHorarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'bloques'                      => 'nullable|array',
            'bloques.*.tipo'               => 'nullable|in:carga_academica,asesoria,administrativa',
            'bloques.*.coord'              => 'nullable|integer|exists:coordinaciones,id',
            'bloques.*.horario_id'         => 'nullable|integer|exists:horarios,id',
            'bloques.*.salon_id'           => 'nullable|integer|exists:salones,id',
            'bloques.*.carga_academica_id' => 'nullable|integer|exists:carga_academica_docente,id',
            'bloques.*.seccion'            => 'nullable|string|max:30',
        ];
    }

    public function messages(): array
    {
        return [
            'bloques.*.tipo.in'               => 'El tipo debe ser: carga_academica, asesoria o administrativa.',
            'bloques.*.coord.exists'          => 'La coordinación seleccionada no existe.',
            'bloques.*.horario_id.exists'     => 'El horario seleccionado no existe.',
            'bloques.*.salon_id.exists'       => 'El salón seleccionado no existe.',
            'bloques.*.carga_academica_id.exists' => 'La carga académica seleccionada no existe.',
            'bloques.*.seccion.max'           => 'La sección no puede exceder 30 caracteres.',
        ];
    }
}
