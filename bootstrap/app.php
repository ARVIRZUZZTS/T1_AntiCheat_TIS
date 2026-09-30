<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

$basePath = dirname(__DIR__);

// En el servidor de la UMSS el doc root debe llamarse `public_html`, no `public`.
// Sin esto, public_path() resuelve a `public/` y @vite no encuentra
// `build/manifest.json` (los assets igual se sirven desde /build/... porque
// las URLs las arma asset(), no public_path()). Se detecta solo: en local no
// existe public_html/, asi que sigue usando public/.
$publicPath = is_dir($basePath.'/public_html') ? $basePath.'/public_html' : $basePath.'/public';

$app = Application::configure(basePath: $basePath)
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();

$app->usePublicPath($publicPath);
$app->instance('path.public', $publicPath);

return $app;
