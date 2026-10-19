<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class QrShowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        DB::table('roles')->insertOrIgnore(['id' => 1, 'nombre' => 'Superadmin']);
        DB::table('roles')->insertOrIgnore(['id' => 3, 'nombre' => 'Docente']);
        DB::table('permisos')->updateOrInsert(
            ['id' => 1],
            ['codigo' => 'usuarios_ver', 'nombre' => 'Ver usuarios', 'modulo' => 'usuarios']
        );
        DB::table('rol_permiso')->updateOrInsert(['rol_id' => 1, 'permiso_id' => 1]);
    }

    public function test_propio_siempre_puede_ver_su_qr(): void
    {
        $user = User::factory()->create(['rol_id' => 3]);

        $this->actingAs($user)
            ->get(route('qr.show', $user))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/svg+xml');
    }

    public function test_admin_con_usuarios_ver_puede_ver_qr_de_otro(): void
    {
        $admin = User::factory()->create(['rol_id' => 1]);
        $otro  = User::factory()->create(['rol_id' => 3]);

        $this->actingAs($admin)
            ->get(route('qr.show', $otro))
            ->assertOk();
    }

    public function test_docente_sin_permiso_no_puede_ver_qr_de_otro(): void
    {
        $docente = User::factory()->create(['rol_id' => 3]);
        $otro    = User::factory()->create(['rol_id' => 3]);

        $this->actingAs($docente)
            ->get(route('qr.show', $otro))
            ->assertForbidden();
    }

    public function test_qr_estatico_nulo_devuelve_404_y_no_500(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->char('codigo_qr_estatico', 36)->nullable()->change();
        });

        $user = User::factory()->create(['rol_id' => 3, 'codigo_qr_estatico' => null]);

        $this->actingAs($user)
            ->get(route('qr.show', $user))
            ->assertNotFound();
    }

    public function test_admin_sobre_docente_sin_qr_estatico_devuelve_404(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->char('codigo_qr_estatico', 36)->nullable()->change();
        });

        $admin = User::factory()->create(['rol_id' => 1]);
        $docente = User::factory()->create(['rol_id' => 3, 'codigo_qr_estatico' => null]);

        $this->actingAs($admin)
            ->get(route('qr.show', $docente))
            ->assertNotFound();
    }
}
