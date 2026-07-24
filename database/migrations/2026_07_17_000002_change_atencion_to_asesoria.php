<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (config('database.default') !== 'sqlite') {
            DB::statement("ALTER TABLE docente_horario MODIFY tipo_actividad ENUM('carga_academica','atencion_estudiante','asesoria','administrativa') DEFAULT 'carga_academica'");
        }
        DB::statement("UPDATE docente_horario SET tipo_actividad = 'asesoria' WHERE tipo_actividad = 'atencion_estudiante'");
        if (config('database.default') !== 'sqlite') {
            DB::statement("ALTER TABLE docente_horario MODIFY tipo_actividad ENUM('carga_academica','asesoria','administrativa') DEFAULT 'carga_academica'");
        }
    }

    public function down(): void
    {
        if (config('database.default') !== 'sqlite') {
            DB::statement("ALTER TABLE docente_horario MODIFY tipo_actividad ENUM('carga_academica','atencion_estudiante','asesoria','administrativa') DEFAULT 'carga_academica'");
        }
        DB::statement("UPDATE docente_horario SET tipo_actividad = 'atencion_estudiante' WHERE tipo_actividad = 'asesoria'");
        if (config('database.default') !== 'sqlite') {
            DB::statement("ALTER TABLE docente_horario MODIFY tipo_actividad ENUM('carga_academica','atencion_estudiante','administrativa') DEFAULT 'carga_academica'");
        }
    }
};
