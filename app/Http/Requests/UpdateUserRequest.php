<?php

namespace App\Http\Requests;

use App\Models\Rol;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * @property string $nombre
 * @property string $apellido
 * @property int $cedula
 * @property string $email
 * @property string|null $password
 * @property int $rol_id
 */
class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        $usuario = $this->route('usuario');
        $actor   = auth()->user();

        if (!$usuario || !$actor) {
            return false;
        }

        // Una cuenta Superadmin solo puede ser editada por otra Superadmin (CWE-269)
        return !((int) $usuario->rol_id === 1 && (int) $actor->rol_id !== 1);
    }

    public function rules(): array
    {
        $usuarioId = $this->route('usuario')->id;

        $rules = [
            'nombre'   => ['required', 'string', 'max:80', 'regex:/^[a-zA-ZáéíóúñÁÉÍÓÚÑ\s]+$/u'],
            'apellido' => ['required', 'string', 'max:80', 'regex:/^[a-zA-ZáéíóúñÁÉÍÓÚÑ\s]+$/u'],
            'cedula'   => "required|integer|digits_between:7,8|min:1000000|unique:users,cedula,{$usuarioId}",
            'email'    => "required|email|max:120|unique:users,email,{$usuarioId}",
            'rol_id'   => ['required', 'integer', Rule::exists('roles', 'id'), Rule::in($this->rolesAsignables())],
            'password' => array_merge(['nullable', 'confirmed'], \App\Support\PasswordPolicy::rules()),
        ];

        // Nadie puede cambiar su propio rol (evita autopromoción, CWE-269)
        if ((int) $this->route('usuario')->id === (int) auth()->id()) {
            $rules['rol_id'][] = Rule::in([(int) $this->route('usuario')->rol_id]);
        }

        return $rules;
    }

    /**
     * Solo el Superadmin puede asignar el rol Superadmin (CWE-269).
     */
    private function rolesAsignables(): array
    {
        $actor = auth()->user();

        $roles = $actor && (int) $actor->rol_id === 1
            ? Rol::pluck('id')->all()
            : Rol::where('id', '!=', 1)->pluck('id')->all();

        if (!in_array(5, $roles)) {
            $roles[] = 5;
        }

        return array_values(array_unique($roles));
    }

    public function messages(): array
    {
        return [
            'cedula.required'       => 'La cédula es obligatoria.',
            'cedula.integer'        => 'La cédula debe ser un número entero.',
            'cedula.digits_between' => 'La cédula debe tener entre 7 y 8 dígitos.',
            'cedula.min'            => 'La cédula debe ser mínimo 1.000.000.',
            'cedula.unique'         => 'Esta cédula ya está registrada.',
            'nombre.regex'          => 'El nombre solo puede contener letras y espacios.',
            'apellido.regex'        => 'El apellido solo puede contener letras y espacios.',
            'password.min'          => 'La contraseña debe tener al menos 16 caracteres.',
            'password.regex'        => 'La contraseña debe incluir al menos una mayúscula, una minúscula y un número.',
            'password.confirmed'    => 'Las contraseñas no coinciden.',
            'email.unique'          => 'Este correo ya está registrado.',
            'email.email'           => 'El correo no es válido.',
        ];
    }
}
