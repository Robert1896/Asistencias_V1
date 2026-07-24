<?php

namespace Tests\Feature;

use App\Models\Rol;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private function seedRoles(): void
    {
        DB::table('roles')->insert([
            ['id' => 1, 'nombre' => 'Superadmin', 'descripcion' => 'Acceso total'],
            ['id' => 3, 'nombre' => 'Docente', 'descripcion' => 'Profesores'],
        ]);
    }

    public function test_authenticated_user_can_view_dashboard(): void
    {
        $this->seedRoles();

        $user = User::factory()->admin()->create();

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertStatus(200);
    }

    public function test_unauthenticated_user_is_redirected_to_login(): void
    {
        $response = $this->get(route('dashboard'));

        $response->assertRedirect(route('login'));
    }

    public function test_docente_is_redirected_to_empleado_dashboard(): void
    {
        $this->seedRoles();

        $user = User::factory()->docente()->create();

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertRedirect(route('empleado.dashboard'));
    }
}
