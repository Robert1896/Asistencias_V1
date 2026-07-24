<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ─── Coordinaciones ────────────────────────────────
        Schema::create('coordinaciones', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 120);
            $table->string('descripcion', 255)->nullable();
            $table->timestamps();
        });

        // ─── Bloques horarios reutilizables ───────────────
        Schema::create('horarios', function (Blueprint $table) {
            $table->id();
            $table->tinyInteger('dia_semana')->unsigned()->comment('1=Lun…6=Sáb');
            $table->time('hora_inicio');
            $table->time('hora_fin');
            $table->string('nombre', 100);
            $table->timestamps();

            $table->index('dia_semana');
        });

        // ─── Tabla pivote: docente ↔ horario ──────────────
        Schema::create('docente_horario', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('horario_id')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->enum('tipo_actividad', [
                'carga_academica',
                'atencion_estudiante',
                'administrativa',
            ])->default('carga_academica');
            $table->foreignId('coordinacion_id')->nullable()->constrained('coordinaciones')
                  ->nullOnDelete()->cascadeOnUpdate();
            $table->timestamps();

            $table->unique(['user_id', 'horario_id', 'tipo_actividad']);
            $table->index('user_id');
            $table->index('horario_id');
        });

        // ─── Seed: bloques horarios estándar ──────────────
        $bloques = [];
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

        foreach ($dias as $num => $nom) {
            foreach ($rangos as $i => $r) {
                $bloques[] = [
                    'dia_semana'  => $num,
                    'hora_inicio' => $r[0],
                    'hora_fin'    => $r[1],
                    'nombre'      => "{$nom} {$r[0]}–{$r[1]}",
                ];
            }
        }

        DB::table('horarios')->insert($bloques);

        // ─── Seed: coordinaciones por defecto ─────────────
        DB::table('coordinaciones')->insert([
            ['nombre' => 'Coordinación Académica',   'descripcion' => 'Horas administrativas en oficina académica'],
            ['nombre' => 'Coordinación de Carrera',  'descripcion' => 'Gestión y seguimiento de carrera'],
            ['nombre' => 'Coordinación de Extensión', 'descripcion' => 'Actividades de extensión universitaria'],
            ['nombre' => 'Coordinación de Investigación', 'descripcion' => 'Labores de investigación'],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('docente_horario');
        Schema::dropIfExists('horarios');
        Schema::dropIfExists('coordinaciones');
    }
};
