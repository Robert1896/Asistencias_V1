<?php

namespace Tests\Feature;

use App\Models\Rol;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ApiTest extends TestCase
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

    public function test_api_returns_401_without_token(): void
    {
        $response = $this->getJson('/api/v1/usuarios');

        $response->assertStatus(401);
    }

    public function test_api_returns_403_for_non_admin_user(): void
    {
        $this->seedRoles();

        $user = User::factory()->docente()->create();
        $this->actingAs($user, 'sanctum');

        $response = $this->getJson('/api/v1/usuarios');

        $response->assertStatus(403);
        $response->assertJson(['message' => 'No autorizado.']);
    }

    public function test_api_returns_usuarios_for_admin(): void
    {
        $this->seedRoles();

        $admin = User::factory()->admin()->create();
        $this->actingAs($admin, 'sanctum');

        User::factory()->count(2)->create(['rol_id' => 3]);

        $response = $this->getJson('/api/v1/usuarios');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => ['id', 'nombre', 'apellido', 'email', 'estatus'],
            ],
        ]);
        $response->assertJsonMissing(['password', 'remember_token', 'codigo_qr_estatico']);
    }

    public function test_api_usuarios_excludes_sensitive_fields(): void
    {
        $this->seedRoles();

        $admin = User::factory()->admin()->create();
        $this->actingAs($admin, 'sanctum');

        $response = $this->getJson('/api/v1/usuarios/' . $admin->id);

        $response->assertStatus(200);
        $response->assertJsonMissing(['password', 'remember_token', 'codigo_qr_estatico', 'deleted_at']);
    }

    public function test_api_returns_asistencias_for_admin(): void
    {
        $this->seedRoles();

        $admin = User::factory()->admin()->create();
        $this->actingAs($admin, 'sanctum');

        $response = $this->getJson('/api/v1/asistencias');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => ['id', 'fecha', 'estado'],
            ],
        ]);
    }
}
