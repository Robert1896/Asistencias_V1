<?php

use App\Http\Controllers\Api\V1\AsistenciaApiController;
use App\Http\Controllers\Api\V1\UsuarioApiController;
use Illuminate\Support\Facades\Route;

Route::get('diag-db', function (\Illuminate\Http\Request $request) {
    $secret = (string) config('app.cron_secret');
    abort_if($secret === '' || ! hash_equals($secret, (string) $request->bearerToken()), 401);

    $conn = config('database.default');

    try {
        \Illuminate\Support\Facades\DB::connection()->getPdo();
        $info = \Illuminate\Support\Facades\DB::select('select database() as db, current_user() as usr, version() as ver');
        $tables = \Illuminate\Support\Facades\DB::select('show tables');

        return response()->json([
            'ok'            => true,
            'default'       => $conn,
            'host'          => config('database.connections.mysql.host'),
            'port'          => config('database.connections.mysql.port'),
            'database'      => config('database.connections.mysql.database'),
            'username'      => config('database.connections.mysql.username'),
            'ssl_ca_env'    => env('MYSQL_ATTR_SSL_CA'),
            'info'          => $info,
            'table_count'   => count($tables),
        ]);
    } catch (\Throwable $e) {
        return response()->json([
            'ok'       => false,
            'default'  => $conn,
            'host'     => config('database.connections.mysql.host'),
            'port'     => config('database.connections.mysql.port'),
            'database' => config('database.connections.mysql.database'),
            'username' => config('database.connections.mysql.username'),
            'ssl_ca_env' => env('MYSQL_ATTR_SSL_CA'),
            'class'    => get_class($e),
            'message'  => $e->getMessage(),
        ], 500);
    }
})->name('diag.db');

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