<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class W3cCompliance
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if ($response instanceof \Illuminate\Http\Response && str_contains($response->headers->get('Content-Type', ''), 'text/html')) {
            $content = $response->getContent();
            $content = preg_replace('/(<input[^>]*type=["\']hidden["\'][^>]*?)\s+autocomplete=["\'](?:on|off)["\']/i', '$1', $content);
            $response->setContent($content);
        }

        return $response;
    }
}
