<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('asistencias', function (Blueprint $table) {
            $table->unsignedBigInteger('horario_id')->nullable()->after('fecha');
            $table->time('bloque_inicio')->nullable()->after('horario_id');
            $table->time('bloque_fin')->nullable()->after('bloque_inicio');
        });
    }

    public function down(): void
    {
        Schema::table('asistencias', function (Blueprint $table) {
            $table->dropColumn(['horario_id', 'bloque_inicio', 'bloque_fin']);
        });
    }
};
