<?php

namespace Tests\Feature;

use App\Models\Rol;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class EstacionTest extends TestCase
{
    use RefreshDatabase;

    private function seedRoles(): void
    {
        DB::table('roles')->insert([
            ['id' => 1, 'nombre' => 'Superadmin', 'descripcion' => 'Acceso total'],
            ['id' => 3, 'nombre' => 'Docente', 'descripcion' => 'Profesores'],
        ]);

        DB::table('permisos')->insert([
            ['id' => 1, 'codigo' => 'usuarios_ver', 'nombre' => 'Ver usuarios', 'modulo' => 'usuarios'],
            ['id' => 2, 'codigo' => 'usuarios_crear', 'nombre' => 'Crear usuarios', 'modulo' => 'usuarios'],
            ['id' => 3, 'codigo' => 'usuarios_editar', 'nombre' => 'Editar usuarios', 'modulo' => 'usuarios'],
            ['id' => 4, 'codigo' => 'usuarios_eliminar', 'nombre' => 'Eliminar usuarios', 'modulo' => 'usuarios'],
        ]);

        foreach (range(1, 4) as $permisoId) {
            DB::table('rol_permiso')->insert([
                'rol_id' => 1, 'permiso_id' => $permisoId,
            ]);
        }
    }

    public function test_estacion_page_is_accessible(): void
    {
        $this->seedRoles();

        $user = User::factory()->admin()->create();

        $response = $this->actingAs($user)->get(route('estacion.index'));

        $response->assertStatus(200);
    }

    public function test_estacion_requires_authentication(): void
    {
        $response = $this->get(route('estacion.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_scan_endpoint_returns_json(): void
    {
        $this->seedRoles();

        $user = User::factory()->admin()->create();

        $response = $this->actingAs($user)->postJson(route('estacion.procesar'), [
            'qr_hash' => 'nonexistent-qr-code',
        ]);

        $response->assertJson(['ok' => false]);
    }

    public function test_scan_with_invalid_qr_returns_error(): void
    {
        $this->seedRoles();

        $user = User::factory()->admin()->create();

        $response = $this->actingAs($user)->postJson(route('estacion.procesar'), [
            'qr_hash' => 'invalid-qr',
        ]);

        $response->assertJson([
            'ok' => false,
            'tipo' => 'error',
        ]);
    }

    public function test_scan_with_empty_qr_returns_400(): void
    {
        $this->seedRoles();

        $user = User::factory()->admin()->create();

        $response = $this->actingAs($user)->postJson(route('estacion.procesar'), [
            'qr_hash' => '',
        ]);

        $response->assertStatus(400);
        $response->assertJson(['ok' => false]);
    }
}
