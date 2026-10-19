<?php

use App\Http\Controllers\Api\V1\AsistenciaApiController;
use App\Http\Controllers\Api\V1\UsuarioApiController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware(['auth:sanctum', 'throttle:api'])->group(function () {
    Route::get('usuarios', [UsuarioApiController::class, 'index'])
        ->middleware('api.admin:usuarios_ver');
    Route::get('usuarios/{usuario}', [UsuarioApiController::class, 'show'])
        ->middleware('api.admin:usuarios_ver');

    Route::get('asistencias', [AsistenciaApiController::class, 'index'])
        ->middleware('api.admin:asistencias_ver');
    Route::get('asistencias/{asistencia}', [AsistenciaApiController::class, 'show'])
        ->middleware('api.admin:asistencias_ver');
});