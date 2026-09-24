<?php

/**
 * @file    TipoInfraccion.php
 *
 * @author  Equipo T1 <dev@techone.local>
 *
 * @created 2026-09-24
 *
 * @updated 2026-09-24
 *
 * @description
 * Enum de dominio del tipo de infracción registrado en la central de riesgos
 * (columna `tipo_infraccion` de `central_riesgo`).
 *
 * @changelog
 * - 2026-09-24  [T1]  feat: creación inicial del enum.
 */

namespace App\Enums;

enum TipoInfraccion: string
{
    case Tramposo = 'tramposo';
    case Sospechoso = 'sospechoso';
    case Pendiente = 'pendiente';
    case AulaEquivocada = 'aula equivocada';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Tramposo => 'Tramposo',
            self::Sospechoso => 'Sospechoso',
            self::Pendiente => 'Pendiente',
            self::AulaEquivocada => 'Aula equivocada',
        };
    }
}
