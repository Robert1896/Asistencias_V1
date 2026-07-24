<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @property string $fecha
 * @property string $nombre
 * @property bool|null $recurrente
 */
class StoreFeriadoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'fecha'      => 'required|date|after_or_equal:2020-01-01',
            'nombre'     => 'required|string|max:120',
            'recurrente' => 'required|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'fecha.required' => 'La fecha es obligatoria.',
            'fecha.date'     => 'La fecha debe ser una fecha válida.',
            'fecha.after_or_equal' => 'La fecha debe ser posterior o igual al 01/01/2020.',
            'nombre.required' => 'El nombre del feriado es obligatorio.',
            'nombre.max'      => 'El nombre no puede exceder 120 caracteres.',
            'recurrente.required' => 'El campo recurrente es obligatorio.',
            'recurrente.boolean'  => 'El campo recurrente debe ser verdadero o falso.',
        ];
    }
}
