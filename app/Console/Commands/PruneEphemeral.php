<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PruneEphemeral extends Command
{
    protected $signature = 'app:prune-ephemeral';

    protected $description = 'Purga sesiones y entradas de cache vencidas para evitar crecimiento y presion de escritura';

    /**
     * Las tablas `sessions` y `cache` crecen sin limite cuando el driver es
     * database (produccion en Vercel). Laravel no las poda solo y en Hobby el
     * cron corre una vez al dia, asi que se purgan ambas de una pasada.
     */
    public function handle(): int
    {
        $sesiones = $this->purgarSesiones();
        $cache = $this->purgarCache();

        $this->info("Sesiones purgadas: {$sesiones}. Cache purgada: {$cache}.");

        return self::SUCCESS;
    }

    private function purgarSesiones(): int
    {
        if (config('session.driver') !== 'database') {
            return 0;
        }

        $tabla = (string) config('session.table', 'sessions');

        try {
            if (! Schema::hasTable($tabla)) {
                return 0;
            }

            // Margen de 24 h sobre el lifetime configurado.
            $limite = now()
                ->subMinutes((int) config('session.lifetime', 120) + 1440)
                ->getTimestamp();

            return DB::table($tabla)->where('last_activity', '<', $limite)->delete();
        } catch (\Throwable $e) {
            $this->warn('No se pudieron purgar las sesiones: '.$e->getMessage());

            return 0;
        }
    }

    private function purgarCache(): int
    {
        if (config('cache.default') !== 'database') {
            return 0;
        }

        try {
            if (! Schema::hasTable('cache')) {
                return 0;
            }

            return DB::table('cache')->where('expiration', '<', now()->getTimestamp())->delete();
        } catch (\Throwable $e) {
            $this->warn('No se pudo purgar el cache: '.$e->getMessage());

            return 0;
        }
    }
}
