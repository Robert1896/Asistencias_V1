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

        // Asignar mismos permisos que Asistente (9, 12, 13, 11, 14, 7)
        $permisos = [9, 12, 13, 11, 14, 7];
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
