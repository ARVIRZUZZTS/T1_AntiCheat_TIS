<?php

/**
 * @file    EstadoEstudianteExamen.php
 *
 * @author  Equipo T1 <dev@techone.local>
 *
 * @created 2026-09-24
 *
 * @updated 2026-09-24
 *
 * @description
 * Enum de dominio del estado de habilitación de un estudiante en un examen
 * (columna `estado` de `estudiante_examen`).
 *
 * @changelog
 * - 2026-09-24  [T1]  feat: creación inicial del enum.
 */

namespace App\Enums;

enum EstadoEstudianteExamen: string
{
    case Habilitado = 'habilitado';
    case Deshabilitado = 'deshabilitado';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Habilitado => 'Habilitado',
            self::Deshabilitado => 'Deshabilitado',
        };
    }
}
