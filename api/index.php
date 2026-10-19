<?php

/*
|--------------------------------------------------------------------------
| Entrypoint para Vercel (runtime vercel-php)
|--------------------------------------------------------------------------
| Vercel exige que las funciones serverless vivan en api/. Reenviamos
| todas las peticiones al front controller real de Laravel.
*/

require __DIR__.'/../public/index.php';
