<?php
/**
 * @file    RegistroIncidenciaDTO.php
 *
 * @author  Valery D. Ortuno P <valerydariana98@gmail.com>
 *
 * @created 2026-10-25
 *
 * @description
 * Datos de una incidencia tal como llegan de los modales del monitor en vivo
 * (registro rápido de ingreso o de monitor, y estudiante no inscrito), antes de
 * validarlos. `nombre` y `apellido` solo se usan si el SIS no existe en la base.
 *
 * @changelog
 * - 2026-10-25  [Valery D. Ortuno P]  feat: creación inicial del DTO (HU 5).
 *
 * @see  App\Services\CentralRiesgo\RegistrarIncidenciaService
 */

namespace App\Support\DTO;

readonly class RegistroIncidenciaDTO
{
    /** Origen: registro del ingreso al examen. */
    public const ORIGEN_INGRESO = 'ingreso';

    /** Origen: durante el examen (vista Monitor). */
    public const ORIGEN_MONITOR = 'monitor';

    public function __construct(
        public string $sis,
        public int $idExamen,
        public string $origen,
        public string $motivo,
        public ?string $detalle = null,
        public ?string $nombre = null,
        public ?string $apellido = null,
    ) {}
}
