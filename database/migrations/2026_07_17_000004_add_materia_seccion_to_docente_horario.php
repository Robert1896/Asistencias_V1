<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('docente_horario', function (Blueprint $table) {
            $table->foreignId('carga_academica_id')->nullable()
                  ->constrained('carga_academica_docente')
                  ->nullOnDelete()
                  ->after('tipo_actividad');
            $table->string('seccion', 30)->nullable()->after('carga_academica_id');
        });
    }

    public function down(): void
    {
        Schema::table('docente_horario', function (Blueprint $table) {
            $table->dropForeign(['carga_academica_id']);
            $table->dropColumn(['carga_academica_id', 'seccion']);
        });
    }
};
