<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->tinyIncrements('id');
            $table->string('nombre', 50)->unique();
            $table->string('descripcion', 255)->nullable();
            $table->timestamps();
        });

        Schema::create('permisos', function (Blueprint $table) {
            $table->smallIncrements('id');
            $table->string('codigo', 60)->unique()->comment('Identificador único: usuarios_crear, etc.');
            $table->string('nombre', 100);
            $table->string('modulo', 60)->comment('Agrupación: usuarios, roles, horarios, etc.');
            $table->timestamps();
        });

        Schema::create('rol_permiso', function (Blueprint $table) {
            $table->tinyInteger('rol_id')->unsigned();
            $table->smallInteger('permiso_id')->unsigned();
            $table->timestamps();
            $table->primary(['rol_id', 'permiso_id']);
            $table->foreign('rol_id')->references('id')->on('roles')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreign('permiso_id')->references('id')->on('permisos')->cascadeOnDelete()->cascadeOnUpdate();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('password');
            $table->string('nombre_completo', 150);
            $table->string('email', 120)->unique();
            $table->tinyInteger('rol_id')->unsigned();
            $table->enum('estatus', ['activo', 'inactivo'])->default('activo');
            $table->char('codigo_qr_estatico', 36)->unique()->comment('UUID v4 estático como carnet digital');
            $table->rememberToken();
            $table->timestamps();

            $table->foreign('rol_id')->references('id')->on('roles')->restrictOnDelete()->cascadeOnUpdate();
            $table->index('estatus');
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });

        Schema::create('horarios_administrativos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->constrained('users')->cascadeOnDelete()->cascadeOnUpdate();
            $table->enum('turno', ['Matutino', 'Vespertino']);
            $table->time('hora_entrada');
            $table->time('hora_salida');
            $table->boolean('lunes')->default(true);
            $table->boolean('martes')->default(true);
            $table->boolean('miercoles')->default(true);
            $table->boolean('jueves')->default(true);
            $table->boolean('viernes')->default(true);
            $table->boolean('sabado')->default(false);
            $table->timestamps();
            $table->unique('usuario_id');
        });

        Schema::create('carga_academica_docente', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->constrained('users')->cascadeOnDelete()->cascadeOnUpdate();
            $table->string('materia', 120);
            $table->enum('tipo', ['Clase', 'Coordinacion', 'Laboratorio', 'Apoyo'])->default('Clase');
            $table->decimal('horas_semanales', 4, 1)->default(0);
            $table->timestamps();
            $table->index('tipo');
        });

        Schema::create('asistencias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->constrained('users')->cascadeOnDelete()->cascadeOnUpdate();
            $table->date('fecha');
            $table->time('hora_entrada')->nullable();
            $table->time('hora_salida')->nullable();
            $table->enum('estado', ['Presente', 'Tarde', 'Ausente', 'Apoyo Sabado'])->default('Presente');
            $table->enum('justificado', ['Si', 'No'])->default('No');
            $table->text('texto_justificacion')->nullable();
            $table->timestamps();
            $table->unique(['usuario_id', 'fecha']);
            $table->index('fecha');
            $table->index('estado');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asistencias');
        Schema::dropIfExists('carga_academica_docente');
        Schema::dropIfExists('horarios_administrativos');
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
        Schema::dropIfExists('rol_permiso');
        Schema::dropIfExists('permisos');
        Schema::dropIfExists('roles');
    }
};
