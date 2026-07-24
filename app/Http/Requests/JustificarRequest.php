<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @property string $texto_justificacion
 * @property \Illuminate\Http\UploadedFile|null $archivo
 */
class JustificarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'texto_justificacion' => 'required|string|max:500',
            'archivo'             => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ];
    }

    public function messages(): array
    {
        return [
            'texto_justificacion.required' => 'El texto de la justificación es obligatorio.',
            'texto_justificacion.string'   => 'El texto de la justificación debe ser una cadena de texto.',
            'texto_justificacion.max'      => 'El texto de la justificación no puede exceder 500 caracteres.',
            'archivo.mimes'                => 'El archivo debe ser de tipo PDF, JPG, JPEG o PNG.',
            'archivo.max'                  => 'El archivo no puede exceder 5 MB.',
        ];
    }
}
