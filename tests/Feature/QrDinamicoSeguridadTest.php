<?php

namespace Tests\Feature;

use App\Models\QrToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class QrDinamicoSeguridadTest extends TestCase
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
            ['id' => 1, 'codigo' => 'estacion_operar', 'nombre' => 'Operar estacion', 'modulo' => 'asistencia'],
        ]);
    }

    private function operador(): User
    {
        return User::factory()->create(['rol_id' => 1, 'estatus' => 'activo']);
    }

    private function escanear(User $operador, string $payload)
    {
        return $this->actingAs($operador)
            ->withHeaders(['X-Forwarded-For' => '127.0.0.1'])
            ->postJson(route('estacion.procesar'), ['qr_hash' => $payload]);
    }

    public function test_qr_falso_que_solo_usa_el_id_del_usuario_es_rechazado(): void
    {
        $operador = $this->operador();
        $victima = User::factory()->create(['rol_id' => 4, 'estatus' => 'activo']);

        $tokenInventado = str_repeat('a', 40);
        $payload = "DynQR:{$victima->id}:{$tokenInventado}:" . now()->timestamp;

        $this->escanear($operador, $payload)
            ->assertOk()
            ->assertJson(['ok' => false]);

        $this->assertDatabaseCount('qr_tokens', 0);
        $this->assertDatabaseCount('asistencias', 0);
    }

    public function test_qr_con_formato_de_token_invalido_es_rechazado(): void
    {
        $operador = $this->operador();
        $victima = User::factory()->create(['rol_id' => 4, 'estatus' => 'activo']);

        foreach (['abc', 'ZZZZZZZZZZZZZZZZZZZZZZZZZZZZZZZZZZZZZZZZ'] as $tokenInvalido) {
            $payload = "DynQR:{$victima->id}:{$tokenInvalido}:" . now()->timestamp;

            $this->escanear($operador, $payload)
                ->assertOk()
                ->assertJson(['ok' => false]);
        }

        $this->assertDatabaseCount('qr_tokens', 0);
    }

    public function test_token_dinamico_es_de_un_solo_uso(): void
    {
        $operador = $this->operador();
        $usuario = User::factory()->create(['rol_id' => 4, 'estatus' => 'activo']);

        $token = bin2hex(random_bytes(20));
        $expiresAt = now()->addSeconds(60);
        QrToken::create(['user_id' => $usuario->id, 'token' => $token, 'expires_at' => $expiresAt]);

        $payload = "DynQR:{$usuario->id}:{$token}:{$expiresAt->timestamp}";

        $this->escanear($operador, $payload)->assertOk();

        $this->assertDatabaseMissing('qr_tokens', [
            'user_id' => $usuario->id,
            'token'   => $token,
        ]);

        $this->escanear($operador, $payload)
            ->assertOk()
            ->assertJson(['ok' => false]);
    }

    public function test_token_expirado_es_rechazado(): void
    {
        $operador = $this->operador();
        $usuario = User::factory()->create(['rol_id' => 4, 'estatus' => 'activo']);

        $token = bin2hex(random_bytes(20));
        $expiresAt = now()->subSeconds(5);
        QrToken::create(['user_id' => $usuario->id, 'token' => $token, 'expires_at' => $expiresAt]);

        $payload = "DynQR:{$usuario->id}:{$token}:{$expiresAt->timestamp}";

        $this->escanear($operador, $payload)
            ->assertOk()
            ->assertJson(['ok' => false]);

        $this->assertDatabaseCount('asistencias', 0);
    }

    public function test_generar_un_qr_nuevo_no_invalida_los_tokens_activos_de_otras_pestanas(): void
    {
        $usuario = User::factory()->create(['rol_id' => 2, 'estatus' => 'activo']);

        $tokenOtraPestana = bin2hex(random_bytes(20));
        QrToken::create([
            'user_id'    => $usuario->id,
            'token'      => $tokenOtraPestana,
            'expires_at' => now()->addSeconds(60),
        ]);

        $this->actingAs($usuario)
            ->get(route('qr.dinamico', $usuario))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/svg+xml');

        $this->assertDatabaseHas('qr_tokens', [
            'user_id' => $usuario->id,
            'token'   => $tokenOtraPestana,
        ]);

        $this->assertSame(2, QrToken::where('user_id', $usuario->id)->count());
    }

    public function test_la_ruta_del_qr_estatico_de_usuario_ya_no_existe(): void
    {
        $usuario = User::factory()->create(['rol_id' => 2, 'estatus' => 'activo']);

        $this->actingAs($usuario)
            ->get('/qr/' . $usuario->id)
            ->assertNotFound();
    }
}
