<?php

/**
 * @file    CursoEstado.php
 *
 * @author  David E. Chavez T. <virzuzz12345@gmail.com>
 *
 * @created 2026-10-11
 *
 * @description
 * Enum de dominio del estado de un curso (columna `estado` de `curso`).
 *
 * Solo `EnCurso` cuenta como materia activa a la hora de repartir los avisos de
 * la central de riesgos (HU 6 · T1): un curso `Finalizo` ya no tiene docencia
 * vigente, así que su docente no recibe notificaciones de nuevos ingresos.
 *
 * Los valores van con mayúscula inicial porque ese es el texto exacto que
 * espera el ENUM de PostgreSQL (`CREATE TYPE curso_estado`).
 *
 * @changelog
 * - 2026-10-11  [David E. Chavez T.]  feat: creación inicial del enum (#HU6).
 */

namespace App\Enums;

enum CursoEstado: string
{
    case EnCurso = 'EnCurso';
    case Finalizo = 'Finalizo';

    public function etiqueta(): string
    {
        return match ($this) {
            self::EnCurso => 'En curso',
            self::Finalizo => 'Finalizó',
        };
    }
}
