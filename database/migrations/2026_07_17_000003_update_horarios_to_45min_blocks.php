<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (config('database.default') !== 'sqlite') {
            DB::statement('SET FOREIGN_KEY_CHECKS = 0');
        }
        DB::table('docente_horario')->delete();
        DB::table('horarios')->delete();
        if (config('database.default') !== 'sqlite') {
            DB::statement('SET FOREIGN_KEY_CHECKS = 1');
        }

        $dias = [
            1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles',
            4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado',
        ];

        $bloques = [
            ['07:00', '07:45'],
            ['07:45', '08:30'],
            ['08:35', '09:20'],
            ['09:20', '10:05'],
            ['10:10', '10:55'],
            ['10:55', '11:40'],
            ['11:45', '12:30'],
            ['13:05', '13:50'],
            ['13:50', '14:35'],
            ['14:40', '15:25'],
            ['15:25', '16:10'],
            ['16:15', '17:00'],
            ['17:00', '17:45'],
        ];

        $rows = [];
        foreach ($dias as $num => $nom) {
            foreach ($bloques as [$inicio, $fin]) {
                $rows[] = [
                    'dia_semana'  => $num,
                    'hora_inicio' => $inicio,
                    'hora_fin'    => $fin,
                    'nombre'      => "{$nom} {$inicio}–{$fin}",
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ];
            }
        }

        DB::table('horarios')->insert($rows);
    }

    public function down(): void
    {
        if (config('database.default') !== 'sqlite') {
            DB::statement('SET FOREIGN_KEY_CHECKS = 0');
        }
        DB::table('docente_horario')->delete();
        DB::table('horarios')->delete();
        if (config('database.default') !== 'sqlite') {
            DB::statement('SET FOREIGN_KEY_CHECKS = 1');
        }

        $dias = [
            1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles',
            4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado',
        ];

        $rangos = [
            ['07:00', '08:00'], ['08:00', '09:00'], ['09:00', '10:00'],
            ['10:00', '11:00'], ['11:00', '12:00'], ['12:00', '13:00'],
            ['13:00', '14:00'], ['14:00', '15:00'], ['15:00', '16:00'],
            ['16:00', '17:00'], ['17:00', '18:00'],
        ];

        $rows = [];
        foreach ($dias as $num => $nom) {
            foreach ($rangos as [$inicio, $fin]) {
                $rows[] = [
                    'dia_semana'  => $num,
                    'hora_inicio' => $inicio,
                    'hora_fin'    => $fin,
                    'nombre'      => "{$nom} {$inicio}–{$fin}",
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ];
            }
        }

        DB::table('horarios')->insert($rows);
    }
};
