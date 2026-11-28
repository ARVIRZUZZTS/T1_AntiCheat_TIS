<?php

/**
 * @file    DocenteMateriaService.php
 *
 * @author  Diego Tejerina <josediegotejerinamolina@gmail.com>
 *
 * @created 2026-11-28
 *
 * @description
 * Relación docente ↔ materia. Lo usa Crear curso: muestra primero las materias
 * del docente y, al crear un curso, agrega la materia a su relación. No hay tope
 * de materias por docente.
 *
 * @changelog
 * - 2026-11-28  [Diego Tejerina]  feat: creación inicial.
 * - 2026-11-28  [Diego Tejerina]  feat: se quita el tope de 5 materias por
 *   docente (un docente puede dictar más).
 *
 * @see  database/migrations/2026_11_28_000003_crear_docente_materia.php
 */

namespace App\Services\Docente;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DocenteMateriaService
{
    /** Ids de las materias del docente. @return Collection<int, int> */
    public function materiasDe(int $idDocente): Collection
    {
        return DB::table('docente_materia')
            ->where('id_docente', $idDocente)
            ->pluck('id_materia')
            ->map(fn (mixed $id): int => (int) $id);
    }

    /** Si el docente ya tiene esa materia. */
    public function tiene(int $idDocente, int $idMateria): bool
    {
        return DB::table('docente_materia')
            ->where('id_docente', $idDocente)
            ->where('id_materia', $idMateria)
            ->exists();
    }

    /**
     * Agrega la materia al docente si aún no la tiene. Idempotente: si ya la
     * tiene, no hace nada. No hay tope de materias por docente.
     */
    public function agregar(int $idDocente, int $idMateria): void
    {
        DB::table('docente_materia')->insertOrIgnore([
            'id_docente' => $idDocente,
            'id_materia' => $idMateria,
        ]);
    }
}
