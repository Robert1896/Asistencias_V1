<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Proteccion de disponibilidad (anti-DDoS / abuso de recursos).
 *
 * Cubre los limites de borde aplicados en rutas publicas, la poda de tablas
 * efimeras y las cabeceras que evitan indexacion.
 */
class ProteccionDDoS extends TestCase
{
    use RefreshDatabase;

    public function test_la_pagina_de_login_limita_peticiones_por_ip(): void
    {
        for ($i = 0; $i < 60; $i++) {
            $this->get('/login')->assertStatus(200);
        }

        $this->get('/login')->assertStatus(429);
    }

    public function test_el_post_de_login_limita_la_rafaga_por_ip(): void
    {
        // El limite por cuenta (3) y por IP (30/15 min) lo aplica el
        // controlador; la ruta solo corta rafagas por encima de 60/min.
        for ($i = 1; $i <= 60; $i++) {
            $this->post('/login', [
                'login' => "usuario{$i}@example.test",
                'password' => 'incorrecta',
            ])->assertStatus(302);
        }

        $this->post('/login', [
            'login' => 'otro@example.test',
            'password' => 'incorrecta',
        ])->assertStatus(429);
    }

    public function test_prune_ephemeral_purga_sesiones_y_cache_vencidos(): void
    {
        config([
            'session.driver' => 'database',
            'cache.default' => 'database',
        ]);

        DB::table('sessions')->insert([
            [
                'id' => 'sesion-vieja',
                'user_id' => null,
                'ip_address' => '127.0.0.1',
                'user_agent' => 'test',
                'payload' => 'x',
                'last_activity' => now()->subDays(10)->getTimestamp(),
            ],
            [
                'id' => 'sesion-nueva',
                'user_id' => null,
                'ip_address' => '127.0.0.1',
                'user_agent' => 'test',
                'payload' => 'x',
                'last_activity' => now()->getTimestamp(),
            ],
        ]);

        DB::table('cache')->insert([
            ['key' => 'cache-vieja', 'value' => 'x', 'expiration' => now()->subHour()->getTimestamp()],
            ['key' => 'cache-nueva', 'value' => 'x', 'expiration' => now()->addHour()->getTimestamp()],
        ]);

        $this->artisan('app:prune-ephemeral')->assertSuccessful();

        $this->assertDatabaseMissing('sessions', ['id' => 'sesion-vieja']);
        $this->assertDatabaseHas('sessions', ['id' => 'sesion-nueva']);

        $this->assertDatabaseMissing('cache', ['key' => 'cache-vieja']);
        $this->assertDatabaseHas('cache', ['key' => 'cache-nueva']);
    }

    public function test_robots_txt_bloquea_todo_el_sitio(): void
    {
        $contenido = (string) file_get_contents(public_path('robots.txt'));

        $this->assertStringContainsString('User-agent: *', $contenido);
        $this->assertStringContainsString('Disallow: /', $contenido);
    }
}
