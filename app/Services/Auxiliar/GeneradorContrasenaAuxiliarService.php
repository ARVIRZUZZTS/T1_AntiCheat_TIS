<?php

/**
 * @file    GeneradorContrasenaAuxiliarService.php
 *
 * @author  Diego Tejerina <josediegotejerinamolina@gmail.com>
 *
 * @created 2026-11-27
 *
 * @description
 * Genera la contraseña en claro de un auxiliar. La diferencia con el docente es
 * el prefijo `AUX-`.
 *
 *  - Con documento (código SIS o carnet): `AUX-` + iniciales de las dos
 *    primeras palabras del nombre + el documento + iniciales de las dos
 *    primeras palabras del apellido, todo en mayúsculas. Si un campo tiene una
 *    sola palabra, su inicial se repite. Ej.: Manual Jorge / Camacho Flores /
 *    9479386 → `AUX-MJ9479386CF`.
 *  - Sin documento (externo sin carnet): `AUX-` + 10 caracteres aleatorios.
 *
 * Las tildes y la eñe se normalizan (Á→A, Ñ→N) y los apóstrofos o guiones se
 * ignoran al sacar la inicial. La contraseña se entrega en claro para enviarla
 * por correo; quien la persiste la guarda con hash y nunca se muestra.
 *
 * @changelog
 * - 2026-11-27  [Diego Tejerina]  feat: creación inicial (regla AUX-).
 *
 * @see  App\Services\Auxiliar\CrearAuxiliarService
 */

namespace App\Services\Auxiliar;

use Illuminate\Support\Str;

class GeneradorContrasenaAuxiliarService
{
    /** Prefijo que distingue la contraseña de un auxiliar de la de un docente. */
    public const PREFIJO = 'AUX-';

    /** Longitud de la parte aleatoria cuando el auxiliar no tiene documento. */
    private const LARGO_ALEATORIO = 10;

    /**
     * Devuelve la contraseña en claro del auxiliar.
     *
     * @param  string       $nombre     Nombre(s) del auxiliar.
     * @param  string       $apellido   Apellido(s) del auxiliar.
     * @param  string|null  $documento  Código SIS o carnet; null o vacío si es externo sin carnet.
     *
     * @return string  La contraseña en claro (empieza con `AUX-`).
     *
     * @author Diego Tejerina <josediegotejerinamolina@gmail.com>
     * @since  2026-11-27
     */
    public function generar(string $nombre, string $apellido, ?string $documento = null): string
    {
        $documento = trim((string) $documento);

        if ($documento === '') {
            return self::PREFIJO.$this->aleatoria();
        }

        return self::PREFIJO.$this->iniciales($nombre).$documento.$this->iniciales($apellido);
    }

    /**
     * Iniciales en mayúsculas de las dos primeras palabras; si hay una sola,
     * repite su inicial.
     */
    private function iniciales(string $texto): string
    {
        $palabras = array_values(array_filter(preg_split('/\s+/u', trim($texto)) ?: []));

        if ($palabras === []) {
            return '';
        }

        $primera = $this->inicial($palabras[0]);
        $segunda = isset($palabras[1]) ? $this->inicial($palabras[1]) : $primera;

        return $primera.$segunda;
    }

    /**
     * Primera letra A–Z de la palabra, normalizando tildes/ñ (Á→A, Ñ→N) e
     * ignorando apóstrofos, guiones u otros símbolos.
     */
    private function inicial(string $palabra): string
    {
        $ascii = Str::ascii($palabra);

        return preg_match('/[A-Za-z]/', $ascii, $coincidencia) === 1
            ? strtoupper($coincidencia[0])
            : '';
    }

    /** Cadena aleatoria alfanumérica de longitud fija. */
    private function aleatoria(): string
    {
        return Str::random(self::LARGO_ALEATORIO);
    }
}
