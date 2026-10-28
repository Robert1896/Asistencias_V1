<?php

namespace App\Support;

class DeviceDetector
{
    public static function detectar(?string $userAgent = null): string
    {
        $ua = $userAgent ?? request()->userAgent() ?? '';

        if (empty($ua)) {
            return 'desconocido';
        }

        $uaLower = strtolower($ua);

        if (str_contains($uaLower, 'ipad') || str_contains($uaLower, 'tablet') || str_contains($uaLower, 'kindle') || str_contains($uaLower, 'silk')) {
            return 'tablet';
        }

        if (str_contains($uaLower, 'mobile') || str_contains($uaLower, 'iphone') || str_contains($uaLower, 'ipod') || str_contains($uaLower, 'android') && !str_contains($uaLower, 'tablet') || str_contains($uaLower, 'windows phone')) {
            return 'movil';
        }

        if (str_contains($uaLower, 'windows') || str_contains($uaLower, 'macintosh') || str_contains($uaLower, 'linux') || str_contains($uaLower, 'x11') || str_contains($uaLower, 'crios') || str_contains($uaLower, 'edg') || str_contains($uaLower, 'chrome') || str_contains($uaLower, 'firefox') || str_contains($uaLower, 'safari')) {
            if (str_contains($uaLower, 'mobile')) {
                return 'movil';
            }
            return 'pc';
        }

        return 'desconocido';
    }
}
