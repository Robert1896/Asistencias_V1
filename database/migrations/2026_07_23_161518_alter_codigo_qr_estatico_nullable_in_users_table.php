<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') return;

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_codigo_qr_estatico_unique');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->char('codigo_qr_estatico', 36)->nullable()->unique()->change();
        });

        DB::table('users')->where('rol_id', 3)->update(['codigo_qr_estatico' => null]);
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') return;

        DB::table('users')->where('rol_id', 3)->update(['codigo_qr_estatico' => '00000000-0000-0000-0000-000000000000']);

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_codigo_qr_estatico_unique');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->char('codigo_qr_estatico', 36)->unique()->change();
        });
    }
};
