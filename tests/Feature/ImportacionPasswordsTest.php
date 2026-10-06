<?php

namespace Tests\Feature;

use App\Models\DocenteHorarioPivot;
use App\Models\User;
use App\Services\ImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ImportacionPasswordsTest extends TestCase
{
    use RefreshDatabase;

    private function seedRoles(): void
    {
        DB::table('roles')->insert([
            ['id' => 1, 'nombre' => 'Superadmin', 'descripcion' => 'Acceso total'],
            ['id' => 2, 'nombre' => 'Administrativo', 'descripcion' => 'Administracion'],
            ['id' => 3, 'nombre' => 'Docente', 'descripcion' => 'Profesores'],
        ]);
    }

    private function csvAsignaciones(string $contenido): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'import') . '.csv';
        file_put_contents($path, $contenido);

        return new UploadedFile($path, 'asignaciones.csv', 'text/csv', null, true);
    }

    public function test_importacion_no_expone_passwords_en_el_resultado(): void
    {
        $this->seedRoles();

        $archivo = $this->csvAsignaciones(
            "cedula;nombre;apellido;dia;hora_inicio;hora_fin;salon_codigo;tipo_actividad\n"
            ."12345678;Maria;Lopez;Lunes;08:00:00;08:45:00;A1;carga_academica\n"
        );

        $resultado = app(ImportService::class)->importarAsignaciones($archivo);

        $this->assertArrayHasKey('usuarios_creados', $resultado);
        $this->assertCount(1, $resultado['usuarios_creados']);

        foreach ($resultado['usuarios_creados'] as $usuario) {
            $this->assertArrayNotHasKey('password', $usuario);
        }
    }

    public function test_password_generada_cumple_politica_de_complejidad(): void
    {
        $this->seedRoles();

        $servicio = app(ImportService::class);
        $metodo = new \ReflectionMethod($servicio, 'generarPasswordSegura');
        $metodo->setAccessible(true);

        for ($i = 0; $i < 25; $i++) {
            $password = $metodo->invoke(null, 16);

            $this->assertGreaterThanOrEqual(16, strlen($password));
            $this->assertMatchesRegularExpression('/[a-z]/', $password);
            $this->assertMatchesRegularExpression('/[A-Z]/', $password);
            $this->assertMatchesRegularExpression('/\d/', $password);
            $this->assertMatchesRegularExpression('/[^a-zA-Z0-9]/', $password);
        }
    }

    public function test_passwords_generadas_son_distintas(): void
    {
        $this->seedRoles();

        $servicio = app(ImportService::class);
        $metodo = new \ReflectionMethod($servicio, 'generarPasswordSegura');
        $metodo->setAccessible(true);

        $passwords = [];
        for ($i = 0; $i < 25; $i++) {
            $passwords[] = $metodo->invoke(null, 16);
        }

        $this->assertCount(25, array_unique($passwords));
    }

    public function test_password_minimo_garantizado_es_12(): void
    {
        $this->seedRoles();

        $servicio = app(ImportService::class);
        $metodo = new \ReflectionMethod($servicio, 'generarPasswordSegura');
        $metodo->setAccessible(true);

        $password = $metodo->invoke(null, 4);

        $this->assertGreaterThanOrEqual(12, strlen($password));
    }

    public function test_usuario_importado_nace_inactivo(): void
    {
        $this->seedRoles();

        $archivo = $this->csvAsignaciones(
            "cedula;nombre;apellido;dia;hora_inicio;hora_fin;salon_codigo;tipo_actividad\n"
            ."12345678;Maria;Lopez;Lunes;08:00:00;08:45:00;A1;carga_academica\n"
        );

        app(ImportService::class)->importarAsignaciones($archivo);

        $this->assertDatabaseHas('users', [
            'cedula'  => '12345678',
            'estatus' => 'inactivo',
        ]);
    }

    public function test_password_importada_se_guarda_hasheada(): void
    {
        $this->seedRoles();

        $archivo = $this->csvAsignaciones(
            "cedula;nombre;apellido;dia;hora_inicio;hora_fin;salon_codigo;tipo_actividad\n"
            ."12345678;Maria;Lopez;Lunes;08:00:00;08:45:00;A1;carga_academica\n"
        );

        app(ImportService::class)->importarAsignaciones($archivo);

        $usuario = User::where('cedula', '12345678')->first();

        $this->assertNotNull($usuario);
        $this->assertStringStartsWith('$2y$', $usuario->password);
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check(
            $usuario->password,
            $usuario->password
        ) === false);
    }

    public function test_actividad_registrada_no_contiene_passwords(): void
    {
        $this->seedRoles();

        $archivo = $this->csvAsignaciones(
            "cedula;nombre;apellido;dia;hora_inicio;hora_fin;salon_codigo;tipo_actividad\n"
            ."12345678;Maria;Lopez;Lunes;08:00:00;08:45:00;A1;carga_academica\n"
        );

        app(ImportService::class)->importarAsignaciones($archivo);

        $logs = \App\Models\ActivityLog::all();
        $this->assertGreaterThan(0, $logs->count());

        foreach ($logs as $log) {
            $serializado = json_encode($log->datos);
            $this->assertStringNotContainsStringIgnoringCase('password', (string) $serializado);
        }
    }
}
