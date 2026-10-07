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

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
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
