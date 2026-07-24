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

        DB::statement('ALTER TABLE `asistencias` ADD INDEX `idx_asistencias_fecha` (`fecha`)');
        DB::statement('ALTER TABLE `asistencias` ADD INDEX `idx_asistencias_usuario_fecha` (`usuario_id`, `fecha`)');
        DB::statement('ALTER TABLE `docente_horario` ADD INDEX `idx_docente_horario_user` (`user_id`)');
        DB::statement('ALTER TABLE `docente_horario` ADD INDEX `idx_docente_horario_salon` (`salon_id`)');
        DB::statement('ALTER TABLE `docente_horario` ADD INDEX `idx_docente_horario_horario` (`horario_id`)');
        DB::statement('ALTER TABLE `users` ADD INDEX `idx_users_cedula` (`cedula`)');
        DB::statement('ALTER TABLE `users` ADD INDEX `idx_users_estatus` (`estatus`)');
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement('ALTER TABLE `asistencias` DROP INDEX `idx_asistencias_fecha`');
        DB::statement('ALTER TABLE `asistencias` DROP INDEX `idx_asistencias_usuario_fecha`');
        DB::statement('ALTER TABLE `docente_horario` DROP INDEX `idx_docente_horario_user`');
        DB::statement('ALTER TABLE `docente_horario` DROP INDEX `idx_docente_horario_salon`');
        DB::statement('ALTER TABLE `docente_horario` DROP INDEX `idx_docente_horario_horario`');
        DB::statement('ALTER TABLE `users` DROP INDEX `idx_users_cedula`');
        DB::statement('ALTER TABLE `users` DROP INDEX `idx_users_estatus`');
    }
};
