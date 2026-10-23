<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
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

        Gate::before(function ($user, $ability) {
            if (!$user) {
                return null;
            }
            // Cuenta desactivada: sin autorización en ninguna parte (Fase B)
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
     * el rate limiter (login) lanza un error 500. Si no se pueden crear,
     * se degrada a cache en memoria para no romper la aplicación.
     */
    private function asegurarCachePersistente(): void
    {
        if (config('cache.default') !== 'database') {
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

            if (!Schema::hasTable('cache_locks')) {
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

    private function configurarCorreo(): void
    {
        try {
            if (!Schema::hasTable('correo_config')) {
                return;
            }
        } catch (\Exception $e) {
            return;
        }

        $config = \App\Models\CorreoConfig::where('activo', true)->first();

        if (!$config) {
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
