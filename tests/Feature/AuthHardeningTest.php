<?php

namespace Tests\Feature;

use App\Models\CorreoConfig;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

/**
 * Fase C: throttle de login (por identificador e IP), respuesta generica en
 * recuperacion de contrasena, current_password en cambios sensibles de la
 * cuenta, invalidacion de sesiones al cambiar contrasena y contrasena SMTP
 * cifrada en reposo con migracion de textos planos heredados.
 */
class AuthHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    private function payloadPerfil(array $sobrescribir = []): array
    {
        return array_merge([
            'nombre'   => 'Ana',
            'apellido' => 'Perez',
            'cedula'   => '19876543',
            'email'    => 'ana.perez@ejemplo.com',
        ], $sobrescribir);
    }

    // --- Throttle de login -------------------------------------------------

    public function test_login_throttled_tras_5_fallos(): void
    {
        $credenciales = ['login' => 'victima@ejemplo.com', 'password' => 'incorrecta'];

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('login'), $credenciales)->assertSessionHasErrors('login');
        }

        $respuesta = $this->post(route('login'), $credenciales);
        $respuesta->assertSessionHasErrors('login');
        $this->assertStringContainsString('Demasiados intentos', session('errors')->first('login'));
    }

    public function test_login_throttled_por_ip_tras_30_fallos(): void
    {
        for ($i = 1; $i <= 30; $i++) {
            $this->post(route('login'), [
                'login'    => "atacante{$i}@ejemplo.com",
                'password' => 'incorrecta',
            ])->assertSessionHasErrors('login');
        }

        $respuesta = $this->post(route('login'), [
            'login'    => 'otro-correo@ejemplo.com',
            'password' => 'incorrecta',
        ]);
        $respuesta->assertSessionHasErrors('login');
        $this->assertStringContainsString('esta IP', session('errors')->first('login'));
    }

    // --- Recuperacion de contrasena sin enumeracion (CWE-204) --------------

    public function test_forgot_password_no_revela_existencia_del_correo(): void
    {
        Notification::fake();

        $this->post(route('password.email'), ['email' => 'no-existe@ejemplo.com'])
            ->assertSessionHas('success');
        $mensajeInexistente = session('success');
        Notification::assertNothingSent();

        $usuario = User::factory()->create();
        $this->post(route('password.email'), ['email' => $usuario->email])
            ->assertSessionHas('success');

        $this->assertSame($mensajeInexistente, session('success'));
        Notification::assertSentTo($usuario, ResetPassword::class);
    }

    public function test_forgot_password_limitado_por_ip(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post(route('password.email'), ['email' => 'algo@ejemplo.com'])
                ->assertSessionHas('success');
        }

        $this->post(route('password.email'), ['email' => 'algo@ejemplo.com'])
            ->assertStatus(429);
    }

    // --- current_password en cambios sensibles (CWE-620) -------------------

    public function test_cambio_password_en_perfil_requiere_password_actual(): void
    {
        $usuario = User::factory()->create(['password' => 'ClaveOriginalSegura2026!', 'cedula' => '19876543']);

        $this->actingAs($usuario)->put(route('profile.update'), $this->payloadPerfil([
            'email'                 => $usuario->email,
            'password'              => 'ClaveNuevaSegura2026!x',
            'password_confirmation' => 'ClaveNuevaSegura2026!x',
        ]))->assertSessionHasErrors('current_password');

        $this->actingAs($usuario)->put(route('profile.update'), $this->payloadPerfil([
            'email'                 => $usuario->email,
            'password'              => 'ClaveNuevaSegura2026!x',
            'password_confirmation' => 'ClaveNuevaSegura2026!x',
            'current_password'      => 'equivocada',
        ]))->assertSessionHasErrors('current_password');
        $this->assertTrue(Hash::check('ClaveOriginalSegura2026!', $usuario->fresh()->password));

        $tokenAntes = $usuario->fresh()->remember_token;

        $this->actingAs($usuario)->put(route('profile.update'), $this->payloadPerfil([
            'email'                 => $usuario->email,
            'password'              => 'ClaveNuevaSegura2026!x',
            'password_confirmation' => 'ClaveNuevaSegura2026!x',
            'current_password'      => 'ClaveOriginalSegura2026!',
        ]))->assertRedirect(route('profile.edit'));

        $this->assertTrue(Hash::check('ClaveNuevaSegura2026!x', $usuario->fresh()->password));
        $this->assertNotSame($tokenAntes, $usuario->fresh()->remember_token);
    }

    public function test_cambio_email_en_perfil_requiere_password_actual(): void
    {
        $usuario = User::factory()->create(['password' => 'ClaveOriginalSegura2026!', 'cedula' => '19876543']);

        $this->actingAs($usuario)->put(route('profile.update'), $this->payloadPerfil([
            'email' => 'correo-nuevo@ejemplo.com',
        ]))->assertSessionHasErrors('current_password');

        $this->actingAs($usuario)->put(route('profile.update'), $this->payloadPerfil([
            'email'            => 'correo-nuevo@ejemplo.com',
            'current_password' => 'ClaveOriginalSegura2026!',
        ]))->assertRedirect(route('profile.edit'));

        $this->assertSame('correo-nuevo@ejemplo.com', $usuario->fresh()->email);
    }

    // --- Invalidacion de sesiones al cambiar contrasena (CWE-613) ----------

    public function test_reset_por_correo_invalida_sesiones_del_usuario(): void
    {
        $usuario = User::factory()->create(['password' => 'ClaveOriginalSegura2026!']);
        $usuario->forceFill(['remember_token' => 'token-viejo'])->save();
        $tokenAntes = $usuario->fresh()->remember_token;

        config(['session.driver' => 'database']);
        DB::table('sessions')->insert([
            'id'            => 'sesion-vieja-123',
            'user_id'       => $usuario->id,
            'ip_address'    => null,
            'user_agent'    => null,
            'payload'       => base64_encode(serialize([])),
            'last_activity' => time(),
        ]);

        $token = Password::broker()->createToken($usuario);

        $this->post(route('password.update'), [
            'token'                 => $token,
            'email'                 => $usuario->email,
            'password'              => 'ClaveNuevaSegura2026!x',
            'password_confirmation' => 'ClaveNuevaSegura2026!x',
        ])->assertRedirect(route('login'));

        $usuario->refresh();
        $this->assertTrue(Hash::check('ClaveNuevaSegura2026!x', $usuario->password));
        $this->assertNotSame($tokenAntes, $usuario->remember_token);
        $this->assertDatabaseMissing('sessions', ['id' => 'sesion-vieja-123']);
    }

    public function test_admin_al_cambiar_password_rota_recuerda_del_usuario(): void
    {
        $admin = User::factory()->admin()->create();
        $objetivo = User::factory()->create(['password' => 'ClaveOriginalSegura2026!', 'cedula' => '19876544']);
        $objetivo->forceFill(['remember_token' => 'token-viejo'])->save();

        $this->actingAs($admin)->put(route('admin.usuarios.update', $objetivo), [
            'nombre'                => $objetivo->nombre,
            'apellido'              => $objetivo->apellido,
            'cedula'                => '19876544',
            'email'                 => $objetivo->email,
            'rol_id'                => $objetivo->rol_id,
            'password'              => 'ClaveNuevaSegura2026!x',
            'password_confirmation' => 'ClaveNuevaSegura2026!x',
        ])->assertRedirect(route('admin.usuarios'));

        $objetivo->refresh();
        $this->assertTrue(Hash::check('ClaveNuevaSegura2026!x', $objetivo->password));
        $this->assertNotSame('token-viejo', $objetivo->remember_token);
    }

    public function test_invalidar_otras_sesiones_archivo_file(): void
    {
        $carpeta = sys_get_temp_dir() . '/sesiones_test_' . uniqid();
        mkdir($carpeta, 0777, true);

        config([
            'session.driver'   => 'file',
            'session.encrypt'  => false,
            'session.files'    => $carpeta,
        ]);

        $usuario = User::factory()->create();
        $payload = serialize(['login_web' => $usuario->id, 'x' => 'y']);

        file_put_contents($carpeta . '/sesion-de-otro', $payload);
        file_put_contents($carpeta . '/sesion-actual-mia', $payload);

        $usuario->invalidarOtrasSesiones('sesion-actual-mia');

        $this->assertFileDoesNotExist($carpeta . '/sesion-de-otro');
        $this->assertFileExists($carpeta . '/sesion-actual-mia');
        $this->assertNotNull($usuario->fresh()->remember_token);

        foreach (glob($carpeta . '/*') ?: [] as $archivo) {
            @unlink($archivo);
        }
        @rmdir($carpeta);
    }

    public function test_invalidar_otras_sesiones_archivo_file_encriptado(): void
    {
        $carpeta = sys_get_temp_dir() . '/sesiones_enc_test_' . uniqid();
        mkdir($carpeta, 0777, true);

        config([
            'session.driver'   => 'file',
            'session.encrypt'  => true,
            'session.files'    => $carpeta,
        ]);

        $usuario = User::factory()->create();
        $payload = serialize(['login_web' => $usuario->id]);

        // Simula SESSION_ENCRYPT=true: payload con Crypt::encrypt como EncryptedStore
        file_put_contents($carpeta . '/cifrado-de-otro', Crypt::encrypt($payload));
        file_put_contents($carpeta . '/cifrado-actual', Crypt::encrypt($payload));

        $usuario->invalidarOtrasSesiones('cifrado-actual');

        $this->assertFileDoesNotExist($carpeta . '/cifrado-de-otro');
        $this->assertFileExists($carpeta . '/cifrado-actual');

        foreach (glob($carpeta . '/*') ?: [] as $archivo) {
            @unlink($archivo);
        }
        @rmdir($carpeta);
    }

    // --- Contrasena SMTP cifrada en reposo --------------------------------

    public function test_config_correo_guarda_password_cifrada(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->put(route('admin.correo.update'), [
            'host'           => 'smtp.gmail.com',
            'puerto'         => 587,
            'encriptacion'   => 'tls',
            'usuario'        => 'no-reply@universidad.edu',
            'contrasena'     => 'secreto-smtp-123',
            'direccion_from' => 'no-reply@universidad.edu',
            'nombre_from'    => 'Sistema de Asistencia',
            'activo'         => 1,
        ])->assertRedirect(route('admin.correo'));

        $guardada = DB::table('correo_config')->value('contrasena');

        $this->assertNotSame('secreto-smtp-123', $guardada);
        $this->assertSame('secreto-smtp-123', Crypt::decryptString($guardada));
    }

    public function test_password_smtp_en_texto_plano_se_migra_a_cifrado(): void
    {
        DB::table('correo_config')->update(['contrasena' => 'plana-legacy-123']);

        $config = CorreoConfig::first();
        $this->assertSame('plana-legacy-123', $config->descifrarContrasena());

        $config->refresh();
        $this->assertNotSame('plana-legacy-123', $config->contrasena);
        $this->assertSame('plana-legacy-123', Crypt::decryptString($config->contrasena));
    }
}
