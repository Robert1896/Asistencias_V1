<?php

namespace App\Support;

use Illuminate\Validation\Rules\Password;

/**
 * Política de contraseñas del sistema.
 *
 * Requisito de evaluación (#23): mínimo 16 caracteres, sin máximo de
 * almacenamiento, con complejidad (mayúscula, minúscula y número).
 *
 * Nota: bcrypt trunca internamente a 72 bytes, por lo que no se impone un
 * `max` de validación (el usuario puede escribir lo que quiera; los bytes
 * extra no reducen la seguridad de forma práctica para este dominio).
 */
final class PasswordPolicy
{
    public const MIN_LENGTH = 16;

    /** Regla de complejidad al iniciar sesión: solo longitud básica. */
    public const LOGIN_MIN_LENGTH = 6;

    /**
     * Reglas de validación para contraseñas nuevas (crear/editar/resetear).
     *
     * @return array{0: string, 1: Password}
     */
    public static function rules(): array
    {
        return [
            'string',
            Password::min(self::MIN_LENGTH)->letters()->mixedCase()->numbers(),
        ];
    }

    public static function ruleString(): string
    {
        return 'string|min:' . self::MIN_LENGTH . '|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/';
    }
}
