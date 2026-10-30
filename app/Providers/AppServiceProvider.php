<?php

namespace App\Providers;

use App\Models\CorreoConfig;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Evita repetir la consulta de correo_config dentro del mismo request.
     */
    private static bool $correoConfigurado = false;

    public function register(): void {}

    public function boot(): void
    {
        $this->asegurarCachePersistente();

        Paginator::useBootstrapFive();

        RateLimiter::for('api', function ($user) {
            return $user
                ? Limit::perMinute(60)->by($user->id)
                : Limit::perMinute(20)->by($user?->ip() ?? 'anon');
        });

        // Trafico publico (login, QR, paginas sin sesion): tope por IP y ruta.
        // Se incluye la ruta para que un campus detras de NAT no comparta un
        // unico presupuesto entre login, QR y descargas.
        RateLimiter::for('publico', function (Request $request) {
            return Limit::perMinute(60)->by($request->ip().'|'.$request->route()?->uri());
        });

        // Exportaciones y generacion de archivos: costosas en CPU/memoria.
        RateLimiter::for('exportaciones', function (Request $request) {
            return Limit::perMinute(6)->by($request->user()?->id ?? $request->ip());
        });

        Gate::before(function ($user, $ability) {
            if (! $user) {
                return null;
            }
            // Cuenta desactivada: sin autorizacion en ninguna parte (Fase B)
            if ($user->estatus !== 'activo') {
                return false;
            }
            if ($user->rol_id === 1) {
                return true;
            }

            return $user->tienePermiso($ability) ? true : null;
        });

        $this->configurarCorreo();
    }

    /**
     * Garantiza que las tablas del cache en base de datos existan.
     * En Vercel no se ejecutan migraciones al desplegar; si la tabla falta,
     * el rate limiter (login) lanza un error 500. Si no se pueden crear, se
     * degrada a cache en memoria para no romper la aplicacion.
     *
     * La auto-creacion solo corre cuando APP_DB_BOOTSTRAP_CHECKS esta activo
     * (por defecto). En produccion se desactiva para no pagar dos consultas
     * de esquema en cada request: el 404 de un bot tambien las ejecutaba.
     */
    private function asegurarCachePersistente(): void
    {
        if (config('cache.default') !== 'database') {
            return;
        }

        if (! config('app.db_bootstrap_checks', true)) {
            return;
        }

        try {
            if (Schema::hasTable('cache')) {
                return;
            }

            Schema::create('cache', function (Blueprint $table) {
                $table->string('key')->primary();
                $table->mediumText('value');
                $table->bigInteger('expiration')->index();
            });

            if (! Schema::hasTable('cache_locks')) {
                Schema::create('cache_locks', function (Blueprint $table) {
                    $table->string('key')->primary();
                    $table->string('owner');
                    $table->bigInteger('expiration')->index();
                });
            }
        } catch (\Throwable $e) {
            config(['cache.default' => 'array']);
        }
    }

    /**
     * Aplica la configuracion SMTP guardada por el administrador.
     *
     * Antes se consultaba primero Schema::hasTable('correo_config'): dos
     * round-trips a TiDB en cada request. Ahora se hace una sola consulta y
     * se captura la excepcion si la tabla aun no existe.
     */
    private function configurarCorreo(): void
    {
        if (self::$correoConfigurado || ! config('app.mail_from_db', true)) {
            return;
        }

        self::$correoConfigurado = true;

        try {
            $config = CorreoConfig::where('activo', true)->first();
        } catch (\Throwable $e) {
            // Tabla ausente (instalacion nueva) o base no disponible: se usa
            // el mailer por defecto sin interrumpir el request.
            return;
        }

        if (! $config) {
            return;
        }

        config(['mail.default' => 'smtp']);
        config(['mail.mailers.smtp.host' => $config->host]);
        config(['mail.mailers.smtp.port' => $config->puerto]);
        config(['mail.mailers.smtp.encryption' => $config->encriptacion]);
        config(['mail.mailers.smtp.username' => $config->usuario]);
        config(['mail.mailers.smtp.password' => $config->descifrarContrasena()]);
        config(['mail.from.address' => $config->direccion_from]);
        config(['mail.from.name' => $config->nombre_from]);
    }
}
