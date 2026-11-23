<?php

/**
 * @file    registro.php
 *
 * @author  Diego Tejerina <josediegotejerinamolina@gmail.com>
 *
 * @created 2026-11-23
 *
 * @description
 * Parametros del registro de docentes (HU 27). Define que correos son
 * elegibles para la verificacion automatica por correo electronico: los
 * dominios institucionales y los correos de prueba puntuales. El resto de
 * dominios queda para la validacion manual de dos docentes (HU 26).
 *
 * Los valores vienen de `.env` como listas separadas por comas y se entregan
 * ya normalizados (sin espacios, en minusculas y sin vacios), para que el
 * dominio no tenga que volver a limpiarlos.
 *
 * @changelog
 * - 2026-11-23  [Diego Tejerina]  feat: creacion inicial (HU 27).
 *
 * @see  App\Services\Auth\VerificarCorreoInstitucionalService
 */

/**
 * Convierte una lista separada por comas en un arreglo limpio.
 *
 * @param  string|null  $lista  Valor crudo de `.env`.
 * @return list<string>  Elementos en minusculas, sin espacios ni vacios.
 */
$listaLimpia = static function (?string $lista): array {
    $elementos = array_map(
        static fn (string $elemento): string => mb_strtolower(trim($elemento)),
        explode(',', (string) $lista),
    );

    return array_values(array_filter($elementos, static fn (string $elemento): bool => $elemento !== ''));
};

return [

    /*
    |--------------------------------------------------------------------------
    | Dominios institucionales
    |--------------------------------------------------------------------------
    |
    | Un correo cuyo dominio termina en alguno de estos se verifica por correo
    | electronico (la cuenta se activa al confirmar el enlace, sin pasar por la
    | validacion manual de dos docentes).
    |
    */

    'dominios_institucionales' => $listaLimpia(env('REGISTRO_DOMINIOS_INSTITUCIONALES')),

    /*
    |--------------------------------------------------------------------------
    | Correos de prueba
    |--------------------------------------------------------------------------
    |
    | Correos puntuales que, aunque no sean de un dominio institucional, se
    | tratan como institucionales para poder probar el envio real de correos.
    |
    */

    'correos_prueba' => $listaLimpia(env('REGISTRO_CORREOS_PRUEBA')),

];
