<?php

namespace App\Http\Middleware;

use App\Models\ActivityLog;
use App\Models\EstacionCentral;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\IpUtils;
use Symfony\Component\HttpFoundation\Response;

class VerificarEstacionCentral
{
    public function handle(Request $request, Closure $next): Response
    {
        $config = EstacionCentral::obtener();

        if (!$config->activo || $config->modo === 'ninguno') {
            return $next($request);
        }

        $fallas = [];

        if ($config->verificaRed()) {
            $ip         = (string) $request->ip();
            $permitidas = array_filter(array_map('trim', explode(',', (string) $config->ip_permitida)));

            if (empty($permitidas)) {
                $fallas[] = 'red_sin_configurar';
            } else {
                $coincide = false;
                foreach ($permitidas as $rango) {
                    try {
                        if (IpUtils::checkIp($ip, $rango)) {
                            $coincide = true;
                            break;
                        }
                    } catch (\Throwable $e) {
                        continue;
                    }
                }
                if (!$coincide) {
                    $fallas[] = 'red';
                }
            }
        }

        // El GPS se exige solo en peticiones no-GET: el GET debe poder cargar la
        // página para que el navegador pueda pedir la ubicación al usuario.
        if ($config->verificaGps() && !$request->isMethod('GET')) {
            $lat = $request->header('X-Geo-Lat');
            $lng = $request->header('X-Geo-Lng');

            if ($lat === null || $lng === null || !is_numeric($lat) || !is_numeric($lng)) {
                $fallas[] = 'gps_ausente';
            } elseif ($config->latitud === null || $config->longitud === null) {
                $fallas[] = 'gps_sin_configurar';
            } else {
                $distancia = $this->distanciaMetros(
                    (float) $config->latitud,
                    (float) $config->longitud,
                    (float) $lat,
                    (float) $lng
                );
                if ($distancia > $config->radio_metros) {
                    $fallas[] = 'gps_lejos:' . round($distancia);
                }
            }
        }

        if (empty($fallas)) {
            return $next($request);
        }

        $mensaje = $this->mensajeFallas($fallas, $config);

        ActivityLog::log('estacion_no_autorizada', "Estación central no autorizada: " . implode(', ', $fallas), 'seguridad', [
            'ruta'      => $request->method() . ' ' . $request->path(),
            'ip'        => $request->ip(),
            'modo'      => $config->modo,
            'fallas'    => $fallas,
            'user_id'   => $request->user()?->id,
        ]);

        Log::warning('Estación central no autorizada', [
            'ruta'    => $request->method() . ' ' . $request->path(),
            'fallas'  => $fallas,
            'ip'      => $request->ip(),
            'user_id' => $request->user()?->id,
        ]);

        if ($request->isMethod('GET') && !$request->isJson()) {
            abort(403, $mensaje);
        }

        return response()->json([
            'ok'      => false,
            'tipo'    => 'error',
            'mensaje' => $mensaje,
        ], 403);
    }

    private function mensajeFallas(array $fallas, EstacionCentral $config): string
    {
        foreach ($fallas as $falla) {
            if ($falla === 'red' || $falla === 'red_sin_configurar') {
                return $falla === 'red_sin_configurar'
                    ? 'La verificación por red está activa pero no tiene una IP o rango autorizado configurado. Contacte al administrador.'
                    : 'Esta estación solo puede operarse desde la red autorizada de la estación central.';
            }
        }

        foreach ($fallas as $falla) {
            if ($falla === 'gps_ausente') {
                return 'No se pudo verificar la ubicación del equipo. Habilite la permisión de ubicación (GPS) y reintente.';
            }
            if ($falla === 'gps_sin_configurar') {
                return 'La verificación por GPS está activa pero la ubicación de la estación no está configurada. Contacte al administrador.';
            }
            if (str_starts_with($falla, 'gps_lejos')) {
                $metros = (int) str_replace('gps_lejos:', '', $falla);
                return "La ubicación del equipo está a ~{$metros} m de la estación autorizada (radio: {$config->radio_metros} m).";
            }
        }

        return 'Estación no autorizada.';
    }

    private function distanciaMetros(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $radioTierra = 6371000.0;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return 2 * $radioTierra * asin(min(1.0, sqrt($a)));
    }
}
