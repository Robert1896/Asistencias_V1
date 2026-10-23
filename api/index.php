<?php

/*
|--------------------------------------------------------------------------
| Entrypoint para Vercel (runtime vercel-php)
|--------------------------------------------------------------------------
| Vercel exige que las funciones serverless vivan en api/. Reenviamos
| todas las peticiones al front controller real de Laravel.
*/

$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$path = parse_url($requestUri, PHP_URL_PATH) ?: '/';

// Vercel ejecuta las funciones bajo /api y elimina ese prefijo del path que
// recibe PHP. Laravel define sus rutas API con prefijo /api, asi que las
// restauramos para que /api/v1/* siga funcionando en produccion.
if ($path === '/v1' || str_starts_with($path, '/v1/')) {
    $_SERVER['REQUEST_URI'] = '/api' . $requestUri;
}

require __DIR__.'/../public/index.php';
