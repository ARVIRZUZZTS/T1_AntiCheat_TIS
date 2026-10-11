<?php

/**
 * @file    TipoInfraccion.php
 *
 * @author  Diego Tejerina <josediegotejerinamolina@gmail.com>
 *
* @created 2026-09-24
 *
 * @updated 2026-10-10
 *
 * @description
 * Enum de dominio con el tipo de infracción registrado en la central de riesgos
 * (columna `tipo_infraccion` de `central_riesgo`).
 *
 * Solo dos casos: son los únicos valores del enum `tipo_infraccion` de la base.
 * `AulaEquivocada` y `Pendiente` se quitan porque la base no los tiene y
 * escribirlos en una incidencia la rechazaba.
 *
 * @changelog
 * - 2026-09-24  [T1]  feat: creación inicial del enum.
 * - 2026-09-29  [Valery D. Ortuno P]  fix: el enum queda solo en `tramposo` y
 *   `sospechoso`, igual que el tipo `tipo_infraccion` de la base de datos; el
 *   motivo de la incidencia pasa a la tabla `motivo` (#70).
 * - 2026-10-10  [Alex Candia]  fix: se eliminan `AulaEquivocada` y `Pendiente`,
 *   que quedaron en el enum pero nunca estuvieron en el enum de PostgreSQL. El
 *   tipo `tipo_infraccion` solo admite `tramposo` y `sospechoso`.
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
