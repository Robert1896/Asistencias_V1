<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('docente_horario')
            ->whereNull('user_id')
            ->orWhere('user_id', 0)
            ->delete();
    }

    public function down(): void
    {
        // Cleanup irreversible
    }
};
