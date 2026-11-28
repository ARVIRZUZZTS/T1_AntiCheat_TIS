<?php

/**
 * @file    DatosNuevoAuxiliar.php
 *
 * @author  Diego Tejerina <josediegotejerinamolina@gmail.com>
 *
 * @created 2026-11-27
 *
 * @description
 * Datos ya normalizados para dar de alta un auxiliar. El `tipoAuxiliar` decide
 * si lleva código SIS: 'UMSS' lo exige (identidad y unicidad), 'externo' no. El
 * carnet (cédula) es opcional para todos y es el documento que alimenta la
 * contraseña. `puedeCrearAuxiliares` solo lo puede pedir un docente.
 *
 * @changelog
 * - 2026-11-27  [Diego Tejerina]  feat: creación inicial (alta de auxiliar).
 *
 * @see  App\Services\Auxiliar\CrearAuxiliarService
 */

namespace App\Support\DTO;

final readonly class DatosNuevoAuxiliar
{
    public const TIPO_UMSS = 'UMSS';

    public const TIPO_EXTERNO = 'externo';

    /**
     * @param  string  $nombre               Nombre(s) del auxiliar.
     * @param  string  $apellido             Apellido(s) del auxiliar.
     * @param  string  $tipoAuxiliar         'UMSS' o 'externo'.
     * @param  string  $codSis               Código SIS (solo tipo UMSS); vacío en externo.
     * @param  string  $carnet               Carnet/CI opcional (cualquier tipo); puede ir vacío.
     * @param  string  $correo               Correo del auxiliar (su usuario de acceso).
     * @param  bool    $puedeCrearAuxiliares  Permiso de coordinador solicitado.
     */
    public function __construct(
        public string $nombre,
        public string $apellido,
        public string $tipoAuxiliar,
        public string $codSis,
        public string $carnet,
        public string $correo,
        public bool $puedeCrearAuxiliares,
    ) {
    }

    /** Si el auxiliar es de la UMSS (tiene código SIS). */
    public function esUmss(): bool
    {
        return $this->tipoAuxiliar === self::TIPO_UMSS;
    }

    /**
     * Documento que alimenta la contraseña: el carnet (cédula) para cualquier
     * tipo, o cadena vacía si no se ingresó (entonces la contraseña es aleatoria).
     */
    public function documento(): string
    {
        return $this->carnet;
    }
}
