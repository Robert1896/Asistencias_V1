<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\CargaAcademicaDocente;
use App\Models\DocenteHorarioPivot;
use App\Models\Horario;
use App\Models\Salon;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LimpiarAsignacionesTest extends TestCase
{
    use RefreshDatabase;

    private function seedRolesAndPermissions(): void
    {
        DB::table('roles')->insert([
            ['id' => 1, 'nombre' => 'Superadmin', 'descripcion' => 'Acceso total'],
            ['id' => 3, 'nombre' => 'Docente', 'descripcion' => 'Profesores'],
        ]);

        DB::table('permisos')->insert([
            ['id' => 1, 'codigo' => 'usuarios_ver', 'nombre' => 'Ver usuarios', 'modulo' => 'usuarios'],
        ]);

        DB::table('rol_permiso')->insert(['rol_id' => 1, 'permiso_id' => 1]);
    }

    private function crearAsignaciones(): Horario
    {
        $horario = Horario::create([
            'dia_semana' => 1,
            'hora_inicio' => '08:00:00',
            'hora_fin'    => '08:45:00',
            'nombre'      => 'Bloque 1',
        ]);

        $salon = Salon::create([
            'nombre'    => 'Sala A',
            'codigo'    => 'A1',
            'capacidad' => 30,
            'codigo_qr' => 'qr-a1',
        ]);

        $docente = User::factory()->create(['rol_id' => 3, 'estatus' => 'activo']);

        DocenteHorarioPivot::create([
            'user_id'        => $docente->id,
            'horario_id'     => $horario->id,
            'salon_id'       => $salon->id,
            'tipo_actividad' => 'carga_academica',
        ]);

        CargaAcademicaDocente::create([
            'usuario_id'      => $docente->id,
            'materia'         => 'Matemática',
            'tipo'            => 'Clase',
            'horas_semanales' => 4,
        ]);

        return $horario;
    }

    public function test_limpiar_exige_confirmacion_escrita(): void
    {
        $this->seedRolesAndPermissions();
        $this->crearAsignaciones();

        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->delete(route('admin.salones.limpiar'), [
            'confirmar_limpiar' => '1',
        ]);

        $response->assertSessionHasErrors('confirmacion_texto');
        $this->assertDatabaseCount('docente_horario', 1);
    }

    public function test_limpiar_rechaza_texto_incorrecto(): void
    {
        $this->seedRolesAndPermissions();
        $this->crearAsignaciones();

        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->delete(route('admin.salones.limpiar'), [
            'confirmar_limpiar'   => '1',
            'confirmacion_texto'  => 'eliminar todo',
        ]);

        $response->assertSessionHasErrors('confirmacion_texto');
        $this->assertDatabaseCount('docente_horario', 1);
    }

    public function test_limpiar_funciona_con_confirmacion_correcta(): void
    {
        $this->seedRolesAndPermissions();
        $this->crearAsignaciones();

        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->delete(route('admin.salones.limpiar'), [
            'confirmar_limpiar'  => '1',
            'confirmacion_texto' => 'ELIMINAR TODO',
        ]);

        $response->assertRedirect(route('admin.salones.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseCount('docente_horario', 0);
        $this->assertDatabaseCount('carga_academica_docente', 0);
    }

    public function test_limpiar_registra_auditoria_con_cantidades(): void
    {
        $this->seedRolesAndPermissions();
        $this->crearAsignaciones();

        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->delete(route('admin.salones.limpiar'), [
            'confirmar_limpiar'  => '1',
            'confirmacion_texto' => 'ELIMINAR TODO',
        ]);

        $log = ActivityLog::where('accion', 'salon_limpiar')->latest('id')->first();

        $this->assertNotNull($log);
        $this->assertSame(1, (int) $log->datos['asignaciones_eliminadas']);
        $this->assertSame(1, (int) $log->datos['cargas_eliminadas']);
        $this->assertSame($admin->id, (int) $log->datos['user_id']);
    }

    public function test_limpiar_requiere_autenticacion(): void
    {
        $this->seedRolesAndPermissions();
        $this->crearAsignaciones();

        $response = $this->delete(route('admin.salones.limpiar'), [
            'confirmar_limpiar'  => '1',
            'confirmacion_texto' => 'ELIMINAR TODO',
        ]);

        $response->assertRedirect(route('login'));
        $this->assertDatabaseCount('docente_horario', 1);
    }

    public function test_no_se_puede_eliminar_sin_permission_usuarios_ver(): void
    {
        $this->seedRolesAndPermissions();

        $docente = User::factory()->create(['rol_id' => 3, 'estatus' => 'activo']);

        $response = $this->actingAs($docente)->delete(route('admin.salones.limpiar'), [
            'confirmar_limpiar'  => '1',
            'confirmacion_texto' => 'ELIMINAR TODO',
        ]);

        $response->assertForbidden();
    }
}
