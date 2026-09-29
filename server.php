<?php

/**
 * Laravel - A PHP Framework For Web Artisans
 *
 * @pachage Laravel
 *
 * @author David Chavez <virzuzz12345@gmail.com>
 * http://techone.tis.cs.umss.edu.bo/phppgadmin
 *
 * Doc root segun el entorno, sin tener que descomentar lineas:
 * - servidor UMSS: el hosting obliga a que se llame `public_html`
 * - local: la carpeta normal de Laravel, `public`
 * Se detecta sola. El orden importa: `public_html` se prueba primero, asi que
 * si un dia existe la carpeta en tu maquina sigue ganndole al servidor.
 */
$publicPath = __DIR__.'/public_html';

if (! is_dir($publicPath)) {
    $publicPath = __DIR__.'/public';
}

$uri = urldecode(
    parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)
);

if ($uri !== '/' && file_exists($publicPath.$uri)) {
    return false;
}

require_once $publicPath.'/index.php';
