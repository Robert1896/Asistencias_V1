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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::get('diag-db', function (\Illuminate\Http\Request $request) {
                $caEnv = env('MYSQL_ATTR_SSL_CA');
                $base = [
                    'default'         => config('database.default'),
                    'host'            => config('database.connections.mysql.host'),
                    'port'            => config('database.connections.mysql.port'),
                    'database'        => config('database.connections.mysql.database'),
                    'username'        => config('database.connections.mysql.username'),
                    'ssl_ca_env'      => $caEnv,
                    'ssl_ca_exists'   => $caEnv ? file_exists($caEnv) : false,
                    'base_path'       => base_path(),
                    'ca_in_base'      => file_exists(base_path('ca.pem')),
                    'auth_header_len' => strlen((string) $request->header('Authorization')),
                    'bearer_len'      => strlen((string) $request->bearerToken()),
                    'cron_secret_len' => strlen((string) config('app.cron_secret')),
                    'env_cron_len'    => strlen((string) env('CRON_SECRET')),
                ];

                try {
                    DB::connection()->getPdo();
                    $info = DB::select('select database() as db, current_user() as usr, version() as ver');

                    return response()->json(array_merge($base, ['ok' => true, 'info' => $info]));
                } catch (\Throwable $e) {
                    return response()->json(array_merge($base, [
                        'ok'      => false,
                        'class'   => get_class($e),
                        'message' => $e->getMessage(),
                    ]), 500);
                }
            });
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
