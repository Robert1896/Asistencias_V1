<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('correo_config', function (Blueprint $table) {
            $table->id();
            $table->string('host', 120)->default('smtp.gmail.com');
            $table->integer('puerto')->default(587);
            $table->string('encriptacion', 10)->default('tls');
            $table->string('usuario', 120)->nullable();
            $table->string('contrasena', 255)->nullable();
            $table->string('direccion_from', 120)->nullable();
            $table->string('nombre_from', 100)->default('Sistema de Asistencia');
            $table->boolean('activo')->default(false);
            $table->timestamps();
        });

        DB::table('correo_config')->insert([
            'host'           => 'smtp.gmail.com',
            'puerto'         => 587,
            'encriptacion'   => 'tls',
            'nombre_from'    => 'Sistema de Asistencia',
            'activo'         => false,
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('correo_config');
    }
};
