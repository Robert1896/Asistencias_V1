<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->limpiarHorariosHuerfanos();
        $this->limpiarRegistrosDuplicados();

        if (DB::getDriverName() === 'mysql') {
            $this->eliminarIndicesRedundantes();
            $this->agregarIndiceUnicoPorBloque();
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        Schema::table('asistencias', function (Blueprint $table) {
            $indexExists = DB::selectOne(
                "SELECT INDEX_NAME FROM information_schema.STATISTICS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'asistencias'
                 AND INDEX_NAME = 'unique_asistencia_bloque'"
            );

            if ($indexExists) {
                $table->dropUnique('unique_asistencia_bloque');
            }
        });
    }

    /**
     * Las filas con horario_id inexistente quedaron de una migracion previa que
     * todavia no aplicaba la FK. Se anulan para no arrastrar referencias invalidas.
     */
    private function limpiarHorariosHuerfanos(): void
    {
        DB::statement(
            'UPDATE asistencias
             SET horario_id = NULL
             WHERE horario_id IS NOT NULL
               AND deleted_at IS NULL
               AND horario_id NOT IN (SELECT id FROM horarios)'
        );
    }

    /**
     * Conserva solo el registro mas antiguo por (usuario, fecha, horario) y
     * descarta el resto, que fueron creados antes del control de duplicados.
     */
    private function limpiarRegistrosDuplicados(): void
    {
        $duplicados = DB::select(
            'SELECT a.id
             FROM asistencias a
             INNER JOIN (
                 SELECT usuario_id, fecha, horario_id, MIN(id) AS conservar
                 FROM asistencias
                 WHERE deleted_at IS NULL AND horario_id IS NOT NULL
                 GROUP BY usuario_id, fecha, horario_id
                 HAVING COUNT(*) > 1
             ) d
                ON d.usuario_id = a.usuario_id
               AND d.fecha = a.fecha
               AND d.horario_id = a.horario_id
             WHERE a.id <> d.conservar
               AND a.deleted_at IS NULL'
        );

        foreach ($duplicados as $duplicado) {
            DB::table('asistencias')->where('id', $duplicado->id)->delete();
        }
    }

    /**
     * La tabla arrastra dos indices sobre la misma columna fecha
     * (asistencias_fecha_index e idx_asistencias_fecha). Se conserva el
     * nombre generado por Laravel y se elimina el redundante.
     */
    private function eliminarIndicesRedundantes(): void
    {
        $redundante = DB::selectOne(
            "SELECT INDEX_NAME FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'asistencias'
             AND INDEX_NAME = 'idx_asistencias_fecha'"
        );

        if ($redundante) {
            DB::statement('ALTER TABLE asistencias DROP INDEX idx_asistencias_fecha');
        }
    }

    /**
     * Un docente no puede tener dos asistencias para el mismo bloque horario
     * el mismo dia. Las filas ad-hoc (horario_id NULL) quedan fuera del indice
     * porque en MySQL un indice unico admite multiples NULL.
     */
    private function agregarIndiceUnicoPorBloque(): void
    {
        DB::statement(
            'ALTER TABLE asistencias
             ADD UNIQUE INDEX unique_asistencia_bloque (usuario_id, fecha, horario_id)'
        );
    }
};
