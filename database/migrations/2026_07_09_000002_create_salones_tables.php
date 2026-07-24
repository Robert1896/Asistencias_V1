<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ─── Salones ─────────────────────────────────────
        Schema::create('salones', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 80)->unique();
            $table->string('codigo', 30)->unique()->comment('Código corto: A-101, LAB-2, etc.');
            $table->integer('capacidad')->unsigned()->default(30);
            $table->string('ubicacion', 150)->nullable();
            $table->text('descripcion')->nullable();
            $table->timestamps();
        });

        // ─── Agregar salon_id a docente_horario ──────────
        Schema::table('docente_horario', function (Blueprint $table) {
            $table->foreignId('salon_id')->nullable()
                  ->constrained('salones')
                  ->nullOnDelete()
                  ->cascadeOnUpdate()
                  ->after('coordinacion_id');
        });
    }

    public function down(): void
    {
        Schema::table('docente_horario', function (Blueprint $table) {
            $table->dropForeign(['salon_id']);
            $table->dropColumn('salon_id');
        });
        Schema::dropIfExists('salones');
    }
};
