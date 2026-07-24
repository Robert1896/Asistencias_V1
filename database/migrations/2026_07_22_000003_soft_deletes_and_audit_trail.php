<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('asistencias', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('horarios', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::create('auditoria_asistencias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asistencia_id')->constrained()->cascadeOnDelete();
            $table->foreignId('modificado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->string('accion', 50);
            $table->json('antes')->nullable();
            $table->json('despues')->nullable();
            $table->text('motivo')->nullable();
            $table->timestamps();

            $table->index('asistencia_id');
            $table->index('modificado_por');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auditoria_asistencias');

        Schema::table('horarios', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('asistencias', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
