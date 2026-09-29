<?php

/**
 * @file    EstadoIncidencia.php
 *
 * @author  Diego Tejerina <josediegotejerinamolina@gmail.com>
 *
 * @created 2026-09-24
 *
 * @updated 2026-09-24
 *
 * @description
 * Enum de dominio del estado de una incidencia registrada en la central de
 * riesgos (columna `estado_incidencia` de `central_riesgo`).
 *
 * Los valores van con mayúscula inicial porque ese es el texto exacto que
 * espera el ENUM de PostgreSQL (`CREATE TYPE estado_incidencia`).
 *
 * @changelog
 * - 2026-09-24  [T1]  feat: creación inicial del enum.
 */

namespace App\Enums;

enum EstadoIncidencia: string
{
    case Confirmado = 'Confirmado';
    case Pendiente = 'Pendiente';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Confirmado => 'Confirmado',
            self::Pendiente => 'Pendiente',
        };
    }
}
