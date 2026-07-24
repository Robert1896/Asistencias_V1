<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::table('salones')
            ->where('nombre', 'Actividades Ad-Hoc')
            ->update(['nombre' => 'Asistencia Especial']);

        DB::table('departamentos')
            ->where('codigo', '!=', 'INFO')
            ->delete();
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::table('salones')
            ->where('nombre', 'Asistencia Especial')
            ->update(['nombre' => 'Actividades Ad-Hoc']);

        DB::table('departamentos')->insertOrIgnore([
            ['nombre' => 'Departamento de Contaduría',    'codigo' => 'CONT'],
            ['nombre' => 'Departamento de Derecho',        'codigo' => 'DERE'],
            ['nombre' => 'Departamento de Administración', 'codigo' => 'ADM'],
            ['nombre' => 'Departamento de Educación',      'codigo' => 'EDUC'],
            ['nombre' => 'Actividades Ad-Hoc',             'codigo' => 'ADHOC'],
        ]);
    }
};
