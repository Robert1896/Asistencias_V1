<?php

namespace Tests\Feature;

use App\Models\Rol;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class UsuarioTest extends TestCase
{
    use RefreshDatabase;

    private const ADMIN_PASSWORD = 'AdminSeguro2026!X';

    private function seedRolesAndPermissions(): void
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

    private function createAdmin(): User
    {
        return User::factory()->admin()->create(['password' => self::ADMIN_PASSWORD]);
    }

    public function test_admin_can_list_users(): void
    {
        $this->seedRolesAndPermissions();

        $admin = $this->createAdmin();
        User::factory()->count(3)->create();

        $response = $this->actingAs($admin)->get(route('admin.usuarios'));

        $response->assertStatus(200);
    }

    public function test_admin_can_create_user_via_form(): void
    {
        $this->seedRolesAndPermissions();

        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->post(route('admin.usuarios.store'), [
            'nombre' => 'Juan',
            'apellido' => 'Perez',
            'cedula' => '12345678',
            'email' => 'juan@test.com',
            'password' => 'UsuarioSeguro2026!X',
            'password_confirmation' => 'UsuarioSeguro2026!X',
            'rol_id' => 3,
        ]);

        $response->assertRedirect(route('admin.usuarios'));
        $this->assertDatabaseHas('users', ['email' => 'juan@test.com']);
    }

    public function test_admin_can_toggle_user_status(): void
    {
        $this->seedRolesAndPermissions();

        $admin = $this->createAdmin();
        $user = User::factory()->create(['estatus' => 'activo']);

        $response = $this->actingAs($admin)->patch(route('admin.usuarios.toggle', $user));

        $response->assertRedirect(route('admin.usuarios'));
        $this->assertDatabaseHas('users', ['id' => $user->id, 'estatus' => 'inactivo']);
    }

    public function test_admin_cannot_toggle_own_status(): void
    {
        $this->seedRolesAndPermissions();

        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->patch(route('admin.usuarios.toggle', $admin));

        $response->assertSessionHas('error');
        $this->assertDatabaseHas('users', ['id' => $admin->id, 'estatus' => 'activo']);
    }

    public function test_admin_can_delete_user(): void
    {
        $this->seedRolesAndPermissions();

        $admin = $this->createAdmin();
        $user = User::factory()->create();

        $response = $this->actingAs($admin)->delete(route('admin.usuarios.destroy', $user), [
            'password_confirmacion' => self::ADMIN_PASSWORD,
        ]);

        $response->assertRedirect(route('admin.usuarios'));
        $this->assertSoftDeleted('users', ['id' => $user->id]);
    }

    public function test_admin_delete_requires_correct_password(): void
    {
        $this->seedRolesAndPermissions();

        $admin = $this->createAdmin();
        $user = User::factory()->create();

        $this->actingAs($admin)
            ->delete(route('admin.usuarios.destroy', $user))
            ->assertSessionHasErrors('password_confirmacion');

        $this->actingAs($admin)
            ->delete(route('admin.usuarios.destroy', $user), [
                'password_confirmacion' => 'ClaveIncorrecta2026!X',
            ])
            ->assertSessionHasErrors('password_confirmacion');

        $this->assertDatabaseHas('users', ['id' => $user->id, 'deleted_at' => null]);
    }

    public function test_admin_cannot_delete_themselves(): void
    {
        $this->seedRolesAndPermissions();

        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->delete(route('admin.usuarios.destroy', $admin));

        $response->assertSessionHas('error');
        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }
}
