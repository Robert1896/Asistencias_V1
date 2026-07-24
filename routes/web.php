<?php

use App\Http\Controllers\Admin\AsistenciaController;
use App\Http\Controllers\Admin\BitacoraController;
use App\Http\Controllers\Admin\CargaAcademicaController;
use App\Http\Controllers\Admin\CorreoConfigController;
use App\Http\Controllers\Admin\DocenteHorarioController;
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
    Route::post('forgot-password', [LoginController::class, 'sendResetLink'])->name('password.email');
    Route::get('reset-password/{token}', [LoginController::class, 'showResetForm'])->name('password.reset');
    Route::post('reset-password', [LoginController::class, 'reset'])->name('password.update');
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

    // Estación de chequeo
    Route::get('estacion', [EstacionController::class, 'index'])->name('estacion.index');
    Route::post('estacion/procesar', [EstacionController::class, 'procesarLectura'])->name('estacion.procesar');
    Route::post('estacion/adhoc', [EstacionController::class, 'procesarAdHoc'])->name('estacion.adhoc');
    Route::get('estacion/manual', [EstacionController::class, 'registroManual'])->name('estacion.manual')->middleware('can:asistencias_registrar');
    Route::post('estacion/manual', [EstacionController::class, 'registrarManual'])->name('estacion.registrar')->middleware('can:asistencias_registrar');
    Route::get('estacion/buscar', [EstacionController::class, 'buscarEmpleado'])->name('estacion.buscar')->middleware('can:asistencias_registrar');

    // Escaneo de salón por el docente
    Route::get('mi-escanear-salon', [EstacionController::class, 'escanearSalon'])->name('docente.escanear.salon');
    Route::post('estacion/salon-qr', [EstacionController::class, 'procesarSalonQr'])->name('estacion.salon-qr');

    // Panel del docente
    Route::get('mi-dashboard', [\App\Http\Controllers\Empleado\DashboardController::class, 'index'])->name('empleado.dashboard');
    Route::get('mi-horario', [\App\Http\Controllers\Empleado\DashboardController::class, 'horario'])->name('empleado.horario');
    Route::get('mi-estadisticas', [\App\Http\Controllers\Empleado\DashboardController::class, 'estadisticas'])->name('empleado.estadisticas');

    // Administración
    Route::middleware('can:usuarios_ver')->prefix('admin')->group(function () {
        // Usuarios
        Route::get('usuarios', [UsuarioController::class, 'index'])->name('admin.usuarios');
        Route::middleware('can:usuarios_crear')->group(function () {
            Route::post('usuarios', [UsuarioController::class, 'store'])->name('admin.usuarios.store');
            Route::post('usuarios/importar', [UsuarioController::class, 'importar'])->name('admin.usuarios.importar');
        });
        Route::put('usuarios/{usuario}', [UsuarioController::class, 'update'])->name('admin.usuarios.update')->middleware('can:usuarios_editar');
        Route::patch('usuarios/{usuario}/toggle-estatus', [UsuarioController::class, 'toggleEstatus'])->name('admin.usuarios.toggle')->middleware('can:usuarios_editar');
        Route::delete('usuarios/{usuario}', [UsuarioController::class, 'destroy'])->name('admin.usuarios.destroy')->middleware('can:usuarios_eliminar');

        // Roles
        Route::get('roles', [RolController::class, 'index'])->name('admin.roles');
        Route::put('roles/{rol}/permisos', [RolController::class, 'updatePermisos'])->name('admin.roles.permisos');

        // Horarios (admin)
        Route::get('horarios/{usuario}/editar', [HorarioController::class, 'edit'])->name('admin.horarios.edit');
        Route::post('horarios/administrativo', [HorarioController::class, 'guardarAdmin'])->name('admin.horarios.guardar-admin');
        Route::post('horarios/carga', [HorarioController::class, 'guardarCarga'])->name('admin.horarios.guardar-carga');
        Route::delete('horarios/carga/{carga}', [HorarioController::class, 'eliminarCarga'])->name('admin.horarios.eliminar-carga');

        // Reportes
        Route::get('reportes', [ReporteController::class, 'index'])->name('admin.reportes');
        Route::get('reportes/exportar', [ReporteController::class, 'exportar'])->name('admin.reportes.exportar');
        Route::get('reportes/empleado/{empleado}', [ReporteController::class, 'empleado'])->name('admin.reportes.empleado');
        Route::get('reportes/empleado/{empleado}/exportar', [ReporteController::class, 'empleadoExportar'])->name('admin.reportes.empleado.exportar');

        // Horarios docentes
        Route::get('docentes/horarios', [DocenteHorarioController::class, 'index'])->name('admin.docentes.horarios');
        Route::get('docentes/{user}/horarios/editar', [DocenteHorarioController::class, 'edit'])->name('admin.docentes.horarios.edit');
        Route::put('docentes/{user}/horarios', [DocenteHorarioController::class, 'update'])->name('admin.docentes.horarios.update');

        // Salones (CRUD)
        Route::get('salones', [SalonController::class, 'index'])->name('admin.salones.index');
        Route::post('salones', [SalonController::class, 'store'])->name('admin.salones.store');
        Route::put('salones/{salon}', [SalonController::class, 'update'])->name('admin.salones.update');
        Route::delete('salones/{salon}', [SalonController::class, 'destroy'])->name('admin.salones.destroy');
        Route::get('salones/{salon}/qr', [SalonController::class, 'qrCode'])->name('admin.salones.qr');
        Route::post('salones/{salon}/qr/regenerar', [SalonController::class, 'regenerarQr'])->name('admin.salones.qr.regenerar');

        // Exportar horario de salón (PDF, CSV, imagen)
        Route::get('salones/{salon}/exportar', [SalonController::class, 'exportar'])->name('admin.salones.exportar');

        // Disponibilidad de salón (AJAX)
        Route::post('salones/disponibilidad', [SalonController::class, 'disponibilidad'])->name('admin.salones.disponibilidad');

        // Importar asignaciones de salones
        Route::delete('salones/asignaciones/limpiar', [SalonController::class, 'limpiarAsignaciones'])->name('admin.salones.limpiar');
        Route::post('salones/importar', [SalonController::class, 'importar'])->name('admin.salones.importar');
        Route::get('salones/plantilla', [SalonController::class, 'plantilla'])->name('admin.salones.plantilla');

        // Bitácora
        Route::get('bitacora', [BitacoraController::class, 'index'])->name('admin.bitacora');

        // Configuración de correo
        Route::get('correo', [CorreoConfigController::class, 'index'])->name('admin.correo');
        Route::put('correo', [CorreoConfigController::class, 'update'])->name('admin.correo.update');
        Route::post('correo/probar', [CorreoConfigController::class, 'probar'])->name('admin.correo.probar');

        // Días feriados
        Route::get('feriados', [FeriadoController::class, 'index'])->name('admin.feriados');
        Route::post('feriados', [FeriadoController::class, 'store'])->name('admin.feriados.store');
        Route::delete('feriados/{feriado}', [FeriadoController::class, 'destroy'])->name('admin.feriados.destroy');

        // Materias por docente
        Route::get('materias', [CargaAcademicaController::class, 'index'])->name('admin.materias');
        Route::post('materias', [CargaAcademicaController::class, 'store'])->name('admin.materias.store');
        Route::put('materias/{carga}', [CargaAcademicaController::class, 'update'])->name('admin.materias.update');
        Route::delete('materias/{carga}', [CargaAcademicaController::class, 'destroy'])->name('admin.materias.destroy');
    });

    // Accesos compartidos (usuarios_ver O permiso específico)
    Route::prefix('admin')->group(function () {
        Route::get('horarios', [HorarioController::class, 'index'])->name('admin.horarios')->middleware('can:horarios_ver');
        Route::get('asistencias', [AsistenciaController::class, 'index'])->name('admin.asistencias')->middleware('can:asistencias_ver');
        Route::put('asistencias/{asistencia}/justificar', [AsistenciaController::class, 'justificar'])->name('admin.asistencias.justificar')->middleware('can:asistencias_justificar');
        Route::get('salones/ocupacion', [SalonController::class, 'ocupacion'])->name('admin.salones.ocupacion')->middleware('can:salones_ver');
        Route::post('salones/ocupacion/data', [SalonController::class, 'ocupacionData'])->name('admin.salones.ocupacion.data')->middleware('can:salones_ver');
    });

    // QR (sin permiso específico, cualquier autenticado lo ve)
    Route::get('mi-qr-dinamico', [QrController::class, 'showPage'])->name('qr.dinamico.page');
    Route::get('qr/{user}', [QrController::class, 'show'])->name('qr.show');
    Route::get('qr/{user}/dinamico', [QrController::class, 'qrDinamico'])->name('qr.dinamico');
    Route::post('qr/{user}/token', [QrController::class, 'generarToken'])->name('qr.token');
});
