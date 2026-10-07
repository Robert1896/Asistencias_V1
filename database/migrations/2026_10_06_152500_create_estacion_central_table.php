<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('estacion_central', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 120)->default('Estación Central');
            $table->string('modo', 10)->default('ninguno');
            $table->decimal('latitud', 10, 7)->nullable();
            $table->decimal('longitud', 10, 7)->nullable();
            $table->unsignedInteger('radio_metros')->default(150);
            $table->string('ip_permitida', 100)->nullable();
            $table->boolean('activo')->default(false);
            $table->foreignId('registrado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        DB::table('estacion_central')->insert([
            'nombre'       => 'Estación Central',
            'modo'         => 'ninguno',
            'radio_metros' => 150,
            'activo'       => false,
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        $permiso = DB::table('permisos')->where('codigo', 'estacion_configurar')->first();
        $permisoId = $permiso ? $permiso->id : 22;
        DB::table('permisos')->updateOrInsert(
            ['id' => $permisoId],
            [
                'codigo' => 'estacion_configurar',
                'nombre' => 'Configurar estación central',
                'modulo' => 'estacion',
            ]
        );

        if (DB::table('roles')->where('id', 1)->exists() && DB::table('permisos')->where('id', $permisoId)->exists()) {
            DB::table('rol_permiso')->insertOrIgnore([
                'rol_id'     => 1,
                'permiso_id' => $permisoId,
            ]);
        }
    }

    public function down(): void
    {
        DB::table('rol_permiso')->where('rol_id', 1)->where('permiso_id', 22)->delete();
        DB::table('permisos')->where('id', 22)->delete();
        Schema::dropIfExists('estacion_central');
    }
};
