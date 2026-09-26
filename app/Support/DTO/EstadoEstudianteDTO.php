<?php
/**
 * @file    EstadoEstudianteDTO.php
 *
 * @author  Alex Candia <alex.leonar.candia@gmail.com>
 *
 * @created 2026-09-26
 *
 * @updated 2026-09-26
 *
 * @description
 * DTO del servicio para consultar el estado de un estudiante para el registro de asistencia en un examen.
 *
 * @changelog
 * - 2026-09-26  [T1]  feat: creación inicial del DTO.
 */

namespace App\Support\DTO;

use App\Enums\TipoEstadoRegistro;
use App\Enums\TipoInfraccion;
use App\Models\Examen;

readonly class EstadoEstudianteDTO
{
    public function __construct(
        public TipoEstadoRegistro $tipo,
        public string $sisEstudiante,
        public string $nombreCompleto,
        public ?string $motivo = null,
        public ?string $fecha = null,
        public ?TipoInfraccion $tipoInfraccion = null,
        public ?Examen $examen = null,
        public bool $yaRegistro = false,
        public ?string $horaRegistro = null,
    ) {}

    public static function inhabilitado(
        string $sis,
        string $nombre,
        ?string $motivo,
    ): self {
        return new self(
            tipo: TipoEstadoRegistro::INHABILITADO,
            sisEstudiante: $sis,
            nombreCompleto: $nombre,
            motivo: $motivo,
        );
    }

    public static function enRiesgo(
        string $sis,
        string $nombre,
        ?string $motivo,
        ?string $fecha,
        TipoInfraccion $tipoInfraccion,
    ): self {
        return new self(
            tipo: TipoEstadoRegistro::EN_RIESGO,
            sisEstudiante: $sis,
            nombreCompleto: $nombre,
            motivo: $motivo,
            fecha: $fecha,
            tipoInfraccion: $tipoInfraccion,
        );
    }

    public static function normal(
        string $sis,
        string $nombre,
        Examen $examen,
        bool $yaRegistro,
        ?string $horaRegistro,
    ): self {
        return new self(
            tipo: TipoEstadoRegistro::NORMAL,
            sisEstudiante: $sis,
            nombreCompleto: $nombre,
            examen: $examen,
            yaRegistro: $yaRegistro,
            horaRegistro: $horaRegistro,
        );
    }

    public static function sinRegistro(string $sis, string $nombre): self
    {
        return new self(
            tipo: TipoEstadoRegistro::SIN_REGISTRO,
            sisEstudiante: $sis,
            nombreCompleto: $nombre,
        );
    }

    public function isNormal(): bool
    {
        return $this->tipo === TipoEstadoRegistro::NORMAL;
    }

    public function permiteRegistrarIngreso(): bool
    {
        return $this->isNormal() && !$this->yaRegistro;
    }
}