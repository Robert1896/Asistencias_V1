<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('rol_permiso')
            ->where('rol_id', 3)
            ->where('permiso_id', 9)
            ->delete();
    }

    public function down(): void
    {
        DB::table('rol_permiso')->insertOrIgnore([
            'rol_id' => 3,
            'permiso_id' => 9,
        ]);
    }
};
