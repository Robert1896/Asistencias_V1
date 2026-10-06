<?php

namespace App\Support;

class Csv
{
    private const PELIGROSOS = ['=', '+', '-', '@', "\t", "\r"];

    public static function sanitize(mixed $valor): mixed
    {
        if (is_string($valor) && $valor !== '' && in_array($valor[0], self::PELIGROSOS, true)) {
            return "'" . $valor;
        }

        return $valor;
    }

    public static function fila(array $fila): array
    {
        return array_map([self::class, 'sanitize'], $fila);
    }
}
