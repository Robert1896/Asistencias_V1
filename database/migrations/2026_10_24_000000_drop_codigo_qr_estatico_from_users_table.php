<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Los usuarios solo usan QR dinámico; el QR estático queda reservado
        // exclusivamente para los salones (salones.codigo_qr).
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['codigo_qr_estatico']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('codigo_qr_estatico');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->char('codigo_qr_estatico', 36)->nullable()->unique();
        });
    }
};
