<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

/**
 * Fase B: permisos por módulo (A1 fin de usuarios_ver como llave maestra),
 * Gate::before con estatus, middleware global de cuenta inactiva,
 * dashboard M12 y asistencia ad-hoc solo roles 2/3.
 */
class AutorizacionModulosTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    private function asignarPermisos(int $rolId, array $codigos): void
    {
        foreach (DB::table('permisos')->whereIn('codigo', $codigos)->pluck('id') as $id) {
            DB::table('rol_permiso')->insertOrIgnore(['rol_id' => $rolId, 'permiso_id' => $id]);
        }
    }

    private function user(int $rolId, array $codigos = [], string $estatus = 'activo'): User
    {
        $this->asignarPermisos($rolId, $codigos);

        return User::factory()->create(['rol_id' => $rolId, 'estatus' => $estatus]);
    }

    private function permisosDe(int $rolId): array
    {
        return DB::table('rol_permiso')->where('rol_id', $rolId)->pluck('permiso_id')->all();
    }

    // ─── A1: usuarios_ver deja de ser llave maestra ──────────────────

    public function test_usuarios_ver_no_abre_otros_modulos(): void
    {
        $soloUsuarios = $this->user(3, ['usuarios_ver']);

        foreach ([
            'admin.bitacora', 'admin.correo', 'admin.reportes', 'admin.feriados',
            'admin.materias', 'admin.roles', 'admin.horarios', 'admin.asistencias',
            'admin.salones.index', 'admin.docentes.horarios',
        ] as $ruta) {
            $this->actingAs($soloUsuarios)
                ->get(route($ruta))
                ->assertForbidden("usuarios_ver no debe abrir {$ruta}");
        }

        $this->actingAs($soloUsuarios)->get(route('admin.usuarios'))->assertOk();
    }

    public function test_bitacora_requiere_solo_bitacora_ver(): void
    {
        $sinPermiso = $this->user(3);
        $this->actingAs($sinPermiso)->get(route('admin.bitacora'))->assertForbidden();

        $conPermiso = $this->user(3, ['bitacora_ver']);
        $this->actingAs($conPermiso)->get(route('admin.bitacora'))->assertOk();
    }

    public function test_correo_requiere_solo_correo_editar(): void
    {
        $sinPermiso = $this->user(3);
        $this->actingAs($sinPermiso)->get(route('admin.correo'))->assertForbidden();

        $conPermiso = $this->user(3, ['correo_editar']);
        $this->actingAs($conPermiso)->get(route('admin.correo'))->assertOk();
    }

    public function test_feriados_requiere_solo_feriados_editar(): void
    {
        $sinPermiso = $this->user(3);
        $this->actingAs($sinPermiso)->get(route('admin.feriados'))->assertForbidden();

        $conPermiso = $this->user(3, ['feriados_editar']);
        $this->actingAs($conPermiso)->get(route('admin.feriados'))->assertOk();
    }

    public function test_reportes_requiere_reportes_ver(): void
    {
        $sinPermiso = $this->user(3);
        $this->actingAs($sinPermiso)->get(route('admin.reportes'))->assertForbidden();

        $conPermiso = $this->user(3, ['reportes_ver']);
        $this->actingAs($conPermiso)->get(route('admin.reportes'))->assertOk();
    }

    public function test_salones_se_divide_por_permisos_de_salones(): void
    {
        $sinPermiso = $this->user(3);
        $this->actingAs($sinPermiso)->get(route('admin.salones.index'))->assertForbidden();
        $this->actingAs($sinPermiso)->post(route('admin.salones.store'), [])->assertForbidden();

        $ver = $this->user(3, ['salones_ver']);
        $this->actingAs($ver)->get(route('admin.salones.index'))->assertOk();
        // Ver no implica crear
        $this->actingAs($ver)->post(route('admin.salones.store'), [])->assertForbidden();

        $crear = $this->user(3, ['salones_crear']);
        $respuesta = $this->actingAs($crear)->post(route('admin.salones.store'), []);
        $respuesta->assertStatus(302);
        $respuesta->assertSessionHasErrors('nombre');
    }

    // ─── Superadmin: bypass por rol_id, no por estatus ───────────────

    public function test_superadmin_mantiene_bypass_sin_permisos_explicitos(): void
    {
        DB::table('rol_permiso')->where('rol_id', 1)->delete();

        $super = User::factory()->create(['rol_id' => 1, 'estatus' => 'activo']);

        $this->actingAs($super)->get(route('admin.usuarios'))->assertOk();
        $this->actingAs($super)->get(route('admin.bitacora'))->assertOk();
        $this->actingAs($super)->get(route('admin.salones.index'))->assertOk();
    }

    public function test_superadmin_inactivo_no_tiene_autorizacion(): void
    {
        DB::table('rol_permiso')->where('rol_id', 1)->delete();

        $inactivo = User::factory()->create(['rol_id' => 1, 'estatus' => 'inactivo']);

        // Capa 1 (web): el middleware lo deslogea y redirige al login
        $this->actingAs($inactivo)->get(route('admin.usuarios'))->assertRedirect(route('login'));

        // Capa 2: Gate::before deniega aunque el middleware no llegara a correr
        $this->assertFalse(Gate::forUser($inactivo)->allows('usuarios_ver'));
        $this->assertFalse(Gate::forUser($inactivo)->allows('bitacora_ver'));
    }

    // ─── Middleware global: cuenta inactiva pierde la sesión web ─────

    public function test_usuario_inactivo_es_deslogueado_en_web(): void
    {
        $inactivo = $this->user(2, [], 'inactivo');

        $this->actingAs($inactivo)
            ->get(route('dashboard'))
            ->assertRedirect(route('login'));
    }

    public function test_usuario_activo_sigue_funcionando(): void
    {
        $activo = $this->user(2, ['asistencias_ver']);

        $this->actingAs($activo)->get(route('dashboard'))->assertOk();
    }

    // ─── M12: dashboard global requiere asistencias_ver ──────────────

    public function test_dashboard_global_oculto_sin_asistencias_ver(): void
    {
        // Rol 2 por defecto: horarios_ver + estacion_operar (sin asistencias_ver)
        $rol2 = $this->user(2);
        $this->actingAs($rol2)->get(route('dashboard'))->assertRedirect(route('estacion.index'));

        // Rol 4 ('Asistente') conserva su vista propia
        $rol4 = $this->user(4);
        $this->actingAs($rol4)->get(route('dashboard'))->assertOk();

        // Rol 1 con asistencias_ver por bypass
        $rol1 = User::factory()->create(['rol_id' => 1, 'estatus' => 'activo']);
        $this->actingAs($rol1)->get(route('dashboard'))->assertOk();
    }

    // ─── Asistencia ad-hoc: solo roles 2 y 3 ─────────────────────────

    public function test_procesar_adhoc_rechaza_roles_distintos_a_2_y_3(): void
    {
        $operador = $this->user(2);
        $departamento = DB::table('departamentos')->first();
        $this->assertNotNull($departamento);

        $super = User::factory()->create(['rol_id' => 1, 'estatus' => 'activo']);
        $this->actingAs($operador)
            ->postJson(route('estacion.adhoc'), [
                'user_id' => $super->id, 'departamento_id' => $departamento->id,
            ])
            ->assertStatus(422)
            ->assertJson(['ok' => false]);

        $asistente = User::factory()->create(['rol_id' => 4, 'estatus' => 'activo']);
        $this->actingAs($operador)
            ->postJson(route('estacion.adhoc'), [
                'user_id' => $asistente->id, 'departamento_id' => $departamento->id,
            ])
            ->assertStatus(422)
            ->assertJson(['ok' => false]);
    }

    public function test_procesar_adhoc_acepta_docente_sin_salon_id(): void
    {
        $operador = $this->user(2);
        $departamento = DB::table('departamentos')->first();
        $docente = User::factory()->create(['rol_id' => 3, 'estatus' => 'activo']);

        $this->actingAs($operador)
            ->postJson(route('estacion.adhoc'), [
                'user_id' => $docente->id, 'departamento_id' => $departamento->id,
            ])
            ->assertOk()
            ->assertJson(['ok' => true]);

        $this->assertDatabaseHas('asistencias', [
            'usuario_id' => $docente->id,
            'estado'     => 'Presente',
        ]);
    }

    // ─── Seeder y matriz por defecto intacta ─────────────────────────

    public function test_seeder_crea_permisos_nuevos_y_rol1_los_recibe_todos(): void
    {
        $this->assertSame(21, DB::table('permisos')->count());

        foreach ([
            'salones_crear', 'salones_editar', 'salones_eliminar',
            'bitacora_ver', 'correo_editar', 'feriados_editar', 'materias_editar',
        ] as $codigo) {
            $this->assertNotNull(
                DB::table('permisos')->where('codigo', $codigo)->first(),
                "Falta el permiso {$codigo}"
            );
        }

        $this->assertSame(21, DB::table('rol_permiso')->where('rol_id', 1)->count());
    }

    public function test_matriz_por_defecto_no_amplia_roles_2_3_4(): void
    {
        $this->assertEqualsCanonicalizing([7, 9], $this->permisosDe(2));
        $this->assertEqualsCanonicalizing([], $this->permisosDe(3));
        $this->assertEqualsCanonicalizing([7, 9, 11, 12, 13, 14], $this->permisosDe(4));

        // Ningún rol distinto al 1 recibe permisos nuevos de la Fase B
        $nuevos = DB::table('permisos')
            ->whereIn('codigo', [
                'salones_crear', 'salones_editar', 'salones_eliminar',
                'bitacora_ver', 'correo_editar', 'feriados_editar', 'materias_editar',
            ])->pluck('id');

        foreach ([2, 3, 4] as $rolId) {
            $this->assertEmpty(
                array_intersect($nuevos->all(), $this->permisosDe($rolId)),
                "El rol {$rolId} no debe recibir permisos nuevos automáticamente"
            );
        }
    }

    // ─── Vistas: accesos por defecto del Asistente se conservan ──────

    public function test_asistente_conserva_accesos_y_no_gana_modulos_sensibles(): void
    {
        $asistente = $this->user(4);

        $this->actingAs($asistente)->get(route('admin.salones.index'))->assertOk();
        $this->actingAs($asistente)->get(route('admin.horarios'))->assertOk();
        $this->actingAs($asistente)->get(route('admin.asistencias'))->assertOk();

        $this->actingAs($asistente)->get(route('admin.usuarios'))->assertForbidden();
        $this->actingAs($asistente)->get(route('admin.bitacora'))->assertForbidden();
        $this->actingAs($asistente)->get(route('admin.reportes'))->assertForbidden();
        $this->actingAs($asistente)->get(route('admin.correo'))->assertForbidden();
    }
}
