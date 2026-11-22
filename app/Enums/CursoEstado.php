<?php

/**
 * @file    CursoEstado.php
 *
 * @author  David E. Chavez T. <virzuzz12345@gmail.com>
 *
 * @created 2026-10-11
 *
 * @updated 2026-11-20
 *
 * @description
 * Enum de dominio del estado de un curso (columna `estado` de `curso`).
 *
 * `EnCurso` cuenta como materia activa a la hora de repartir los avisos de
 * la central de riesgos (HU 6 · T1). `Finalizo` indica que el curso ya concluyó
 * por su calendario/gestión. `Archivado` es un tercer estado independiente
 * (HU #321 · Task #323): el docente lo archiva manualmente y el curso queda en
 * solo lectura, conservando su historial.
 *
 * Los valores van con mayúscula inicial porque ese es el texto exacto que
 * espera el ENUM de PostgreSQL (`CREATE TYPE curso_estado`).
 *
 * @changelog
 * - 2026-10-11  [David E. Chavez T.]  feat: creación inicial del enum (#HU6).
 * - 2026-11-20  [Equipo T1]  feat: agrega estado `Archivado` (HU #321 · Task #323).
 */

namespace App\Enums;

enum CursoEstado: string
{
    case EnCurso = 'EnCurso';
    case Finalizo = 'Finalizo';
    case Archivado = 'Archivado';

    public function etiqueta(): string
    {
        return match ($this) {
            self::EnCurso => 'En curso',
            self::Finalizo => 'Finalizó',
            self::Archivado => 'Archivado',
        };
    }
}
