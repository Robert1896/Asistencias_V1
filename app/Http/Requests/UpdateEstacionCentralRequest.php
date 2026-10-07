<?php

namespace App\Http\Requests;

use App\Models\EstacionCentral;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEstacionCentralRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('estacion_configurar') ?? false;
    }

    public function rules(): array
    {
        $modo = (string) $this->input('modo');

        return [
            'nombre'       => ['required', 'string', 'max:120'],
            'modo'         => ['required', Rule::in(EstacionCentral::MODOS)],
            'latitud'      => [Rule::requiredIf(in_array($modo, ['gps', 'ambos'], true)), 'nullable', 'numeric', 'between:-90,90'],
            'longitud'     => [Rule::requiredIf(in_array($modo, ['gps', 'ambos'], true)), 'nullable', 'numeric', 'between:-180,180'],
            'radio_metros' => ['required', 'integer', 'min:10', 'max:20000'],
            'ip_permitida' => [Rule::requiredIf(in_array($modo, ['red', 'ambos'], true)), 'nullable', 'string', 'max:100', function (string $attribute, mixed $value, \Closure $fail) {
                if ($value === null || $value === '') {
                    return;
                }

                foreach (array_map('trim', explode(',', (string) $value)) as $rango) {
                    if ($rango === '') {
                        continue;
                    }
                    if (!preg_match('/^(\d{1,3}\.){3}\d{1,3}(\/\d{1,2})?$/', $rango)) {
                        $fail('La IP o rango de red debe tener el formato 200.45.10.5 o 200.45.10.0/24 (separe varias con coma).');
                        return;
                    }
                    if (substr_count($rango, '/') === 0 && !filter_var($rango, FILTER_VALIDATE_IP)) {
                        $fail("La IP «{$rango}» no es válida.");
                        return;
                    }
                }
            }],
            'activo'       => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required'       => 'Ingrese un nombre para la estación.',
            'modo.required'         => 'Seleccione el método de verificación.',
            'modo.in'               => 'Método de verificación no válido.',
            'latitud.required'      => 'Ingrese la latitud de la estación (requerido para verificación por GPS).',
            'longitud.required'     => 'Ingrese la longitud de la estación (requerido para verificación por GPS).',
            'latitud.between'       => 'La latitud debe estar entre -90 y 90.',
            'longitud.between'      => 'La longitud debe estar entre -180 y 180.',
            'radio_metros.required' => 'Ingrese el radio de tolerancia en metros.',
            'radio_metros.min'      => 'El radio mínimo es de 10 metros.',
            'radio_metros.max'      => 'El radio máximo es de 20,000 metros.',
            'ip_permitida.required' => 'Ingrese la IP o rango de red autorizado (requerido para verificación por red).',
        ];
    }
}
