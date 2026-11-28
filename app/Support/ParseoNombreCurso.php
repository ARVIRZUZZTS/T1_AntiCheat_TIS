<?php

/**
 * @file    ParseoNombreCurso.php
 *
 * @author  Diego Tejerina <josediegotejerinamolina@gmail.com>
 *
 * @created 2026-11-28
 *
 * @description
 * Interpreta un `nombre_curso` heredado (texto libre) para separar la materia
 * del número de grupo, en la migración de datos. Tolera los formatos que
 * aparecen en la base: "Materia - Grupo 2", "Materia Grupo 2", "Materia G1",
 * "Materia - 2". Si no se reconoce un grupo, devuelve la materia completa y
 * grupo null (se reporta para revisión manual). No modifica tildes ni
 * mayúsculas de la materia: la comparación con el catálogo se hace aparte, con
 * Validadores::normalizarNombreMateria.
 *
 * Es una utilidad pura de formato, sin lógica de negocio (arquitectura.md).
 *
 * @changelog
 * - 2026-11-28  [Diego Tejerina]  feat: creación inicial.
 *
 * @see  App\Support\NombreCurso
 * @see  App\Console\Commands\AsignarMateriaGrupoCursos
 */

namespace App\Support;

final class ParseoNombreCurso
{
    /** Patrones de grupo al final del nombre, del más específico al más general. */
    private const PATRONES = [
        '/\s*[-–—]?\s*grupo\s*0*([0-9]{1,2})\s*$/iu',
        '/\s*[-–—]?\s*gr?\s*0*([0-9]{1,2})\s*$/iu',
        '/\s*[-–—]\s*0*([0-9]{1,2})\s*$/u',
    ];

    /**
     * Separa el nombre en materia y número de grupo.
     *
     * @param  string  $nombreCurso  Nombre libre del curso.
     *
     * @return array{materia: string, grupo: ?int}  Materia (texto) y grupo (1-10) o null.
     */
    public static function interpretar(string $nombreCurso): array
    {
        $nombre = trim($nombreCurso);

        foreach (self::PATRONES as $patron) {
            if (preg_match($patron, $nombre, $coincidencia) === 1) {
                $grupo = (int) $coincidencia[1];
                $materia = trim((string) preg_replace($patron, '', $nombre));
                // La materia no puede quedar vacía (p. ej. un nombre que es solo "Grupo 2").
                if ($materia !== '' && $grupo >= 1 && $grupo <= 10) {
                    return ['materia' => $materia, 'grupo' => $grupo];
                }
            }
        }

        return ['materia' => $nombre, 'grupo' => null];
    }
}
