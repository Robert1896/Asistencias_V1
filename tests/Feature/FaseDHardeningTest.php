<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FaseDHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_cors_no_permite_origenes_metodos_ni_headers_comodin(): void
    {
        $this->assertNotContains('*', config('cors.allowed_origins'));
        $this->assertNotContains('*', config('cors.allowed_methods'));
        $this->assertNotContains('*', config('cors.allowed_headers'));
        $this->assertFalse(config('cors.supports_credentials'));
        $this->assertContains(config('app.url'), config('cors.allowed_origins'));
        $this->assertGreaterThan(0, config('cors.max_age'));
    }

    public function test_qr_estatico_se_cachea_solo_en_el_cliente_privado(): void
    {
        $usuario = User::factory()->create();

        $respuesta = $this->actingAs($usuario)->get(route('qr.show', $usuario));

        $respuesta->assertOk();
        $cache = $respuesta->headers->get('Cache-Control');
        $this->assertStringContainsString('private', $cache);
        $this->assertStringNotContainsString('public', $cache);
    }

    public function test_todo_recurso_cdn_de_javascript_o_hoja_de_estilos_lleva_sri(): void
    {
        $vistros = \Symfony\Component\Finder\Finder::create()
            ->in(base_path('resources/views'))
            ->name('*.blade.php');
        $vistas = [];
        foreach ($vistros as $archivo) {
            $vistas[] = $archivo->getRealPath();
        }
        $this->assertNotEmpty($vistas, 'No se encontraron vistas Blade');

        $sinSri = [];

        foreach ($vistas as $vista) {
            $contenido = file_get_contents($vista);

            foreach (preg_match_all('/<script\b[^>]*>/i', $contenido, $m) ? $m[0] : [] as $tag) {
                if (preg_match('/src="https:\/\//', $tag) && !preg_match('/integrity="sha384-[A-Za-z0-9+\/=]+"/', $tag)) {
                    $sinSri[] = basename($vista) . ' → ' . $tag;
                }
            }

            foreach (preg_match_all('/<link\b[^>]*>/i', $contenido, $m) ? $m[0] : [] as $tag) {
                if (preg_match('/rel="stylesheet"/i', $tag)
                    && preg_match('/href="https:\/\//', $tag)
                    && !preg_match('/integrity="sha384-[A-Za-z0-9+\/=]+"/', $tag)) {
                    $sinSri[] = basename($vista) . ' → ' . $tag;
                }
            }
        }

        $this->assertSame([], $sinSri, "Recursos CDN sin SRI:\n" . implode("\n", $sinSri));
    }

    public function test_csp_permite_los_origenes_remotos_que_las_vistas_utilizan(): void
    {
        $csp = $this->get('/login')->headers->get('Content-Security-Policy');

        $this->assertStringContainsString('https://unpkg.com', $csp, 'Falta unpkg en script-src (escáner QR)');
        $this->assertStringContainsString('https://fonts.bunny.net', $csp, 'Falta fonts.bunny.net en style-src');
        $this->assertMatchesRegularExpression('/style-src [^;]*https:\/\/fonts\.bunny\.net/', $csp);
        $this->assertMatchesRegularExpression('/media-src [^;]*https:\/\/cdn\.soundjay\.com/', $csp);
    }

    public function test_archivos_demo_html_no_existen_en_public(): void
    {
        $demos = [
            'tonal-progression-demo.html',
            'eye-icons-demo.html',
            'demo-modal-adhoc.html',
            'dark-mode-demo.html',
            'eye-minimal-demo.html',
        ];

        foreach ($demos as $demo) {
            $this->assertFileDoesNotExist(public_path($demo), "El demo {$demo} no debe ser servible");
        }
    }

    public function test_env_example_fija_session_encrypt_por_defecto(): void
    {
        $contenido = file_get_contents(base_path('.env.example'));

        $this->assertStringContainsString('SESSION_ENCRYPT=true', $contenido);
        $this->assertStringNotContainsString('SESSION_ENCRYPT=false', $contenido);
        $this->assertStringNotContainsString('APP_KEY=base64:', $contenido, 'El .env.example no debe traer una clave generada');
    }
}
