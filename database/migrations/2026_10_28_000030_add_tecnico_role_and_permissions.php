<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Agregar rol Técnico si no existe
        DB::table('roles')->insertOrIgnore([
            'id' => 5,
            'nombre' => 'Técnico',
            'descripcion' => 'Soporte técnico con funciones operativas',
        ]);

        // Asignar los mismos permisos que Asistente, solo si ya existen.
        // (En una instalación limpia los permisos se crean en el seeder, así
        // que aquí no hay nada que enlazar todavía.)
        $codigos = [
            'estacion_operar',
            'asistencias_ver',
            'asistencias_registrar',
            'asistencias_justificar',
            'salones_ver',
            'horarios_ver',
        ];
        $permisos = DB::table('permisos')->whereIn('codigo', $codigos)->pluck('id');

        foreach ($permisos as $permisoId) {
            DB::table('rol_permiso')->insertOrIgnore([
                'rol_id' => 5,
                'permiso_id' => $permisoId,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('rol_permiso')->where('rol_id', 5)->delete();
        DB::table('roles')->where('id', 5)->where('nombre', 'Técnico')->delete();
    }
};
