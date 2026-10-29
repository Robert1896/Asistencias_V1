<?php

namespace App\Http\Requests;

use App\Models\Rol;
use App\Models\User;
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
        if ((int) $usuario->rol_id === 1 && (int) $actor->rol_id !== 1) {
            return false;
        }

        return true;
    }

    public function rules(): array
    {
        $usuario   = $this->route('usuario');
        $usuarioId = $usuario ? $usuario->id : null;

        return [
            'nombre'   => ['required', 'string', 'max:80', 'regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s\'-]+$/u'],
            'apellido' => ['required', 'string', 'max:80', 'regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s\'-]+$/u'],
            'cedula'   => $usuarioId
                ? "required|integer|digits_between:7,8|min:1000000|unique:users,cedula,{$usuarioId}"
                : 'required|integer|digits_between:7,8|min:1000000|unique:users,cedula',
            'email'    => $usuarioId
                ? "required|email|max:120|unique:users,email,{$usuarioId}"
                : 'required|email|max:120|unique:users,email',
            'rol_id'   => $this->reglaRol($usuario),
            'password' => array_merge(['nullable', 'confirmed'], \App\Support\PasswordPolicy::rules()),
        ];
    }

    /**
     * Regla del rol: solo roles existentes y asignables. Nadie puede cambiar
     * su propio rol (evita autopromoción, CWE-269).
     */
    private function reglaRol(?User $usuario): array
    {
        if ($usuario && (int) $usuario->id === (int) auth()->id()) {
            return ['required', 'integer', Rule::in([(int) $usuario->rol_id])];
        }

        return [
            'required',
            'integer',
            Rule::exists('roles', 'id'),
            Rule::in($this->rolesAsignables()),
        ];
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

        return array_values(array_unique(array_map('intval', $roles)));
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
            'rol_id.exists'         => 'El rol seleccionado no existe.',
            'rol_id.in'             => 'No tienes permiso para asignar ese rol.',
        ];
    }
}
