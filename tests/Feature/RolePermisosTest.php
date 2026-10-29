<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RolePermisosTest extends TestCase
{
    use RefreshDatabase;

    private function seedRolesYPermisos(): void
    {
        DB::table('roles')->insert([
            ['id' => 1, 'nombre' => 'Superadmin', 'descripcion' => 'Acceso total'],
            ['id' => 2, 'nombre' => 'Administrativo', 'descripcion' => 'Administracion'],
            ['id' => 3, 'nombre' => 'Docente', 'descripcion' => 'Profesores'],
            ['id' => 4, 'nombre' => 'Asistente', 'descripcion' => 'Asistencia'],
        ]);

        DB::table('permisos')->insert([
            ['id' => 1, 'codigo' => 'usuarios_ver', 'nombre' => 'Ver usuarios', 'modulo' => 'usuarios'],
            ['id' => 2, 'codigo' => 'usuarios_crear', 'nombre' => 'Crear usuarios', 'modulo' => 'usuarios'],
            ['id' => 3, 'codigo' => 'usuarios_editar', 'nombre' => 'Editar usuarios', 'modulo' => 'usuarios'],
            ['id' => 4, 'codigo' => 'usuarios_eliminar', 'nombre' => 'Eliminar usuarios', 'modulo' => 'usuarios'],
            ['id' => 5, 'codigo' => 'roles_ver', 'nombre' => 'Ver roles', 'modulo' => 'roles'],
            ['id' => 6, 'codigo' => 'roles_editar', 'nombre' => 'Asignar permisos', 'modulo' => 'roles'],
        ]);
    }

    private function usuarioConRolPermisos(int $rolId, array $permisoIds): User
    {
        foreach ($permisoIds as $permisoId) {
            DB::table('rol_permiso')->insertOrIgnore(['rol_id' => $rolId, 'permiso_id' => $permisoId]);
        }

        return User::factory()->create(['rol_id' => $rolId, 'estatus' => 'activo']);
    }

    private function payloadUsuario(array $sobrescribir = []): array
    {
        return array_merge([
            'nombre'   => 'Ana',
            'apellido' => 'Perez',
            'cedula'   => '19876543',
            'email'    => 'ana.perez@example.com',
            'password' => 'UsuarioSeguro2026!X',
            'password_confirmation' => 'UsuarioSeguro2026!X',
            'rol_id'   => 3,
        ], $sobrescribir);
    }

    // ─── C2: rutas de roles con permisos propios ─────────────────────

    public function test_ver_roles_requiere_roles_ver_y_no_usuarios_ver(): void
    {
        $this->seedRolesYPermisos();

        $soloVerUsuarios = $this->usuarioConRolPermisos(4, [1]);
        $this->actingAs($soloVerUsuarios)
            ->get(route('admin.roles'))
            ->assertForbidden();

        $verRoles = $this->usuarioConRolPermisos(4, [5]);
        $this->actingAs($verRoles)
            ->get(route('admin.roles'))
            ->assertOk();
    }

    public function test_editar_permisos_requiere_roles_editar(): void
    {
        $this->seedRolesYPermisos();

        $soloVerUsuarios = $this->usuarioConRolPermisos(4, [1]);
        $this->actingAs($soloVerUsuarios)
            ->put(route('admin.roles.permisos', 3), ['permisos' => [1, 2, 3]])
            ->assertForbidden();

        $editor = $this->usuarioConRolPermisos(4, [6]);
        $this->actingAs($editor)
            ->put(route('admin.roles.permisos', 3), ['permisos' => [1, 2]])
            ->assertRedirect(route('admin.roles'));

        $this->assertDatabaseHas('rol_permiso', ['rol_id' => 3, 'permiso_id' => 1]);
        $this->assertDatabaseHas('rol_permiso', ['rol_id' => 3, 'permiso_id' => 2]);
    }

    public function test_nadie_puede_editar_su_propio_rol(): void
    {
        $this->seedRolesYPermisos();

        $editor = $this->usuarioConRolPermisos(4, [6]);
        $permisosPrevios = DB::table('rol_permiso')->where('rol_id', 4)->pluck('permiso_id')->all();

        $this->actingAs($editor)
            ->put(route('admin.roles.permisos', 4), ['permisos' => [1, 2, 3, 4, 5, 6]])
            ->assertForbidden();

        $this->assertEqualsCanonicalizing(
            $permisosPrevios,
            DB::table('rol_permiso')->where('rol_id', 4)->pluck('permiso_id')->all()
        );
    }

    public function test_solo_superadmin_puede_editar_el_rol_superadmin(): void
    {
        $this->seedRolesYPermisos();

        $editor = $this->usuarioConRolPermisos(4, [6]);
        $this->actingAs($editor)
            ->put(route('admin.roles.permisos', 1), ['permisos' => [1, 2, 3, 4, 5, 6]])
            ->assertForbidden();

        $superadmin = User::factory()->admin()->create(['estatus' => 'activo']);
        $this->actingAs($superadmin)
            ->put(route('admin.roles.permisos', 3), ['permisos' => [1]])
            ->assertRedirect(route('admin.roles'));

        $this->assertDatabaseHas('rol_permiso', ['rol_id' => 3, 'permiso_id' => 1]);
    }

    // ─── C3: allow-list de rol_id ────────────────────────────────────

    public function test_no_se_puede_asignar_rol_superadmin_en_creacion(): void
    {
        $this->seedRolesYPermisos();

        $creador = $this->usuarioConRolPermisos(2, [1, 2]);

        $this->actingAs($creador)
            ->post(route('admin.usuarios.store'), $this->payloadUsuario(['rol_id' => 1]))
            ->assertSessionHasErrors('rol_id');

        $this->assertDatabaseCount('users', 1);
    }

    public function test_superadmin_si_puede_asignar_rol_superadmin(): void
    {
        $this->seedRolesYPermisos();

        $superadmin = User::factory()->admin()->create(['estatus' => 'activo']);

        $this->actingAs($superadmin)
            ->post(route('admin.usuarios.store'), $this->payloadUsuario(['rol_id' => 1]))
            ->assertRedirect(route('admin.usuarios'));

        $this->assertDatabaseHas('users', ['email' => 'ana.perez@example.com', 'rol_id' => 1]);
    }

    public function test_no_se_puede_autopromover_editando_la_propia_cuenta(): void
    {
        $this->seedRolesYPermisos();

        $editor = $this->usuarioConRolPermisos(4, [1, 3]);

        $this->actingAs($editor)
            ->put(route('admin.usuarios.update', $editor), $this->payloadUsuario([
                'cedula' => (string) $editor->cedula,
                'email'  => $editor->email,
                'rol_id' => 1,
            ]))
            ->assertSessionHasErrors('rol_id');

        $this->actingAs($editor)
            ->put(route('admin.usuarios.update', $editor), $this->payloadUsuario([
                'cedula' => (string) $editor->cedula,
                'email'  => $editor->email,
                'rol_id' => 2,
            ]))
            ->assertSessionHasErrors('rol_id');

        $this->assertSame(4, (int) $editor->fresh()->rol_id);
    }

    public function test_no_se_puede_editar_cuenta_superadmin(): void
    {
        $this->seedRolesYPermisos();

        $superadmin = User::factory()->admin()->create(['estatus' => 'activo']);
        $editor      = $this->usuarioConRolPermisos(2, [1, 3]);

        $this->actingAs($editor)
            ->put(route('admin.usuarios.update', $superadmin), $this->payloadUsuario([
                'cedula' => (string) $superadmin->cedula,
                'email'  => $superadmin->email,
                'rol_id' => 2,
            ]))
            ->assertForbidden();

        $this->assertSame(1, (int) $superadmin->fresh()->rol_id);
    }

    public function test_no_se_puede_desactivar_cuenta_superadmin(): void
    {
        $this->seedRolesYPermisos();

        $superadmin = User::factory()->admin()->create(['estatus' => 'activo']);
        $editor      = $this->usuarioConRolPermisos(2, [1, 3]);

        $this->actingAs($editor)
            ->patch(route('admin.usuarios.toggle', $superadmin))
            ->assertForbidden();

        $this->assertSame('activo', $superadmin->fresh()->estatus);
    }

    // ─── C3: importación ─────────────────────────────────────────────

    public function test_importacion_rechaza_filas_con_rol_superadmin(): void
    {
        $this->seedRolesYPermisos();

        $creador = $this->usuarioConRolPermisos(2, [1, 2]);

        $csv = "nombre;apellido;email;cedula;rol\n"
            . "Pedro;Gomez;pedro@example.com;11223344;Superadmin\n"
            . "Luisa;Rojas;luisa@example.com;11223355;Docente\n";

        $archivo = UploadedFile::fake()->createWithContent('usuarios.csv', $csv);

        $this->actingAs($creador)
            ->post(route('admin.usuarios.importar'), ['archivo' => $archivo])
            ->assertSessionHas('errores_importar');

        $this->assertDatabaseMissing('users', ['email' => 'pedro@example.com']);
        $this->assertDatabaseHas('users', ['email' => 'luisa@example.com', 'rol_id' => 3]);

        $errores = session('errores_importar');
        $this->assertStringContainsString('Superadmin', implode(' ', $errores));
    }
}
