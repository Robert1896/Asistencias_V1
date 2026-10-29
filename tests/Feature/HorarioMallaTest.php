<?php

namespace Tests\Feature;

use App\Models\DocenteHorarioPivot;
use App\Models\Horario;
use App\Models\User;
use App\Services\ImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class HorarioMallaTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        DB::table('roles')->insertOrIgnore([
            ['id' => 1, 'nombre' => 'Superadmin', 'descripcion' => 'Acceso total'],
            ['id' => 3, 'nombre' => 'Docente', 'descripcion' => 'Profesores'],
        ]);

        return User::factory()->admin()->create(['estatus' => 'activo']);
    }

    public function test_la_migracion_deja_la_malla_completa_en_los_seis_dias(): void
    {
        $rangos = Horario::rangosObjetivo();

        $this->assertSame(count(Horario::DIAS) * count($rangos), Horario::count());

        // El hueco de 12:30–13:05 ahora es un bloque asignable en todos los días.
        $this->assertSame(6, Horario::where('hora_inicio', '12:30:00')
            ->where('hora_fin', '13:05:00')
            ->count());
    }

    public function test_sincronizar_malla_es_idempotente(): void
    {
        $antes = Horario::count();

        $this->assertSame(0, Horario::sincronizarMalla());
        $this->assertSame($antes, Horario::count());
    }

    public function test_sincronizar_restaura_un_bloque_borrado(): void
    {
        Horario::where('dia_semana', 3)
            ->where('hora_inicio', '12:30:00')
            ->where('hora_fin', '13:05:00')
            ->delete(); // soft delete

        $this->assertFalse(Horario::where('dia_semana', 3)
            ->where('hora_inicio', '12:30:00')
            ->exists());

        $this->assertGreaterThanOrEqual(1, Horario::sincronizarMalla());

        $this->assertTrue(Horario::where('dia_semana', 3)
            ->where('hora_inicio', '12:30:00')
            ->where('hora_fin', '13:05:00')
            ->exists());
    }

    public function test_un_rango_personalizado_se_replica_a_los_seis_dias(): void
    {
        Horario::create([
            'dia_semana'  => 1,
            'hora_inicio' => '18:00:00',
            'hora_fin'    => '18:45:00',
            'nombre'      => 'Lunes 18:00–18:45',
        ]);

        $this->assertSame(1, Horario::where('hora_inicio', '18:00:00')->count());

        Horario::sincronizarMalla();

        $this->assertSame(6, Horario::where('hora_inicio', '18:00:00')
            ->where('hora_fin', '18:45:00')
            ->count());
    }

    public function test_el_grid_del_docente_ofrece_el_bloque_de_1230_en_todos_los_dias(): void
    {
        $admin   = $this->admin();
        $docente = User::factory()->create(['rol_id' => 3, 'estatus' => 'activo']);

        $response = $this->actingAs($admin)->get(route('admin.docentes.horarios.edit', $docente));

        $response->assertOk();

        $ids = Horario::where('hora_inicio', '12:30:00')
            ->where('hora_fin', '13:05:00')
            ->pluck('id');

        $this->assertCount(6, $ids);

        foreach ($ids as $id) {
            $response->assertSee('id="chk-' . $id . '"', false);
        }

        // Ninguna celda del grid queda sin bloque.
        $response->assertDontSee('Sin bloque', false);
    }

    public function test_se_puede_asignar_el_bloque_de_1230_a_un_docente(): void
    {
        $admin   = $this->admin();
        $docente = User::factory()->create(['rol_id' => 3, 'estatus' => 'activo']);

        $bloque = Horario::where('dia_semana', 2)
            ->where('hora_inicio', '12:30:00')
            ->where('hora_fin', '13:05:00')
            ->firstOrFail();

        $payload = [
            'bloques' => [
                $bloque->id . '-carga_academica' => [
                    'horario_id' => $bloque->id,
                    'tipo'       => 'carga_academica',
                ],
            ],
        ];

        $this->actingAs($admin)
            ->put(route('admin.docentes.horarios.update', $docente), $payload)
            ->assertRedirect(route('admin.docentes.horarios'));

        $this->assertDatabaseHas('docente_horario', [
            'user_id'        => $docente->id,
            'horario_id'     => $bloque->id,
            'tipo_actividad' => 'carga_academica',
        ]);
    }

    public function test_la_importacion_replica_un_rango_nuevo_a_los_seis_dias(): void
    {
        $this->admin(); // roles
        User::factory()->create([
            'rol_id'  => 3,
            'cedula'  => '12345678',
            'estatus' => 'activo',
        ]);

        $path = tempnam(sys_get_temp_dir(), 'import') . '.csv';
        file_put_contents(
            $path,
            "cedula;nombre;apellido;dia;hora_inicio;hora_fin;salon_codigo;tipo_actividad\n"
            . "12345678;Maria;Lopez;Lunes;18:00:00;18:45:00;A1;asesoria\n"
        );
        $archivo = new UploadedFile($path, 'asignaciones.csv', 'text/csv', null, true);

        $resultado = app(ImportService::class)->importarAsignaciones($archivo);

        $this->assertSame(1, $resultado['created']);

        // El rango nuevo queda disponible en los seis días para asignarlo.
        $this->assertSame(6, Horario::where('hora_inicio', '18:00:00')
            ->where('hora_fin', '18:45:00')
            ->count());
    }

    public function test_las_vistas_de_horario_renderizan_con_la_malla_completa(): void
    {
        $admin   = $this->admin();
        $docente = User::factory()->create(['rol_id' => 3, 'estatus' => 'activo']);

        $bloque = Horario::where('dia_semana', 1)
            ->where('hora_inicio', '12:30:00')
            ->where('hora_fin', '13:05:00')
            ->firstOrFail();

        DocenteHorarioPivot::create([
            'user_id'        => $docente->id,
            'horario_id'     => $bloque->id,
            'tipo_actividad' => 'carga_academica',
        ]);

        $this->actingAs($docente)
            ->get(route('empleado.horario'))
            ->assertOk()
            ->assertSee('12:30');

        $this->actingAs($admin)
            ->get(route('admin.salones.ocupacion'))
            ->assertOk();
    }
}
