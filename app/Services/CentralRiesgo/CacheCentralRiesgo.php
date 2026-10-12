<?php

/**
 * @file    CacheCentralRiesgo.php
 *
 * @author  Valery D. Ortuno P. <valerydariana98@gmail.com>
 *
 * @created 2026-10-11
 *
 * @updated 2026-10-11
 *
 * @description
 * Caché corta de la central de riesgos. La base vive en Supabase y cada viaje
 * de red pesa (~1.2 s de handshake + ~0.4 s por consulta), así que la lista y
 * el detalle se guardan en la caché local para que las interacciones repetidas
 * (abrir/cerrar el detalle, repetir una búsqueda) no toquen la base.
 *
 * La frescura no depende del TTL sino de una versión: cada vez que se escribe
 * una incidencia (`CentralRiesgo` dispara `saved`/`deleted`) la versión sube y
 * las claves anteriores dejan de usarse. Los TTL quedan como red de seguridad
 * por si alguna escritura no pasara por Eloquent.
 *
 * @see  App\Livewire\CentralRiesgo\ListaEstudiantes
 * @see  App\Models\CentralRiesgo
 *
 * @changelog
 * - 2026-10-11  [Valery D. Ortuno P]  feat: creación inicial: lista y detalle
 *   cacheados con invalidación por versión.
 */

namespace App\Services\CentralRiesgo;

use Illuminate\Support\Facades\Cache;

class CacheCentralRiesgo
{
    /** Clave de la versión vigente. */
    private const CLAVE_VERSION = 'central-riesgo:version';

    /** TTL de la lista, en segundos (red de seguridad). */
    private const TTL_LISTA = 300;

    /** TTL del detalle, en segundos (red de seguridad). */
    private const TTL_DETALLE = 600;

    /** Versión vigente: cambia cada vez que se escribe una incidencia. */
    public static function version(): int
    {
        return (int) Cache::get(self::CLAVE_VERSION, 1);
    }

    /** Invalida la lista y los detalles ya cacheados. */
    public static function invalidar(): void
    {
        Cache::forever(self::CLAVE_VERSION, self::version() + 1);
    }

    /**
     * Clave de la lista para el usuario, el alcance, la búsqueda y el orden
     * vigentes (la página se corta después, así que no forma parte de la clave).
     */
    public static function claveLista(int $usuario, string $alcance, ?string $busqueda, bool $ordenAlfabetico): string
    {
        return sprintf(
            'central-riesgo:lista:v%d:%d:%s:%s:%d',
            self::version(),
            $usuario,
            $alcance,
            (string) $busqueda,
            $ordenAlfabetico ? 1 : 0,
        );
    }

    /** Clave del detalle de una incidencia. */
    public static function claveDetalle(int $idRegistro): string
    {
        return sprintf('central-riesgo:detalle:v%d:%d', self::version(), $idRegistro);
    }

    public static function ttlLista(): int
    {
        return self::TTL_LISTA;
    }

    public static function ttlDetalle(): int
    {
        return self::TTL_DETALLE;
    }
}
