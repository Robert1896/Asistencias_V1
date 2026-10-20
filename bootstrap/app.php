<?php

use App\Http\Middleware\ApiAdminOnly;
use App\Http\Middleware\DocenteSessionTimeout;
use App\Http\Middleware\NoCacheHeaders;
use App\Http\Middleware\RegistrarAccesoDenegado;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SesionAbsoluta;
use App\Http\Middleware\VerificarEstacionCentral;
use App\Http\Middleware\W3cCompliance;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            $cronSecret = function (\Illuminate\Http\Request $request): void {
                $secret = (string) config('app.cron_secret');
                abort_if($secret === '' || ! hash_equals($secret, (string) $request->bearerToken()), 401);
            };

            Route::get('cron/mark-absents', function (\Illuminate\Http\Request $request) use ($cronSecret) {
                $cronSecret($request);

                \Illuminate\Support\Facades\Artisan::call('app:mark-absents');

                return response()->json([
                    'ok'     => true,
                    'output' => trim(\Illuminate\Support\Facades\Artisan::output()),
                ]);
            })->name('cron.mark-absents');

            Route::get('cron/migrate', function (\Illuminate\Http\Request $request) use ($cronSecret) {
                $cronSecret($request);

                \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);

                return response()->json([
                    'ok'     => true,
                    'output' => trim(\Illuminate\Support\Facades\Artisan::output()),
                ]);
            })->name('cron.migrate');

            Route::get('cron/import-datos', function (\Illuminate\Http\Request $request) use ($cronSecret) {
                $cronSecret($request);

                $path = database_path('data/datos_asistencias.php');
                abort_unless(is_file($path), 404, 'No se encontro el archivo de datos.');

                $sql = require $path;
                abort_unless(is_string($sql) && trim($sql) !== '', 500, 'Archivo de datos invalido.');

                $pdo = \Illuminate\Support\Facades\DB::connection()->getPdo();
                $pdo->exec('SET NAMES utf8mb4');

                $ejecutadas = 0;
                foreach (array_filter(array_map('trim', preg_split('/;\R/', $sql))) as $sentencia) {
                    $pdo->exec($sentencia);
                    $ejecutadas++;
                }

                $conteos = [];
                foreach (['users', 'roles', 'permisos', 'horarios', 'salones', 'asistencias'] as $tabla) {
                    $conteos[$tabla] = (int) \Illuminate\Support\Facades\DB::table($tabla)->count();
                }

                return response()->json([
                    'ok'               => true,
                    'sentencias'       => $ejecutadas,
                    'conteos'          => $conteos,
                    'muestra'          => \Illuminate\Support\Facades\DB::table('users')
                        ->where('nombre', 'like', '%Jos%')
                        ->limit(3)
                        ->get(['nombre', 'apellido', 'nombre_completo']),
                ]);
            })->name('cron.import-datos');
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');
        $middleware->append(SecurityHeaders::class);
        $middleware->append(W3cCompliance::class);
        $middleware->append(RegistrarAccesoDenegado::class);
        $middleware->web(append: [
            DocenteSessionTimeout::class,
            \App\Http\Middleware\VerificarEstatusActivo::class,
            SesionAbsoluta::class,
        ]);
        $middleware->alias([
            'no-cache' => NoCacheHeaders::class,
            'api.admin' => ApiAdminOnly::class,
            'estacion.central' => VerificarEstacionCentral::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->reportable(function (\Throwable $e) {
            Log::error($e->getMessage(), [
                'exception' => $e,
                'url'       => request()->url(),
                'user_id'   => auth()->id(),
            ]);
        });
        $exceptions->renderable(function (\Illuminate\Session\TokenMismatchException $e) {
            return redirect()->route('login')
                ->with('error', 'La sesión ha expirado. Por favor, inicia sesión nuevamente.');
        });
    })->create();
