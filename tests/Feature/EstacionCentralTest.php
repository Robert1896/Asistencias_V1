<?php

namespace Tests\Feature;

use App\Models\EstacionCentral;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class EstacionCentralTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        DB::table("roles")->insertOrIgnore(["id" => 1, "nombre" => "Superadmin"]);
        DB::table("roles")->insertOrIgnore(["id" => 2, "nombre" => "Administrativo"]);
        DB::table("roles")->insertOrIgnore(["id" => 3, "nombre" => "Docente"]);
        DB::table('permisos')->updateOrInsert(['id'=>9], ['codigo'=>'estacion_operar','nombre'=>'Operar estacion','modulo'=>'estacion']);
        DB::table('rol_permiso')->updateOrInsert(['rol_id'=>3,'permiso_id'=>9]);
    }

    private function docente()
    {
        return User::factory()->create(["rol_id" => 3]);
    }

    public function test_modo_ninguno_allows_access()
    {
        EstacionCentral::query()->delete();
        EstacionCentral::create(["modo" => "ninguno", "activo" => false]);
        $docente = $this->docente();
        $this->actingAs($docente)
            ->get(route("estacion.index"))
            ->assertStatus(200);
    }
}