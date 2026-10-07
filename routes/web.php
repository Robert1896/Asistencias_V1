<?php

use App\Http\Controllers\Admin\AsistenciaController;
use App\Http\Controllers\Admin\BitacoraController;
use App\Http\Controllers\Admin\CargaAcademicaController;
use App\Http\Controllers\Admin\CorreoConfigController;
use App\Http\Controllers\Admin\DocenteHorarioController;
use App\Http\Controllers\Admin\EstacionCentralController;
use App\Http\Controllers\Admin\HorarioController;
use App\Http\Controllers\Admin\FeriadoController;
use App\Http\Controllers\Admin\ReporteController;
use App\Http\Controllers\Admin\RolController;
use App\Http\Controllers\Admin\SalonController;
use App\Http\Controllers\Admin\UsuarioController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EstacionController;
use App\Http\Controllers\JustificacionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\QrController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Autenticación
|--------------------------------------------------------------------------
*/
Route::middleware(['guest', 'no-cache'])->group(function () {
    Route::get('login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('login', [LoginController::class, 'login']);
    Route::get('forgot-password', [LoginController::class, 'showForgotForm'])->name('password.request');
    Route::post('forgot-password', [LoginController::class, 'sendResetLink'])->middleware('throttle:5,1')->name('password.email');
    Route::get('reset-password/{token}', [LoginController::class, 'showResetForm'])->name('password.reset');
    Route::post('reset-password', [LoginController::class, 'reset'])->middleware('throttle:10,1')->name('password.update');
});

Route::post('logout', [LoginController::class, 'logout'])->middleware(['auth', 'no-cache'])->name('logout');

/*
|--------------------------------------------------------------------------
| Zona autenticada
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'no-cache'])->group(function () {
    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Perfil
    Route::get('profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('profile', [ProfileController::class, 'update'])->name('profile.update');

    // Justificaciones del empleado
    Route::get('mis-justificaciones', [JustificacionController::class, 'index'])->name('justificaciones');
    Route::post('justificar/{asistencia}', [JustificacionController::class, 'solicitar'])->name('justificaciones.solicitar');
    Route::get('justificaciones/archivo/{asistencia}', [JustificacionController::class, 'archivo'])->name('justificaciones.archivo');

    // Estación de chequeo (sujeta a verificación de estación central)
    Route::middleware('can:estacion_operar')->group(function () {
        Route::get('estacion', [EstacionController::class, 'index'])->name('estacion.index')->middleware('estacion.central');
        Route::post('estacion/procesar', [EstacionController::class, 'procesarLectura'])->name('estacion.procesar')->middleware(['estacion.central', 'throttle:60,1']);
        Route::post('estacion/adhoc', [EstacionController::class, 'procesarAdHoc'])->name('estacion.adhoc')->middleware(['estacion.central', 'throttle:60,1']);
    });
    Route::get('estacion/manual', [EstacionController::class, 'registroManual'])->name('estacion.manual')->middleware(['can:asistencias_registrar', 'estacion.central']);
    Route::post('estacion/manual', [EstacionController::class, 'registrarManual'])->name('estacion.registrar')->middleware(['can:asistencias_registrar', 'estacion.central', 'throttle:30,1']);
    Route::get('estacion/buscar', [EstacionController::class, 'buscarEmpleado'])->name('estacion.buscar')->middleware(['can:asistencias_registrar', 'estacion.central']);

    // Escaneo de salón por el docente
    Route::get('mi-escanear-salon', [EstacionController::class, 'escanearSalon'])->name('docente.escanear.salon');
    Route::post('estacion/salon-qr', [EstacionController::class, 'procesarSalonQr'])->name('estacion.salon-qr')->middleware('throttle:60,1');

    // Panel del docente
    Route::get('mi-dashboard', [\App\Http\Controllers\Empleado\DashboardController::class, 'index'])->name('empleado.dashboard');
    Route::get('mi-horario', [\App\Http\Controllers\Empleado\DashboardController::class, 'horario'])->name('empleado.horario');
    Route::get('mi-estadisticas', [\App\Http\Controllers\Empleado\DashboardController::class, 'estadisticas'])->name('empleado.estadisticas');

    // ─── Administración ─────────────────────────────────────────────
    // Usuarios: llave maestra usuarios_ver + permiso específico anidado
    Route::middleware('can:usuarios_ver')->prefix('admin')->group(function () {
        Route::get('usuarios', [UsuarioController::class, 'index'])->name('admin.usuarios');
        Route::middleware('can:usuarios_crear')->group(function () {
            Route::post('usuarios', [UsuarioController::class, 'store'])->name('admin.usuarios.store');
            Route::post('usuarios/importar', [UsuarioController::class, 'importar'])->name('admin.usuarios.importar')->middleware('throttle:10,1');
        });
        Route::put('usuarios/{usuario}', [UsuarioController::class, 'update'])->name('admin.usuarios.update')->middleware('can:usuarios_editar');
        Route::patch('usuarios/{usuario}/toggle-estatus', [UsuarioController::class, 'toggleEstatus'])->name('admin.usuarios.toggle')->middleware('can:usuarios_editar');
        Route::delete('usuarios/{usuario}', [UsuarioController::class, 'destroy'])->name('admin.usuarios.destroy')->middleware('can:usuarios_eliminar');
    });

    // Módulos: permiso específico por ruta (Fase B — fin de usuarios_ver como llave maestra)
    Route::prefix('admin')->group(function () {
        // Roles
        Route::get('roles', [RolController::class, 'index'])->name('admin.roles')->middleware('can:roles_ver');
        Route::put('roles/{rol}/permisos', [RolController::class, 'updatePermisos'])->name('admin.roles.permisos')->middleware('can:roles_editar');

        // Horarios
        Route::get('horarios', [HorarioController::class, 'index'])->name('admin.horarios')->middleware('can:horarios_ver');
        Route::get('horarios/{usuario}/editar', [HorarioController::class, 'edit'])->name('admin.horarios.edit')->middleware('can:horarios_editar');
        Route::post('horarios/administrativo', [HorarioController::class, 'guardarAdmin'])->name('admin.horarios.guardar-admin')->middleware('can:horarios_editar');
        Route::post('horarios/carga', [HorarioController::class, 'guardarCarga'])->name('admin.horarios.guardar-carga')->middleware('can:horarios_editar');
        Route::delete('horarios/carga/{carga}', [HorarioController::class, 'eliminarCarga'])->name('admin.horarios.eliminar-carga')->middleware('can:horarios_editar');

        // Horarios docentes
        Route::get('docentes/horarios', [DocenteHorarioController::class, 'index'])->name('admin.docentes.horarios')->middleware('can:horarios_editar');
        Route::get('docentes/{user}/horarios/editar', [DocenteHorarioController::class, 'edit'])->name('admin.docentes.horarios.edit')->middleware('can:horarios_editar');
        Route::put('docentes/{user}/horarios', [DocenteHorarioController::class, 'update'])->name('admin.docentes.horarios.update')->middleware('can:horarios_editar');

        // Reportes
        Route::get('reportes', [ReporteController::class, 'index'])->name('admin.reportes')->middleware('can:reportes_ver');
        Route::get('reportes/exportar', [ReporteController::class, 'exportar'])->name('admin.reportes.exportar')->middleware('can:reportes_ver');
        Route::get('reportes/empleado/{empleado}', [ReporteController::class, 'empleado'])->name('admin.reportes.empleado')->middleware('can:reportes_ver');
        Route::get('reportes/empleado/{empleado}/exportar', [ReporteController::class, 'empleadoExportar'])->name('admin.reportes.empleado.exportar')->middleware('can:reportes_ver');

        // Salones (CRUD + utilidades)
        Route::get('salones', [SalonController::class, 'index'])->name('admin.salones.index')->middleware('can:salones_ver');
        Route::post('salones', [SalonController::class, 'store'])->name('admin.salones.store')->middleware('can:salones_crear');
        Route::put('salones/{salon}', [SalonController::class, 'update'])->name('admin.salones.update')->middleware('can:salones_editar');
        Route::delete('salones/{salon}', [SalonController::class, 'destroy'])->name('admin.salones.destroy')->middleware('can:salones_eliminar');
        Route::get('salones/{salon}/qr', [SalonController::class, 'qrCode'])->name('admin.salones.qr')->middleware('can:salones_ver');
        Route::post('salones/{salon}/qr/regenerar', [SalonController::class, 'regenerarQr'])->name('admin.salones.qr.regenerar')->middleware('can:salones_editar');
        Route::get('salones/{salon}/exportar', [SalonController::class, 'exportar'])->name('admin.salones.exportar')->middleware('can:salones_ver');
        Route::post('salones/disponibilidad', [SalonController::class, 'disponibilidad'])->name('admin.salones.disponibilidad')->middleware('can:salones_ver');
        Route::delete('salones/asignaciones/limpiar', [SalonController::class, 'limpiarAsignaciones'])
            ->middleware(['can:salones_eliminar', 'throttle:5,60'])
            ->name('admin.salones.limpiar');
        Route::post('salones/importar', [SalonController::class, 'importar'])->name('admin.salones.importar')->middleware(['can:salones_editar', 'throttle:10,1']);
        Route::get('salones/plantilla', [SalonController::class, 'plantilla'])->name('admin.salones.plantilla')->middleware('can:salones_ver');
        Route::get('salones/ocupacion', [SalonController::class, 'ocupacion'])->name('admin.salones.ocupacion')->middleware('can:salones_ver');
        Route::post('salones/ocupacion/data', [SalonController::class, 'ocupacionData'])->name('admin.salones.ocupacion.data')->middleware('can:salones_ver');

        // Asistencias
        Route::get('asistencias', [AsistenciaController::class, 'index'])->name('admin.asistencias')->middleware('can:asistencias_ver');
        Route::put('asistencias/{asistencia}/justificar', [AsistenciaController::class, 'justificar'])->name('admin.asistencias.justificar')->middleware('can:asistencias_justificar');

        // Bitácora
        Route::get('bitacora', [BitacoraController::class, 'index'])->name('admin.bitacora')->middleware('can:bitacora_ver');

        // Configuración de correo
        Route::get('correo', [CorreoConfigController::class, 'index'])->name('admin.correo')->middleware('can:correo_editar');
        Route::put('correo', [CorreoConfigController::class, 'update'])->name('admin.correo.update')->middleware('can:correo_editar');
        Route::post('correo/probar', [CorreoConfigController::class, 'probar'])->name('admin.correo.probar')->middleware('can:correo_editar');

        // Estación central
        Route::get('estacion-central', [EstacionCentralController::class, 'edit'])->name('admin.estacion.central')->middleware('can:estacion_configurar');
        Route::put('estacion-central', [EstacionCentralController::class, 'update'])->name('admin.estacion.central.update')->middleware('can:estacion_configurar');

        // Días feriados
        Route::get('feriados', [FeriadoController::class, 'index'])->name('admin.feriados')->middleware('can:feriados_editar');
        Route::post('feriados', [FeriadoController::class, 'store'])->name('admin.feriados.store')->middleware('can:feriados_editar');
        Route::delete('feriados/{feriado}', [FeriadoController::class, 'destroy'])->name('admin.feriados.destroy')->middleware('can:feriados_editar');

        // Materias por docente
        Route::get('materias', [CargaAcademicaController::class, 'index'])->name('admin.materias')->middleware('can:materias_editar');
        Route::post('materias', [CargaAcademicaController::class, 'store'])->name('admin.materias.store')->middleware('can:materias_editar');
        Route::put('materias/{carga}', [CargaAcademicaController::class, 'update'])->name('admin.materias.update')->middleware('can:materias_editar');
        Route::delete('materias/{carga}', [CargaAcademicaController::class, 'destroy'])->name('admin.materias.destroy')->middleware('can:materias_editar');
    });

    // QR (sin permiso específico, cualquier autenticado lo ve)
    Route::get('mi-qr-dinamico', [QrController::class, 'showPage'])->name('qr.dinamico.page');
    Route::get('qr/{user}', [QrController::class, 'show'])->name('qr.show');
    Route::get('qr/{user}/dinamico', [QrController::class, 'qrDinamico'])->name('qr.dinamico');
    Route::post('qr/{user}/token', [QrController::class, 'generarToken'])->name('qr.token')->middleware('throttle:30,1');
});
