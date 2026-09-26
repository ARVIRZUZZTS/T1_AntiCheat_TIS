<?php

/**
 * @file    EstadoIncidencia.php
 * @author  Valery D. Ortuno P. <valerydariana98@gmail.com>
 * @created 2026-09-25
 * @updated 2026-09-25
 *
 * @description
 * Estado con el que una incidencia entra a la central de riesgos. El auxiliar
 * deja el registro pendiente de revisión y el docente lo confirma, por eso el
 * estado se deriva del rol y no se elige a mano en el formulario.
 *
 * @changelog
 * - 2026-09-25  [Valery D. Ortuno P]  feat: creación inicial del enum de estados.
 */

namespace App\Models;

/**
 * Estado de un registro de la central de riesgos.
 *
 * @package  App\Models
 * @author   Valery D. Ortuno P. <valerydariana98@gmail.com>
 * @since    2026-09-25
 *
 * @see  Rol
 */
enum EstadoIncidencia: string
{
    /**
     * Registrado por un auxiliar, pendiente de revisión del docente.
     */
    case SOSPECHOSO = 'Sospechoso';

    /**
     * Registrado por un docente, con la incidencia confirmada.
     */
    case CONFIRMADO = 'Confirmado';

    /**
     * Resuelve el estado que corresponde según el rol de quien registra.
     *
     * @param  Rol  $rol  Rol del usuario que está registrando la incidencia.
     *
     * @return EstadoIncidencia  Estado con el que ingresa el registro.
     *
     * @author Valery D. Ortuno P. <valerydariana98@gmail.com>
     * @since  2026-09-25
     */
    public static function desdeRol(Rol $rol): self
    {
        return match ($rol) {
            Rol::AUXILIAR => self::SOSPECHOSO,
            Rol::DOCENTE => self::CONFIRMADO,
        };
    }
}
