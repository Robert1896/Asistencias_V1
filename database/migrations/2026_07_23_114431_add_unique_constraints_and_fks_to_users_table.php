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

        $fkExists = DB::select("SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'asistencias' AND CONSTRAINT_NAME = 'asistencias_horario_id_foreign' AND CONSTRAINT_TYPE = 'FOREIGN KEY'");

        if (empty($fkExists)) {
            Schema::table('asistencias', function (Blueprint $table) {
                $table->foreign('horario_id')->references('id')->on('horarios')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') return;

        Schema::table('asistencias', function (Blueprint $table) {
            $table->dropForeign(['horario_id']);
        });
    }
};
