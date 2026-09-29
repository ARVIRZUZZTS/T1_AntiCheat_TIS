<?php

/**
 * @file    TipoInfraccion.php
 *
 * @author  Diego Tejerina <josediegotejerinamolina@gmail.com>
 *
 * @created 2026-09-24
 *
 * @updated 2026-09-29
 *
 * @description
 * Enum de dominio del tipo de infracción registrado en la central de riesgos
 * (columna `tipo_infraccion` de `central_riesgo`).
 *
 * @changelog
 * - 2026-09-24  [T1]  feat: creación inicial del enum.
 * - 2026-09-29  [Valery D. Ortuno P]  fix: el enum queda solo en `tramposo` y
 *   `sospechoso`, igual que el tipo `tipo_infraccion` de la base de datos; el
 *   motivo de la incidencia pasa a la tabla `motivo` (#70).
 */

namespace App\Enums;

enum TipoInfraccion: string
{
    case Tramposo = 'tramposo';
    case Sospechoso = 'sospechoso';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Tramposo => 'Tramposo',
            self::Sospechoso => 'Sospechoso',
        };
    }
}
