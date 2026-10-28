<?php

namespace App\Support;

class DeviceDetector
{
    public static function detectar(?string $userAgent = null): string
    {
        $ua = $userAgent ?? request()->userAgent() ?? '';
        $ua = strtolower($ua);

        if (str_contains($ua, 'ipad') || str_contains($ua, 'tablet')) {
            return 'tablet';
        }

        if (str_contains($ua, 'mobile') || str_contains($ua, 'android') || str_contains($ua, 'iphone') || str_contains($ua, 'ipod')) {
            return 'movil';
        }

        if (str_contains($ua, 'windows') || str_contains($ua, 'macintosh') || str_contains($ua, 'x11') || str_contains($ua, 'linux')) {
            return 'pc';
        }

        return 'desconocido';
    }
}
