<?php

/**
 * @file    Rol.php
 * @author  Valery D. Ortuno P. <valerydariana98@gmail.com>
 * @created 2026-09-25
 * @updated 2026-09-25
 *
 * @description
 * Roles del personal que opera el sistema durante un examen. El valor de cada
 * caso es el texto que se muestra en la interfaz.
 *
 * @changelog
 * - 2026-09-25  [Valery D. Ortuno P]  feat: creación inicial del enum de roles.
 */

namespace App\Models;

/**
 * Identifica a la persona que registra el ingreso de un estudiante o una
 * incidencia en la central de riesgos.
 *
 * @package  App\Models
 * @author   Valery D. Ortuno P. <valerydariana98@gmail.com>
 * @since    2026-09-25
 *
 * @see  EstadoIncidencia
 */
enum Rol: string
{
    /**
     * Registra la incidencia, pero la deja a la espera de revisión del docente.
     */
    case AUXILIAR = 'Auxiliar';

    /**
     * Registra la incidencia y la confirma con su propia firma.
     */
    case DOCENTE = 'Docente';
}
