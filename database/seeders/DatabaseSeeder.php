<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ─── Roles ────────────────────────────────────────
        DB::table('roles')->insertOrIgnore([
            ['id' => 1, 'nombre' => 'Superadmin',     'descripcion' => 'Acceso total al sistema'],
            ['id' => 2, 'nombre' => 'Administrativo', 'descripcion' => 'Personal de oficina con turno fijo'],
            ['id' => 3, 'nombre' => 'Docente',        'descripcion' => 'Profesores con carga académica variable'],
            ['id' => 4, 'nombre' => 'Asistente',      'descripcion' => 'Operador de la estación de chequeo'],
            ['id' => 5, 'nombre' => 'Técnico',        'descripcion' => 'Soporte técnico con funciones operativas'],
        ]);

        // ─── Permisos ─────────────────────────────────────
        DB::table('permisos')->insertOrIgnore([
            ['id' => 1,  'codigo' => 'usuarios_ver',        'nombre' => 'Ver listado de usuarios',        'modulo' => 'usuarios'],
            ['id' => 2,  'codigo' => 'usuarios_crear',      'nombre' => 'Crear nuevos usuarios',          'modulo' => 'usuarios'],
            ['id' => 3,  'codigo' => 'usuarios_editar',     'nombre' => 'Editar usuarios existentes',     'modulo' => 'usuarios'],
            ['id' => 4,  'codigo' => 'usuarios_eliminar',   'nombre' => 'Eliminar usuarios',              'modulo' => 'usuarios'],
            ['id' => 5,  'codigo' => 'roles_ver',           'nombre' => 'Ver roles',                      'modulo' => 'roles'],
            ['id' => 6,  'codigo' => 'roles_editar',        'nombre' => 'Asignar/Quitar permisos',        'modulo' => 'roles'],
            ['id' => 7,  'codigo' => 'horarios_ver',        'nombre' => 'Ver horarios',                   'modulo' => 'horarios'],
            ['id' => 8,  'codigo' => 'horarios_editar',     'nombre' => 'Cargar/Editar horarios',         'modulo' => 'horarios'],
            ['id' => 9,  'codigo' => 'estacion_operar',     'nombre' => 'Operar estación de chequeo',     'modulo' => 'estacion'],
            ['id' => 10, 'codigo' => 'reportes_ver',        'nombre' => 'Ver reportes de productividad',  'modulo' => 'reportes'],
            ['id' => 11, 'codigo' => 'asistencias_justificar', 'nombre' => 'Justificar inasistencias',   'modulo' => 'asistencias'],
            ['id' => 12, 'codigo' => 'asistencias_ver',     'nombre' => 'Ver registros de asistencia',    'modulo' => 'asistencias'],
            ['id' => 13, 'codigo' => 'asistencias_registrar', 'nombre' => 'Registrar asistencia manual', 'modulo' => 'asistencias'],
            ['id' => 14, 'codigo' => 'salones_ver',         'nombre' => 'Ver ocupación de salones',       'modulo' => 'salones'],
            ['id' => 15, 'codigo' => 'salones_crear',       'nombre' => 'Crear salones',                  'modulo' => 'salones'],
            ['id' => 16, 'codigo' => 'salones_editar',      'nombre' => 'Editar salones',                 'modulo' => 'salones'],
            ['id' => 17, 'codigo' => 'salones_eliminar',    'nombre' => 'Eliminar salones',               'modulo' => 'salones'],
            ['id' => 18, 'codigo' => 'bitacora_ver',        'nombre' => 'Ver bitácora',                   'modulo' => 'bitacora'],
            ['id' => 19, 'codigo' => 'correo_editar',       'nombre' => 'Configurar correo SMTP',         'modulo' => 'correo'],
            ['id' => 20, 'codigo' => 'feriados_editar',     'nombre' => 'Gestionar días feriados',        'modulo' => 'feriados'],
            ['id' => 21, 'codigo' => 'materias_editar',     'nombre' => 'Gestionar materias',             'modulo' => 'materias'],
            ['id' => 22, 'codigo' => 'estacion_configurar', 'nombre' => 'Configurar estación central',    'modulo' => 'estacion'],
        ]);

        // ─── Rol_Permiso ───────────────────────────────
        // Superadmin: TODOS los permisos (incluye los nuevos de la Fase B)
        foreach (DB::table('permisos')->pluck('id') as $permisoId) {
            DB::table('rol_permiso')->insertOrIgnore([
                'rol_id' => 1, 'permiso_id' => $permisoId,
            ]);
        }

        // Administrativo
        foreach ([9, 7] as $permisoId) {
            DB::table('rol_permiso')->insertOrIgnore([
                'rol_id' => 2, 'permiso_id' => $permisoId,
            ]);
        }

        // Asistente: estación + asistencias + salones + horarios
        foreach ([9, 12, 13, 11, 14, 7] as $permisoId) {
            DB::table('rol_permiso')->insertOrIgnore([
                'rol_id' => 4, 'permiso_id' => $permisoId,
            ]);
        }

        // Técnico: mismos permisos que Asistente
        foreach ([9, 12, 13, 11, 14, 7] as $permisoId) {
            DB::table('rol_permiso')->insertOrIgnore([
                'rol_id' => 5, 'permiso_id' => $permisoId,
            ]);
        }

        // ─── Usuario admin (solo si no existe) ────────────
        if (!DB::table('users')->where('email', 'admin@universidad.edu')->exists()) {
            $passwordAdmin = bin2hex(random_bytes(16));

            DB::table('users')->insert([
                'password'           => Hash::make($passwordAdmin, ['rounds' => 12]),
                'nombre'             => 'Super',
                'apellido'           => 'Administrador',
                'nombre_completo'    => 'Super Administrador',
                'email'              => 'admin@universidad.edu',
                'rol_id'             => 1,
                'estatus'            => 'activo',
            ]);

            $this->command->warn('Superadmin creado: admin@universidad.edu');
            $this->command->warn('Contraseña (se muestra UNA sola vez): ' . $passwordAdmin);
        }

        // ─── Departamentos (para asistencia ad-hoc) ───────
        DB::table('departamentos')->insertOrIgnore([
            ['nombre' => 'Departamento de Informática', 'codigo' => 'INFO'],
        ]);

        // ─── Salón virtual para asistencia especial ────────
        if (!DB::table('salones')->where('nombre', 'Asistencia Especial')->exists()) {
            DB::table('salones')->insert([
                'nombre'     => 'Asistencia Especial',
                'codigo'     => 'ADHOC-001',
                'codigo_qr'  => Str::uuid()->toString(),
                'capacidad'  => 0,
                'ubicacion'  => 'Virtual',
                'descripcion' => 'Salón virtual para asistencia en actividades fuera de horario regular (departamentos, defensas, reuniones, etc.)',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
