<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('roles')) {
            DB::statement("INSERT IGNORE INTO roles (id, nombre, descripcion, created_at, updated_at) VALUES (5, 'Técnico', 'Soporte técnico con funciones operativas', NOW(), NOW())");
        }

        if (Schema::hasTable('rol_permiso') && Schema::hasTable('permisos')) {
            $permisos = [7, 9, 11, 12, 13, 14];
            foreach ($permisos as $permisoId) {
                DB::statement("INSERT IGNORE INTO rol_permiso (rol_id, permiso_id, created_at, updated_at) VALUES (5, {$permisoId}, NOW(), NOW())");
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
