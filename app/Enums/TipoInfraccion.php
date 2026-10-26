<?php

/**
 * @file    TipoInfraccion.php
 *
 * @author  Diego Tejerina <josediegotejerinamolina@gmail.com>
 *
* @created 2026-09-24
 *
 * @updated 2026-10-10
 * @updated 2026-10-26
 *
 * @description
 * Enum de dominio con el tipo de infracción registrado en la central de riesgos
 * (columna `tipo_infraccion` de `central_riesgo`).
 *
 * Solo dos casos: son los únicos valores del enum `tipo_infraccion` de la base.
 * `AulaEquivocada` y `Pendiente` se quitan porque la base no los tiene y
 * escribirlos en una incidencia la rechazaba.
 *
 * El tipo también es el estado de revisión de la incidencia (estado()):
 * tramposo = confirmado, sospechoso = pendiente de revisión.
 *
 * @changelog
 * - 2026-09-24  [T1]  feat: creación inicial del enum.
 * - 2026-09-29  [Valery D. Ortuno P]  fix: el enum queda solo en `tramposo` y
 *   `sospechoso`, igual que el tipo `tipo_infraccion` de la base de datos; el
 *   motivo de la incidencia pasa a la tabla `motivo` (#70).
 * - 2026-10-10  [Alex Candia]  fix: se eliminan `AulaEquivocada` y `Pendiente`,
 *   que quedaron en el enum pero nunca estuvieron en el enum de PostgreSQL. El
 *   tipo `tipo_infraccion` solo admite `tramposo` y `sospechoso`.
 * - 2026-10-26  [Valery D. Ortuno P]  refactor: agrega estado(), que reemplaza
 *   a la columna `estado_incidencia` y al enum EstadoIncidencia, eliminados.
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

    /**
     * Estado de revisión que representa el tipo: un tramposo es un caso
     * confirmado y un sospechoso uno pendiente de revisión.
     *
     * @return string 'Confirmado' o 'Pendiente'.
     */
    public function estado(): string
    {
        return match ($this) {
            self::Tramposo => 'Confirmado',
            self::Sospechoso => 'Pendiente',
        };
    }
}
