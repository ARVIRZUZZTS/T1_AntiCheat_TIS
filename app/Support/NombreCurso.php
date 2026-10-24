<?php

/**
 * @file    NombreCurso.php
 *
 * @author  Valery D. Ortuno P <valerydariana98@gmail.com>
 *
 * @created 2026-10-24
 *
 * @description
 * Separa el nombre de un curso en materia y grupo. Crear curso guarda el
 * nombre como "{materia} - Grupo {grupo}" (ver StoreCursoRequest), así que
 * se puede volver a partir donde una pantalla necesita solo la materia. Un
 * nombre que no sigue ese formato (cursos cargados antes de Crear curso) se
 * toma entero como materia y sin grupo.
 *
 * Es una utilidad pura de formato, sin lógica de negocio (ver arquitectura.md,
 * sección Helpers).
 *
 * @changelog
 * - 2026-10-24  [Valery D. Ortuno P]  feat: creación inicial.
 *
 * @see  App\Http\Requests\StoreCursoRequest
 */

namespace App\Support;

final class NombreCurso
{
    private const SEPARADOR = ' - Grupo ';

    /**
     * Materia del curso: "Calculo I - Grupo 2" -> "Calculo I".
     *
     * @param  string  $nombreCurso  Nombre completo del curso.
     *
     * @return string  La materia, o el nombre entero si no trae grupo.
     *
     * @author Valery D. Ortuno P <valerydariana98@gmail.com>
     * @since  2026-10-24
     */
    public static function materia(string $nombreCurso): string
    {
        $posicion = mb_strrpos($nombreCurso, self::SEPARADOR);

        return $posicion === false ? $nombreCurso : mb_substr($nombreCurso, 0, $posicion);
    }

    /**
     * Grupo del curso: "Calculo I - Grupo 2" -> "2".
     *
     * @param  string  $nombreCurso  Nombre completo del curso.
     *
     * @return ?string  El grupo, o null si el nombre no trae grupo.
     *
     * @author Valery D. Ortuno P <valerydariana98@gmail.com>
     * @since  2026-10-24
     */
    public static function grupo(string $nombreCurso): ?string
    {
        $posicion = mb_strrpos($nombreCurso, self::SEPARADOR);

        return $posicion === false
            ? null
            : mb_substr($nombreCurso, $posicion + mb_strlen(self::SEPARADOR));
    }
}
