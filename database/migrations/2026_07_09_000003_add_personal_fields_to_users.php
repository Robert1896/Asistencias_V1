<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('nombre', 80)->after('password');
            $table->string('apellido', 80)->after('nombre');
            $table->string('cedula', 20)->nullable()->unique()->after('apellido');
        });

        // Poblar nombre y apellido desde nombre_completo
        if (config('database.default') !== 'sqlite') {
            DB::statement("UPDATE users SET nombre = SUBSTRING_INDEX(nombre_completo, ' ', 1), apellido = TRIM(SUBSTR(nombre_completo, LOCATE(' ', nombre_completo))) WHERE nombre IS NULL");
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['nombre', 'apellido', 'cedula']);
        });
    }
};
