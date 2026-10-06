<?php

namespace Tests\Feature;

use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    private function csp(string $response): string
    {
        preg_match('/Content-Security-Policy:\s*(.+)/i', $response, $m);
        return $m[1] ?? '';
    }

    public function test_todas_las_respuestas_llevan_csp(): void
    {
        $response = $this->get('/login');

        $this->assertNotSame('', $this->csp((string) $response->headers), 'No se encontró cabecera CSP');
    }

    public function test_script_src_no_usa_unsafe_inline(): void
    {
        $csp = $this->csp((string) $this->get('/login')->headers);

        preg_match('/script-src ([^;]+)/', $csp, $m);
        $this->assertNotEmpty($m, 'No se encontró directive script-src');

        $this->assertStringNotContainsString("'unsafe-inline'", $m[1]);
        $this->assertStringNotContainsString("'unsafe-eval'", $m[1]);
    }

    public function test_script_src_incluye_nonce(): void
    {
        $csp = $this->csp((string) $this->get('/login')->headers);

        $this->assertMatchesRegularExpression("/script-src [^;]*'nonce-[^']+'/", $csp);
    }

    public function test_csp_bloquea_framing_y_objetos(): void
    {
        $csp = $this->csp((string) $this->get('/login')->headers);

        $this->assertStringContainsString("object-src 'none'", $csp);
        $this->assertStringContainsString("frame-ancestors 'none'", $csp);
        $this->assertStringContainsString("base-uri 'self'", $csp);
    }

    public function test_se_mantienen_las_cabeceras_basicas_de_seguridad(): void
    {
        $response = $this->get('/login');

        $this->assertSame('nosniff', $response->headers->get('X-Content-Type-Options'));
        $this->assertSame('DENY', $response->headers->get('X-Frame-Options'));
        $this->assertNotNull($response->headers->get('Referrer-Policy'));
        $this->assertNotNull($response->headers->get('Permissions-Policy'));
    }

    public function test_el_nonce_cambia_en_cada_peticion(): void
    {
        $primero = $this->get('/login')->headers->get('Content-Security-Policy');
        $segundo = $this->get('/login')->headers->get('Content-Security-Policy');

        $this->assertNotSame($primero, $segundo, 'El nonce debe ser único por petición');
    }
}