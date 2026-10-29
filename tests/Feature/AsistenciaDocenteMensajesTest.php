<?php

namespace Tests\Feature;

use App\Models\Asistencia;
use App\Models\Horario;
use App\Models\Salon;
use App\Models\User;
use App\Services\AttendanceService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AsistenciaDocenteMensajesTest extends TestCase
{
    use RefreshDatabase;

    /** Jueves 29/10/2026: día con malla completa. */
    private const FECHA = '2026-10-29';
    private const DIA_ISO = 4;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function roles(): void
    {
        DB::table('roles')->insertOrIgnore([
            ['id' => 1, 'nombre' => 'Superadmin', 'descripcion' => 'Acceso total'],
            ['id' => 3, 'nombre' => 'Docente', 'descripcion' => 'Profesores'],
        ]);
    }

    private function docente(): User
    {
        $this->roles();

        return User::factory()->create([
            'rol_id'          => 3,
            'estatus'         => 'activo',
            'nombre_completo' => 'Docente Prueba',
        ]);
    }

    private function asignarBloque(User $docente, int $dia, string $inicio, string $fin, ?int $salonId = null): Horario
    {
        $bloque = Horario::buscarBloque($dia, $inicio, $fin);
        $this->assertNotNull($bloque, "No existe el bloque {$inicio}-{$fin} para el día {$dia}");

        $docente->horariosDocente()->attach($bloque->id, [
            'tipo_actividad' => 'carga_academica',
            'salon_id'       => $salonId,
        ]);

        return $bloque;
    }

    public function test_un_bloque_en_curso_marca_tarde(): void
    {
        $docente = $this->docente();
        $this->asignarBloque($docente, self::DIA_ISO, '13:05:00', '13:50:00');

        Carbon::setTestNow(Carbon::parse(self::FECHA . ' 13:34:00'));

        $resultado = app(AttendanceService::class)
            ->procesarDocentePorSesion($docente->fresh(), self::FECHA, '13:34:00');

        $this->assertTrue($resultado['ok']);
        $this->assertSame('Tarde', $resultado['estado']);
        $this->assertSame('13:05 – 13:50', $resultado['bloque']);
    }

    public function test_bloque_terminado_explica_que_ya_finalizo_y_no_dice_sin_bloques(): void
    {
        $docente = $this->docente();
        $this->asignarBloque($docente, self::DIA_ISO, '13:05:00', '13:50:00');

        Carbon::setTestNow(Carbon::parse(self::FECHA . ' 14:10:00'));

        $resultado = app(AttendanceService::class)
            ->procesarDocentePorSesion($docente->fresh(), self::FECHA, '14:10:00');

        $this->assertFalse($resultado['ok']);
        $this->assertStringContainsString('ya finalizó', $resultado['mensaje']);
        $this->assertStringNotContainsString('No tiene bloques horarios asignados', $resultado['mensaje']);
        $this->assertSame('14:10:00', $resultado['diagnostico']['hora_servidor']);
        $this->assertSame(['13:05 – 13:50'], $resultado['diagnostico']['sesiones_hoy']);
    }

    public function test_qr_de_salon_con_bloque_terminado_explica_que_ya_finalizo(): void
    {
        $docente = $this->docente();
        $salon   = Salon::create([
            'codigo'    => 'A1',
            'nombre'    => 'Aula 1',
            'capacidad' => 30,
            'codigo_qr' => 'QR-A1',
        ]);

        $this->asignarBloque($docente, self::DIA_ISO, '13:05:00', '13:50:00', $salon->id);

        // Primer registro del día ya hecho (Feature B) para poder escanear el salón.
        Asistencia::create([
            'usuario_id'   => $docente->id,
            'fecha'        => self::FECHA,
            'hora_entrada' => '08:00:00',
            'hora_salida'  => '08:45:00',
            'estado'       => 'Presente',
        ]);

        Carbon::setTestNow(Carbon::parse(self::FECHA . ' 14:10:00'));

        $resultado = app(AttendanceService::class)
            ->procesarDocente($docente->fresh(), 'QR-A1');

        $this->assertFalse($resultado['ok']);
        $this->assertStringContainsString('ya finalizó', $resultado['mensaje']);
        $this->assertSame(self::FECHA, $resultado['diagnostico']['fecha']);
    }

    public function test_docente_con_bloques_solo_en_otro_dia_lo_indica(): void
    {
        $docente = $this->docente();
        $this->asignarBloque($docente, 1, '13:05:00', '13:50:00'); // lunes

        $explicacion = $docente->fresh()->explicarSinSesionActiva(self::FECHA, '13:34:00');

        $this->assertSame('otro_dia', $explicacion['caso']);
        $this->assertStringContainsString('Jueves', $explicacion['mensaje']);
        $this->assertSame(['Lunes'], $explicacion['contexto']['dias_con_bloques']);
    }

    public function test_docente_sin_asignaciones_lo_indica(): void
    {
        $docente = $this->docente();

        $explicacion = $docente->explicarSinSesionActiva(self::FECHA, '13:34:00');

        $this->assertSame('sin_asignaciones', $explicacion['caso']);
        $this->assertStringContainsString('No tiene bloques horarios asignados', $explicacion['mensaje']);
        $this->assertSame([], $explicacion['contexto']['sesiones_hoy']);
    }

    public function test_en_un_hueco_entre_bloques_indica_la_proxima_clase(): void
    {
        $docente = $this->docente();
        $this->asignarBloque($docente, self::DIA_ISO, '13:05:00', '13:50:00');

        $explicacion = $docente->fresh()->explicarSinSesionActiva(self::FECHA, '12:45:00');

        $this->assertSame('proxima', $explicacion['caso']);
        $this->assertStringContainsString('13:05', $explicacion['mensaje']);
    }

    public function test_la_pantalla_de_escaneo_muestra_el_bloque_terminado(): void
    {
        $docente = $this->docente();
        $this->asignarBloque($docente, self::DIA_ISO, '13:05:00', '13:50:00');

        Carbon::setTestNow(Carbon::parse(self::FECHA . ' 14:10:00'));

        $response = $this->actingAs($docente->fresh())->get(route('docente.escanear.salon'));

        $response->assertOk();
        $response->assertSee('Bloque ya finalizado');
        $response->assertSee('13:05 – 13:50');
    }
}
