<?php

namespace App\Support;

use Carbon\Carbon;

class EstadoAsistencia
{
    public const PRESENTE = 'Presente';
    public const TARDE = 'Tarde';
    public const INASISTENTE = 'Inasistente';
    public const APOYO_SABADO = 'Apoyo Sabado';
    public const SIN_ASIGNACION = 'Sin asignación';

    public const MINUTOS_TOLERANCIA = 15;

    public static function desdeHorarioFijo(string $horaAhora, ?string $horaEntrada, bool $esSabado = false, ?int $diaSemana = null): string
    {
        if ($esSabado) {
            return self::APOYO_SABADO;
        }

        if ($horaEntrada === null) {
            return self::PRESENTE;
        }

        $diaSemana ??= now()->dayOfWeek;
        if ($diaSemana > Carbon::FRIDAY) {
            return self::PRESENTE;
        }

        return self::desdeLimite($horaAhora, $horaEntrada);
    }

    public static function desdeLimite(string $horaAhora, string $horaReferencia, int $minutosTolerancia = self::MINUTOS_TOLERANCIA): string
    {
        $limite = Carbon::parse($horaReferencia)->addMinutes($minutosTolerancia)->format('H:i:s');

        return $horaAhora > $limite ? self::TARDE : self::PRESENTE;
    }

    public static function esTarde(string $horaAhora, string $horaReferencia, int $minutosTolerancia = self::MINUTOS_TOLERANCIA): bool
    {
        return self::desdeLimite($horaAhora, $horaReferencia, $minutosTolerancia) === self::TARDE;
    }
}