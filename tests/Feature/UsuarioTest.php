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
        DB::table('roles')->insertOrIgnore([
            ['id' => 1, 'nombre' => 'Superadmin', 'descripcion' => 'Acceso total'],
            ['id' => 2, 'nombre' => 'Administrativo', 'descripcion' => 'Personal de oficina'],
            ['id' => 3, 'nombre' => 'Docente', 'descripcion' => 'Profesores'],
            ['id' => 4, 'nombre' => 'Asistente', 'descripcion' => 'Operador de estación'],
            ['id' => 5, 'nombre' => 'Técnico', 'descripcion' => 'Soporte técnico'],
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
        $this->assertDatabaseHas('users', ['email' => 'juan@test.com', 'rol_id' => 3]);
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

    private function datosEdicion(User $user, int $rolId): array
    {
        return [
            'nombre'   => $user->nombre,
            'apellido' => $user->apellido,
            // Cédula válida y única derivada del id (evita aleatorios < 1.000.000).
            'cedula'   => (string) (10000000 + $user->id),
            'email'    => $user->email,
            'rol_id'   => $rolId,
        ];
    }

    public function test_admin_can_change_user_role_from_tecnico_to_docente(): void
    {
        $this->seedRolesAndPermissions();

        $admin = $this->createAdmin();
        $user  = User::factory()->create(['rol_id' => 5]);

        $response = $this->actingAs($admin)
            ->put(route('admin.usuarios.update', $user), $this->datosEdicion($user, 3));

        $response->assertRedirect(route('admin.usuarios'));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('users', ['id' => $user->id, 'rol_id' => 3]);
    }

    public function test_admin_can_change_user_role_from_tecnico_to_administrativo(): void
    {
        $this->seedRolesAndPermissions();

        $admin = $this->createAdmin();
        $user  = User::factory()->create(['rol_id' => 5]);

        $this->actingAs($admin)
            ->put(route('admin.usuarios.update', $user), $this->datosEdicion($user, 2))
            ->assertRedirect(route('admin.usuarios'));

        $this->assertDatabaseHas('users', ['id' => $user->id, 'rol_id' => 2]);
    }

    public function test_changing_user_role_does_not_rename_roles(): void
    {
        $this->seedRolesAndPermissions();

        $admin = $this->createAdmin();
        $user  = User::factory()->create(['rol_id' => 5]);

        $this->actingAs($admin)
            ->put(route('admin.usuarios.update', $user), $this->datosEdicion($user, 3));

        $this->assertDatabaseHas('roles', ['id' => 1, 'nombre' => 'Superadmin']);
        $this->assertDatabaseHas('roles', ['id' => 2, 'nombre' => 'Administrativo']);
        $this->assertDatabaseHas('roles', ['id' => 3, 'nombre' => 'Docente']);
        $this->assertDatabaseHas('roles', ['id' => 4, 'nombre' => 'Asistente']);
        $this->assertDatabaseHas('roles', ['id' => 5, 'nombre' => 'Técnico']);
    }

    public function test_update_rejects_unknown_role(): void
    {
        $this->seedRolesAndPermissions();

        $admin = $this->createAdmin();
        $user  = User::factory()->create(['rol_id' => 5]);

        $this->actingAs($admin)
            ->put(route('admin.usuarios.update', $user), $this->datosEdicion($user, 99))
            ->assertSessionHasErrors('rol_id');

        $this->assertDatabaseHas('users', ['id' => $user->id, 'rol_id' => 5]);
    }

    public function test_admin_cannot_change_own_role(): void
    {
        $this->seedRolesAndPermissions();

        $admin = $this->createAdmin();

        $this->actingAs($admin)
            ->put(route('admin.usuarios.update', $admin), $this->datosEdicion($admin, 2))
            ->assertSessionHasErrors('rol_id');

        $this->assertDatabaseHas('users', ['id' => $admin->id, 'rol_id' => 1]);
    }

    public function test_error_de_validacion_reabre_la_fila_con_los_datos_escritos(): void
    {
        $this->seedRolesAndPermissions();

        $admin = $this->createAdmin();
        $user  = User::factory()->create(['rol_id' => 5, 'cedula' => 12345678]);
        $otro  = User::factory()->create(['rol_id' => 4, 'cedula' => 87654321]);

        $payload = $this->datosEdicion($user, 3);
        $payload['nombre']         = 'Evelyn Renombrada';
        $payload['cedula']         = $otro->cedula; // ya registrada → falla
        $payload['_form']          = 'editar';
        $payload['usuario_id']     = $user->id;
        $payload['usuario_nombre'] = $user->nombre_completo;

        $response = $this->followingRedirects()
            ->actingAs($admin)
            ->from(route('admin.usuarios'))
            ->put(route('admin.usuarios.update', $user), $payload);

        $response->assertOk();

        // Alerta global que nombra al usuario afectado.
        $response->assertSee('No se pudieron guardar los cambios de ' . $user->nombre_completo, false);

        // La fila del usuario se reabre sola.
        $response->assertSee('class="collapse show" id="edit-' . $user->id . '"', false);

        // El campo conserva lo que se escribió, no el valor de la base de datos.
        $response->assertSee('value="Evelyn Renombrada"', false);

        // El formulario de creación no se contamina con los datos de la fila.
        $html = $response->getContent();
        preg_match('/id="nuevoNombre"[^>]*value="([^"]*)"/s', $html, $coincidencia);
        $this->assertSame('', $coincidencia[1] ?? null);

        // Nada se guardó.
        $this->assertDatabaseHas('users', [
            'id'     => $user->id,
            'rol_id' => 5,
            'nombre' => $user->nombre,
        ]);
    }

    public function test_la_fila_de_edicion_llega_cerrada_cuando_no_hay_errores(): void
    {
        $this->seedRolesAndPermissions();

        $admin = $this->createAdmin();
        $user  = User::factory()->create(['rol_id' => 5, 'cedula' => 12345678]);

        $this->actingAs($admin)
            ->get(route('admin.usuarios'))
            ->assertOk()
            ->assertSee('class="collapse" id="edit-' . $user->id . '"', false)
            ->assertDontSee('alertErroresGlobales', false);
    }
}
