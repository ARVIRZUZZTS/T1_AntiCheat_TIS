<?php

/**
 * @file    ListarMateriasSelectorService.php
 *
 * @author  Diego Tejerina <josediegotejerinamolina@gmail.com>
 *
 * @created 2026-11-28
 *
 * @description
 * Arma el catálogo de materias para el buscador de "Crear curso": todas las
 * materias con su código y semestre, marcando las del docente para mostrarlas
 * primero (una sola consulta a `materia` y una a `docente_materia`, sin N+1).
 *
 * @changelog
 * - 2026-11-28  [Diego Tejerina]  feat: creación inicial.
 *
 * @see  App\Services\Docente\DocenteMateriaService
 * @see  resources/views/components/ui/selector-materia.blade.php
 */

namespace App\Services\Curso;

use App\Models\Materia;
use App\Services\Docente\DocenteMateriaService;

class ListarMateriasSelectorService
{
    public function __construct(
        private readonly DocenteMateriaService $docenteMaterias,
    ) {}

    /**
     * @param  int  $idDocente  Docente autenticado.
     * @return list<array{id: int, codigo: ?string, nombre: string, semestre: ?int, delDocente: bool}>
     */
    public function ejecutar(int $idDocente): array
    {
        $delDocente = $this->docenteMaterias->materiasDe($idDocente)->flip();

        return Materia::query()
            ->orderByRaw('semestre is null')
            ->orderBy('semestre')
            ->orderBy('nombre')
            ->get(['id_materia', 'codigo', 'nombre', 'semestre'])
            ->map(fn (Materia $materia): array => [
                'id' => (int) $materia->id_materia,
                'codigo' => $materia->codigo,
                'nombre' => (string) $materia->nombre,
                'semestre' => $materia->semestre !== null ? (int) $materia->semestre : null,
                'delDocente' => $delDocente->has((int) $materia->id_materia),
            ])
            ->sortBy(fn (array $m): string => ($m['delDocente'] ? '0' : '1')
                .' '.str_pad((string) ($m['semestre'] ?? 99), 2, '0', STR_PAD_LEFT)
                .' '.mb_strtolower($m['nombre']))
            ->values()
            ->all();
    }
}
