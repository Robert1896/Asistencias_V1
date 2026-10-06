<?php

namespace Tests\Feature;

use App\Models\Permiso;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ApiPermisosTest extends TestCase
{
    use RefreshDatabase;

    private function seedRoles(): void
    {
        DB::table('roles')->insert([
            ['id' => 1, 'nombre' => 'Superadmin', 'descripcion' => 'Acceso total'],
            ['id' => 2, 'nombre' => 'Administrativo', 'descripcion' => 'Admin'],
            ['id' => 3, 'nombre' => 'Docente', 'descripcion' => 'Profesores'],
        ]);
    }

    private function grant(int $rolId, string ...$codigos): void
    {
        foreach ($codigos as $codigo) {
            $permisoId = DB::table('permisos')->insertGetId([
                'codigo'  => $codigo,
                'nombre'  => $codigo,
                'modulo'  => 'test',
            ]);
            DB::table('rol_permiso')->insert([
                'rol_id'    => $rolId,
                'permiso_id' => $permisoId,
            ]);
        }
    }

    public function test_superadmin_pasa_sin_tener_permiso_asignado(): void
    {
        $this->seedRoles();

        $super = User::factory()->create(['rol_id' => 1]);
        $this->actingAs($super, 'sanctum');

        $this->getJson('/api/v1/usuarios')->assertStatus(200);
        $this->getJson('/api/v1/asistencias')->assertStatus(200);
    }

    public function test_administrativo_sin_usuarios_ver_no_entra_a_usuarios(): void
    {
        $this->seedRoles();

        $admin = User::factory()->create(['rol_id' => 2]);
        $this->actingAs($admin, 'sanctum');

        $this->getJson('/api/v1/usuarios')->assertStatus(403);
    }

    public function test_administrativo_con_usuarios_ver_entra_a_usuarios(): void
    {
        $this->seedRoles();
        $this->grant(2, 'usuarios_ver');

        $admin = User::factory()->create(['rol_id' => 2]);
        $this->actingAs($admin, 'sanctum');

        $this->getJson('/api/v1/usuarios')->assertStatus(200);
    }

    public function test_administrativo_solo_asistencias_ver_no_entra_a_usuarios(): void
    {
        $this->seedRoles();
        $this->grant(2, 'asistencias_ver');

        $admin = User::factory()->create(['rol_id' => 2]);
        $this->actingAs($admin, 'sanctum');

        $this->getJson('/api/v1/usuarios')->assertStatus(403);
        $this->getJson('/api/v1/asistencias')->assertStatus(200);
    }

    public function test_docente_nunca_entra_a_la_api(): void
    {
        $this->seedRoles();
        $this->grant(3, 'usuarios_ver', 'asistencias_ver');

        $docente = User::factory()->create(['rol_id' => 3]);
        $this->actingAs($docente, 'sanctum');

        $this->getJson('/api/v1/usuarios')->assertStatus(403);
        $this->getJson('/api/v1/asistencias')->assertStatus(403);
    }

    public function test_usuario_inactivo_no_accede_a_la_api(): void
    {
        $this->seedRoles();
        $this->grant(2, 'usuarios_ver');

        $admin = User::factory()->create(['rol_id' => 2, 'estatus' => 'inactivo']);
        $this->actingAs($admin, 'sanctum');

        $this->getJson('/api/v1/usuarios')->assertStatus(403);
    }

    public function test_rol_fuera_de_la_lista_blanca_es_rechazado(): void
    {
        $this->seedRoles();
        DB::table('roles')->insert(['id' => 4, 'nombre' => 'Asistente', 'descripcion' => 'Operador']);
        $this->grant(4, 'usuarios_ver');

        $user = User::factory()->create(['rol_id' => 4]);
        $this->actingAs($user, 'sanctum');

        $this->getJson('/api/v1/usuarios')->assertStatus(403);
    }
}