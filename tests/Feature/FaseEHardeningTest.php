<?php

namespace Tests\Feature;

use App\Models\Asistencia;
use App\Models\User;
use App\Services\ExportService;
use App\Support\Csv;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class FaseEHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    // ─── CSV / Excel injection (CWE-1236) ──────────────────────────

    public function test_helper_csv_prefija_formulas_peligrosas(): void
    {
        foreach (['=1+1', '+cmd', '-2+3', '@SUM(A1)', "\tx", "\ry"] as $valor) {
            $this->assertStringStartsWith("'", Csv::sanitize($valor), "Falta prefijo para: {$valor}");
        }

        $this->assertSame('hola mundo', Csv::sanitize('hola mundo'));
        $this->assertSame('2026-01-01', Csv::sanitize('2026-01-01'));
        $this->assertSame(5, Csv::sanitize(5));
        $this->assertSame(3.5, Csv::sanitize(3.5));
        $this->assertSame('', Csv::sanitize(''));
    }

    public function test_export_csv_neutraliza_inyeccion_de_formulas(): void
    {
        $reporte = collect([(object) [
            'nombre'          => '=HYPERLINK("http://evil","click")',
            'rol_nombre'      => '+Superadmin',
            'dias_asistidos'  => 10,
            'horas_reales'    => 40,
            'horas_teoricas'  => 40,
            'productividad'   => 100,
        ]]);
        $resumen = collect([(object) [
            'fecha' => '=cmd|calc', 'total_registros' => 1, 'presentes' => 1,
            'tardes' => 0, 'inasistentes' => 0, 'apoyos_sabado' => 0,
        ]]);

        $csv = (new ExportService())->exportarCsv($reporte, $resumen, 'Título')->getContent();

        $this->assertStringContainsString("'=HYPERLINK", $csv);
        $this->assertStringContainsString("'+Superadmin", $csv);
        $this->assertStringContainsString("'=cmd|calc", $csv);
        $this->assertDoesNotMatchRegularExpression('/[;\n]=/', $csv, 'Celda que inicia con = sin neutralizar');
    }

    public function test_export_excel_neutraliza_inyeccion_de_formulas(): void
    {
        $reporte = collect([(object) [
            'nombre'          => '=1+2',
            'rol_nombre'      => '@dato',
            'dias_asistidos'  => 1,
            'horas_reales'    => 1,
            'horas_teoricas'  => 1,
            'productividad'   => 50,
        ]]);
        $resumen = collect();

        $contenido = (new ExportService())->exportarExcel($reporte, $resumen, 'Título')->getContent();
        $tmp = tempnam(sys_get_temp_dir(), 'xlsx');
        file_put_contents($tmp, $contenido);
        $libro = IOFactory::load($tmp);
        unlink($tmp);

        $this->assertStringStartsWith("'", $libro->getSheet(0)->getCell('A4')->getValue());
        $this->assertStringStartsWith("'", $libro->getSheet(0)->getCell('B4')->getValue());
    }

    // ─── Justificaciones privadas (CWE-552) ────────────────────────

    private function asistenciaDe(User $user): Asistencia
    {
        return Asistencia::factory()->create([
            'usuario_id'  => $user->id,
            'estado'      => 'Inasistente',
            'justificado' => 'No',
        ]);
    }

    public function test_la_justificacion_se_guarda_en_disco_privado(): void
    {
        Storage::fake('justificaciones');
        Storage::fake('local');
        Storage::fake('public');

        $user = User::factory()->docente()->create();
        $asistencia = $this->asistenciaDe($user);

        $this->actingAs($user)->post(route('justificaciones.solicitar', $asistencia), [
            'texto_justificacion' => 'Tuve una emergencia médica.',
            'archivo'             => UploadedFile::fake()->create('constancia.pdf', 100, 'application/pdf'),
        ])->assertRedirect(route('justificaciones'));

        $path = $asistencia->fresh()->archivo_justificacion;
        $this->assertNotNull($path);
        Storage::disk('justificaciones')->assertExists($path);
        Storage::disk('local')->assertMissing($path);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_el_archivo_solo_lo_ve_el_dueno_o_uno_con_permiso(): void
    {
        Storage::fake('justificaciones');
        Storage::fake('local');
        Storage::fake('public');

        $dueno = User::factory()->docente()->create();
        $asistencia = $this->asistenciaDe($dueno);

        $this->actingAs($dueno)->post(route('justificaciones.solicitar', $asistencia), [
            'texto_justificacion' => 'Justificación propia.',
            'archivo'             => UploadedFile::fake()->create('doc.pdf', 10, 'application/pdf'),
        ]);
        $path = $asistencia->fresh()->archivo_justificacion;

        $this->actingAs($dueno)->get(route('justificaciones.archivo', $asistencia))->assertOk();

        $intruso = User::factory()->docente()->create();
        $this->actingAs($intruso)->get(route('justificaciones.archivo', $asistencia))->assertForbidden();

        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->get(route('justificaciones.archivo', $asistencia))->assertOk();

        $this->assertNotNull($path);
    }

    public function test_archivos_heredados_en_disco_publico_se_mueven_a_privado(): void
    {
        Storage::fake('justificaciones');
        Storage::fake('local');
        Storage::fake('public');

        $user = User::factory()->docente()->create();
        $asistencia = $this->asistenciaDe($user);

        Storage::disk('public')->put('justificaciones/viejo.pdf', 'contenido antiguo');
        $asistencia->update(['archivo_justificacion' => 'justificaciones/viejo.pdf']);

        $respuesta = $this->actingAs($user)->get(route('justificaciones.archivo', $asistencia));

        $respuesta->assertOk();
        $this->assertStringContainsString('contenido antiguo', $respuesta->streamedContent());
        Storage::disk('justificaciones')->assertExists('justificaciones/viejo.pdf');
        Storage::disk('local')->assertMissing('justificaciones/viejo.pdf');
        Storage::disk('public')->assertMissing('justificaciones/viejo.pdf');
    }

    // ─── Throttling de importaciones ───────────────────────────────

    public function test_importar_usuarios_throtlea_a_partir_del_11avo_intentos(): void
    {
        $admin = User::factory()->admin()->create(['id' => 9999]);

        for ($i = 0; $i < 10; $i++) {
            $respuesta = $this->actingAs($admin)->post(route('admin.usuarios.importar'), []);
            $this->assertNotSame(429, $respuesta->getStatusCode(), "Intento {$i} no debía estar bloqueado");
        }

        $this->actingAs($admin)->post(route('admin.usuarios.importar'), [])->assertStatus(429);
    }

    // ─── Sesión absoluta (CWE-613) ─────────────────────────────────

    public function test_la_sesion_expira_por_tiempo_total_aunque_haya_actividad(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->withSession(['sesion_iniciada_en' => now()->subHours(9)->timestamp])
            ->get(route('dashboard'))
            ->assertRedirect(route('login'));
    }

    public function test_la_sesion_dentro_del_tiempo_maximo_sigue_activa(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->withSession(['sesion_iniciada_en' => now()->subMinutes(10)->timestamp])
            ->get(route('dashboard'))
            ->assertOk();
    }

    // ─── Registro de accesos denegados ─────────────────────────────

    public function test_los_403_se_registran_en_activity_log(): void
    {
        $docente = User::factory()->docente()->create();

        $this->actingAs($docente)->get(route('admin.bitacora'))->assertForbidden();

        $this->assertDatabaseHas('activity_log', [
            'accion'  => 'acceso_denegado',
            'tipo'    => 'seguridad',
            'user_id' => $docente->id,
        ]);

        $log = \App\Models\ActivityLog::where('accion', 'acceso_denegado')->latest('id')->first();
        $this->assertStringContainsString('admin/bitacora', $log->descripcion);
        $this->assertArrayHasKey('ip', $log->datos);
    }
}
