<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('roles')) {
            DB::table('roles')->insertOrIgnore([
                'id' => 5,
                'nombre' => 'Técnico',
                'descripcion' => 'Soporte técnico con funciones operativas',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if (Schema::hasTable('rol_permiso') && Schema::hasTable('permisos')) {
            // Solo enlazar permisos que existan: en una instalación limpia la
            // tabla está vacía en este punto y el seeder los crea después.
            $codigos = [
                'horarios_ver',
                'estacion_operar',
                'asistencias_justificar',
                'asistencias_ver',
                'asistencias_registrar',
                'salones_ver',
            ];
            $permisos = DB::table('permisos')->whereIn('codigo', $codigos)->pluck('id');

            foreach ($permisos as $permisoId) {
                DB::table('rol_permiso')->insertOrIgnore([
                    'rol_id' => 5,
                    'permiso_id' => $permisoId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('rol_permiso')) {
            DB::table('rol_permiso')->where('rol_id', 5)->delete();
        }

        if (Schema::hasTable('roles')) {
            DB::table('roles')->where('id', 5)->where('nombre', 'Técnico')->delete();
        }
    }
};
