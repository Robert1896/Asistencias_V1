<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("UPDATE asistencias SET estado = 'Inasistente' WHERE estado = 'Ausente'");

        if (config('database.default') !== 'sqlite') {
            DB::statement("ALTER TABLE asistencias MODIFY estado ENUM('Presente','Tarde','Inasistente','Apoyo Sabado') DEFAULT 'Presente'");
        }
    }

    public function down(): void
    {
        DB::statement("UPDATE asistencias SET estado = 'Ausente' WHERE estado = 'Inasistente'");

        if (config('database.default') !== 'sqlite') {
            DB::statement("ALTER TABLE asistencias MODIFY estado ENUM('Presente','Tarde','Ausente','Apoyo Sabado') DEFAULT 'Presente'");
        }
    }
};
