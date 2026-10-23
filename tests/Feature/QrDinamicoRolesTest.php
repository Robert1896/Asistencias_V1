<?php

namespace Tests\Feature;

use App\Models\HorarioAdministrativo;
use App\Models\QrToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class QrDinamicoRolesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        DB::table('roles')->insertOrIgnore([
            ['id' => 1, 'nombre' => 'Superadmin', 'descripcion' => 'Acceso total'],
            ['id' => 2, 'nombre' => 'Administrativo', 'descripcion' => 'Oficina'],
            ['id' => 3, 'nombre' => 'Docente', 'descripcion' => 'Docente'],
            ['id' => 4, 'nombre' => 'Asistente', 'descripcion' => 'Asistencia'],
        ]);
        DB::table('permisos')->insertOrIgnore([
            ['id' => 1, 'codigo' => 'usuarios_ver', 'nombre' => 'Ver usuarios', 'modulo' => 'usuarios'],
            ['id' => 2, 'codigo' => 'horarios_editar', 'nombre' => 'Editar horarios', 'modulo' => 'horarios'],
            ['id' => 3, 'codigo' => 'estacion_operar', 'nombre' => 'Operar estacion', 'modulo' => 'asistencia'],
        ]);
    }

    public function test_mi_qr_dinamico_disponible_para_administrativo_y_asistente(): void
    {
        $admin = User::factory()->create(['rol_id' => 2, 'estatus' => 'activo']);
        $asistente = User::factory()->create(['rol_id' => 4, 'estatus' => 'activo']);

        $this->actingAs($admin)->get(route('qr.dinamico.page'))->assertOk();
        $this->actingAs($asistente)->get(route('qr.dinamico.page'))->assertOk();
    }

    public function test_superadmin_puede_marcar_asistencia_con_qr_dinamico(): void
    {
        $super = User::factory()->create(['rol_id' => 1, 'estatus' => 'activo']);

        $token = bin2hex(random_bytes(20));
        $expiresAt = now()->addSeconds(60);
        QrToken::create(['user_id' => $super->id, 'token' => $token, 'expires_at' => $expiresAt]);
        $payload = "DynQR:{$super->id}:{$token}:{$expiresAt->timestamp}";

        $response = $this->withHeaders(['X-Forwarded-For' => '127.0.0.1'])->postJson(route('estacion.procesar'), ['qr_hash' => $payload]);
        $response->assertStatus(200);
    }

    public function test_asistente_con_horario_fijo_marca_entrada_y_salida_con_qr_dinamico(): void
    {
        $asistente = User::factory()->create(['rol_id' => 4, 'estatus' => 'activo']);

        HorarioAdministrativo::create([
            'usuario_id' => $asistente->id,
            'turno' => 'Matutino',
            'hora_entrada' => '07:00',
            'hora_salida' => '13:30',
            'lunes' => true,
            'martes' => true,
            'miercoles' => true,
            'jueves' => true,
            'viernes' => true,
            'sabado' => false,
        ]);

        $token = bin2hex(random_bytes(20));
        $expiresAt = now()->addSeconds(60);
        QrToken::create(['user_id' => $asistente->id, 'token' => $token, 'expires_at' => $expiresAt]);
        $payload = "DynQR:{$asistente->id}:{$token}:{$expiresAt->timestamp}";

        $this->withHeaders(['X-Forwarded-For' => '127.0.0.1'])->postJson(route('estacion.procesar'), ['qr_hash' => $payload])->assertStatus(200);

        $token2 = bin2hex(random_bytes(20));
        $expiresAt2 = now()->addSeconds(60);
        QrToken::create(['user_id' => $asistente->id, 'token' => $token2, 'expires_at' => $expiresAt2]);
        $payload2 = "DynQR:{$asistente->id}:{$token2}:{$expiresAt2->timestamp}";

        $this->withHeaders(['X-Forwarded-For' => '127.0.0.2'])->postJson(route('estacion.procesar'), ['qr_hash' => $payload2])->assertStatus(200);
    }

    public function test_admin_puede_guardar_horario_fijo_para_asistente(): void
    {
        $admin = User::factory()->create(['rol_id' => 1, 'estatus' => 'activo']);
        $asistente = User::factory()->create(['rol_id' => 4, 'estatus' => 'activo']);

        $this->actingAs($admin)
            ->post(route('admin.horarios.guardar-admin'), [
                'usuario_id' => $asistente->id,
                'turno' => 'Vespertino',
                'hora_entrada' => '13:00',
                'hora_salida' => '19:00',
                'lunes' => 1,
                'martes' => 1,
                'miercoles' => 1,
                'jueves' => 1,
                'viernes' => 1,
                'sabado' => 0,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('horarios_administrativos', [
            'usuario_id' => $asistente->id,
            'turno' => 'Vespertino',
        ]);
    }
}
