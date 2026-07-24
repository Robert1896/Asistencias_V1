<?php

namespace App\Providers;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Paginator::useBootstrapFive();

        Gate::before(function ($user, $ability) {
            if (!$user) {
                return null;
            }
            if ($user->rol_id === 1) {
                return true;
            }
            return $user->tienePermiso($ability) ? true : null;
        });

        $this->configurarCorreo();
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

        $config = DB::table('correo_config')->where('activo', true)->first();

        if (!$config) {
            return;
        }

        config(['mail.default' => 'smtp']);
        config(['mail.mailers.smtp.host' => $config->host]);
        config(['mail.mailers.smtp.port' => $config->puerto]);
        config(['mail.mailers.smtp.encryption' => $config->encriptacion]);
        config(['mail.mailers.smtp.username' => $config->usuario]);
        config(['mail.mailers.smtp.password' => $this->decryptPassword($config->contrasena)]);
        config(['mail.from.address' => $config->direccion_from]);
        config(['mail.from.name' => $config->nombre_from]);
    }

    private function decryptPassword(string $encrypted): string
    {
        try {
            return Crypt::decryptString($encrypted);
        } catch (\Exception $e) {
            return $encrypted;
        }
    }
}
