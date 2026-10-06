<?php

namespace Tests\Unit;

use App\Support\EstadoAsistencia;
use Carbon\Carbon;
use Tests\TestCase;

class EstadoAsistenciaTest extends TestCase
{
    public function test_dentro_del_bloque_es_presente(): void
    {
        $this->assertSame(
            EstadoAsistencia::PRESENTE,
            EstadoAsistencia::desdeLimite('08:10:00', '08:00:00')
        );
    }

    public function test_en_el_limite_exacto_sigue_siendo_presente(): void
    {
        $this->assertSame(
            EstadoAsistencia::PRESENTE,
            EstadoAsistencia::desdeLimite('08:15:00', '08:00:00')
        );
    }

    public function test_un_segundo_despues_del_limite_es_tarde(): void
    {
        $this->assertSame(
            EstadoAsistencia::TARDE,
            EstadoAsistencia::desdeLimite('08:15:01', '08:00:00')
        );
    }

    public function test_entradas_tempranas_siempre_presente(): void
    {
        $this->assertSame(
            EstadoAsistencia::PRESENTE,
            EstadoAsistencia::desdeLimite('00:00:01', '23:59:00')
        );
    }

    public function test_tolerancia_es_configurable(): void
    {
        $this->assertSame(
            EstadoAsistencia::TARDE,
            EstadoAsistencia::desdeLimite('08:06:00', '08:00:00', 5)
        );
    }

    public function test_tolerancia_cero_detecta_llegada_tardia(): void
    {
        $this->assertSame(
            EstadoAsistencia::TARDE,
            EstadoAsistencia::desdeLimite('08:00:01', '08:00:00', 0)
        );
    }

    public function test_sabado_siempre_da_apoyo_sabado(): void
    {
        $this->assertSame(
            EstadoAsistencia::APOYO_SABADO,
            EstadoAsistencia::desdeHorarioFijo('07:00:00', '08:00:00', true, Carbon::SATURDAY)
        );
    }

    public function test_sabado_gana_aun_si_llega_tarde(): void
    {
        $this->assertSame(
            EstadoAsistencia::APOYO_SABADO,
            EstadoAsistencia::desdeHorarioFijo('23:00:00', '08:00:00', true, Carbon::SATURDAY)
        );
    }

    public function test_sin_horario_asignado_es_presente(): void
    {
        $this->assertSame(
            EstadoAsistencia::PRESENTE,
            EstadoAsistencia::desdeHorarioFijo('09:30:00', null, false, Carbon::WEDNESDAY)
        );
    }

    public function test_laborable_con_horario_aplica_tolerancia(): void
    {
        $this->assertSame(
            EstadoAsistencia::TARDE,
            EstadoAsistencia::desdeHorarioFijo('08:30:00', '08:00:00', false, Carbon::WEDNESDAY)
        );
    }

    public function test_lunes_a_viernes_aplican_tolerancia(): void
    {
        foreach ([Carbon::MONDAY, Carbon::TUESDAY, Carbon::WEDNESDAY, Carbon::THURSDAY, Carbon::FRIDAY] as $dia) {
            $this->assertSame(
                EstadoAsistencia::TARDE,
                EstadoAsistencia::desdeHorarioFijo('09:00:00', '08:00:00', false, $dia),
                "Día {$dia} debe aplicar tolerancia"
            );
        }
    }

    public function test_domingo_conserva_comportamiento_original_que_aplica_tolerancia(): void
    {
        $this->assertSame(
            EstadoAsistencia::TARDE,
            EstadoAsistencia::desdeHorarioFijo('09:00:00', '08:00:00', false, Carbon::SUNDAY)
        );
    }

    public function test_es_tarde_refleja_el_calculo(): void
    {
        $this->assertTrue(EstadoAsistencia::esTarde('08:20:00', '08:00:00'));
        $this->assertFalse(EstadoAsistencia::esTarde('08:05:00', '08:00:00'));
    }

    public function test_los_cuatro_estados_de_la_columna_existen_como_constantes(): void
    {
        $this->assertSame('Presente', EstadoAsistencia::PRESENTE);
        $this->assertSame('Tarde', EstadoAsistencia::TARDE);
        $this->assertSame('Inasistente', EstadoAsistencia::INASISTENTE);
        $this->assertSame('Apoyo Sabado', EstadoAsistencia::APOYO_SABADO);
    }
}