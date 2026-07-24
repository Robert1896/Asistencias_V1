<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('asistencias', function (Blueprint $table) {
            if (!Schema::hasColumn('asistencias', 'departamento_id')) {
                $table->foreignId('departamento_id')->nullable()->after('salon_id');
            }
            if (!Schema::hasColumn('asistencias', 'departamento_id_foreign')) {
                $table->foreign('departamento_id')->references('id')->on('departamentos')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('asistencias', function (Blueprint $table) {
            $table->dropForeign(['departamento_id']);
            $table->dropColumn('departamento_id');
        });
    }
};
