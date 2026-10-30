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

/**
 * Una clase de 2 horas académicas son dos bloques consecutivos de 45 min
 * (1 h 30 min en total). Se debe registrar la asistencia una sola vez para
 * toda la clase y la tardanza se mide desde el inicio del primer bloque.
 */
class AsistenciaClaseDobleTest extends TestCase
{
    use RefreshDatabase;

    private const FECHA = '2026-10-30'; // viernes
    private const DIA_ISO = 5;

    protected function setUp(): void
    {
        parent::setUp();

        // En SQLite el esquema base trae único (usuario_id, fecha), mientras
        // que MySQL ya usa único por bloque. Se replica aquí para poder probar
        // varias asistencias del mismo día.
        DB::statement('DROP INDEX IF EXISTS asistencias_usuario_id_fecha_unique');
        DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS unique_asistencia_bloque ON asistencias (usuario_id, fecha, horario_id)');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function docente(): User
    {
        DB::table('roles')->insertOrIgnore([
            ['id' => 1, 'nombre' => 'Superadmin', 'descripcion' => 'Acceso total'],
            ['id' => 3, 'nombre' => 'Docente', 'descripcion' => 'Profesores'],
        ]);

        return User::factory()->create([
            'rol_id'          => 3,
            'estatus'         => 'activo',
            'nombre_completo' => 'Docente Clase Doble',
        ]);
    }

    private function salon(): Salon
    {
        return Salon::create([
            'codigo'    => 'A1',
            'nombre'    => 'Aula 1',
            'capacidad' => 30,
            'codigo_qr' => 'QR-A1',
        ]);
    }

    private function asignarBloque(User $docente, Horario $bloque, Salon $salon, string $seccion): void
    {
        $docente->horariosDocente()->attach($bloque->id, [
            'tipo_actividad' => 'carga_academica',
            'salon_id'       => $salon->id,
            'seccion'        => $seccion,
        ]);
    }

    /** @return array{0: Horario, 1: Horario} */
    private function asignarClaseDoble(User $docente, Salon $salon, string $seccion = 'A'): array
    {
        $b1 = Horario::buscarBloque(self::DIA_ISO, '13:05:00', '13:50:00');
        $b2 = Horario::buscarBloque(self::DIA_ISO, '13:50:00', '14:35:00');

        $this->assertNotNull($b1, 'No existe el bloque 13:05-13:50');
        $this->assertNotNull($b2, 'No existe el bloque 13:50-14:35');

        $this->asignarBloque($docente, $b1, $salon, $seccion);
        $this->asignarBloque($docente, $b2, $salon, $seccion);

        return [$b1, $b2];
    }

    /** Feature B: el primer registro del día debe existir para poder usar el QR de salón. */
    private function seedPrimerRegistro(User $docente): void
    {
        Asistencia::create([
            'usuario_id'   => $docente->id,
            'fecha'        => self::FECHA,
            'hora_entrada' => '07:00:00',
            'hora_salida'  => '07:45:00',
            'estado'       => 'Presente',
        ]);
    }

    private function escanear(User $docente): array
    {
        return app(AttendanceService::class)->procesarDocente($docente->fresh(), 'QR-A1');
    }

    public function test_dos_bloques_consecutivos_forman_una_sola_clase(): void
    {
        $docente = $this->docente();
        $salon   = $this->salon();
        $this->asignarClaseDoble($docente, $salon);

        $sesiones = $docente->obtenerSesionesDelDia(self::FECHA);

        $this->assertCount(1, $sesiones);
        $this->assertSame(Horario::normalizarHora('13:05'), Horario::normalizarHora($sesiones[0]['hora_inicio']));
        $this->assertSame(Horario::normalizarHora('14:35'), Horario::normalizarHora($sesiones[0]['hora_fin']));
        $this->assertCount(2, $sesiones[0]['bloques']);
    }

    public function test_bloques_consecutivos_de_secciones_distintas_no_se_unen(): void
    {
        $docente = $this->docente();
        $salon   = $this->salon();

        $b1 = Horario::buscarBloque(self::DIA_ISO, '13:05:00', '13:50:00');
        $b2 = Horario::buscarBloque(self::DIA_ISO, '13:50:00', '14:35:00');

        $this->asignarBloque($docente, $b1, $salon, 'A');
        $this->asignarBloque($docente, $b2, $salon, 'B');

        $this->assertCount(2, $docente->obtenerSesionesDelDia(self::FECHA));
    }

    public function test_un_solo_escaneo_registra_toda_la_clase(): void
    {
        Carbon::setTestNow(Carbon::parse(self::FECHA . ' 13:10:00'));

        $docente = $this->docente();
        $salon   = $this->salon();
        [$b1]    = $this->asignarClaseDoble($docente, $salon);
        $this->seedPrimerRegistro($docente);

        $resultado = $this->escanear($docente);

        $this->assertTrue($resultado['ok']);
        $this->assertSame('Presente', $resultado['estado']);
        $this->assertSame('13:05 – 14:35', $resultado['bloque']);

        $clase = Asistencia::where('usuario_id', $docente->id)
            ->whereNotNull('horario_id')
            ->get();

        $this->assertCount(1, $clase);
        $this->assertEquals($b1->id, $clase[0]->horario_id);
        $this->assertSame(Horario::normalizarHora('13:05'), Horario::normalizarHora($clase[0]->bloque_inicio));
        $this->assertSame(Horario::normalizarHora('14:35'), Horario::normalizarHora($clase[0]->bloque_fin));
    }

    public function test_llegar_tarde_al_segundo_bloque_cuenta_como_tarde(): void
    {
        Carbon::setTestNow(Carbon::parse(self::FECHA . ' 14:10:00'));

        $docente = $this->docente();
        $salon   = $this->salon();
        $this->asignarClaseDoble($docente, $salon);
        $this->seedPrimerRegistro($docente);

        $resultado = $this->escanear($docente);

        $this->assertTrue($resultado['ok']);
        $this->assertSame('Tarde', $resultado['estado']);
        $this->assertSame('13:05 – 14:35', $resultado['bloque']);
    }

    public function test_el_segundo_escaneo_no_duplica_la_asistencia(): void
    {
        Carbon::setTestNow(Carbon::parse(self::FECHA . ' 13:10:00'));

        $docente = $this->docente();
        $salon   = $this->salon();
        $this->asignarClaseDoble($docente, $salon);
        $this->seedPrimerRegistro($docente);

        $this->escanear($docente);

        Carbon::setTestNow(Carbon::parse(self::FECHA . ' 14:20:00'));

        $segundo = $this->escanear($docente);

        $this->assertTrue($segundo['ok']);
        $this->assertSame('info', $segundo['tipo']);
        $this->assertStringContainsString('ya registró asistencia', $segundo['mensaje']);

        $this->assertSame(1, Asistencia::where('usuario_id', $docente->id)
            ->whereNotNull('horario_id')
            ->count());
    }

    public function test_mark_absents_marca_una_sola_ausencia_por_clase(): void
    {
        Carbon::setTestNow(Carbon::parse(self::FECHA . ' 20:00:00'));

        $docente = $this->docente();
        $salon   = $this->salon();
        $this->asignarClaseDoble($docente, $salon);

        $this->artisan('app:mark-absents')->assertSuccessful();

        $ausencias = Asistencia::where('usuario_id', $docente->id)
            ->where('fecha', self::FECHA)
            ->where('estado', 'Inasistente')
            ->get();

        $this->assertCount(1, $ausencias);
        $this->assertSame('13:05 – 14:35', substr($ausencias[0]->bloque_inicio, 0, 5) . ' – ' . substr($ausencias[0]->bloque_fin, 0, 5));
    }
}
