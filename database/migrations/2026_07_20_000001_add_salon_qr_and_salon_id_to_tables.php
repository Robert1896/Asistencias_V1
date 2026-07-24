<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('salones', function (Blueprint $table) {
            $table->char('codigo_qr', 36)->unique()->after('codigo');
        });

        DB::table('salones')->whereNull('codigo_qr')->orderBy('id')->each(function ($salon) {
            DB::table('salones')->where('id', $salon->id)
                ->update(['codigo_qr' => Str::uuid()->toString()]);
        });

        Schema::table('asistencias', function (Blueprint $table) {
            $table->foreignId('salon_id')->nullable()
                  ->constrained('salones')
                  ->nullOnDelete()
                  ->after('horario_id');
        });
    }

    public function down(): void
    {
        Schema::table('asistencias', function (Blueprint $table) {
            $table->dropForeign(['salon_id']);
            $table->dropColumn('salon_id');
        });

        Schema::table('salones', function (Blueprint $table) {
            $table->dropColumn('codigo_qr');
        });
    }
};
