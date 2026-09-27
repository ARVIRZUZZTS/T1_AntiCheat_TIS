<?php
/**
 * @file    TipoEstadoRegistro.php
 *
 * @author  Alex Candia <alex.leonar.candia@gmail.com>
 *
 * @created 2026-09-26
 *
 * @updated 2026-09-26
 *
 * @description
 * Enum del DTO `EstadoEstudianteDTO` que representa el estado de un estudiante en un examen.
 *
 * @changelog
 * - 2026-09-26  [T1]  feat: creación inicial del enum.
 */
namespace App\Enums;

enum TipoEstadoRegistro: string
{
    case INHABILITADO = 'inhabilitado';
    case EN_RIESGO = 'en_riesgo';
    case NORMAL = 'normal';
    case SIN_REGISTRO = 'sin_registro';
}