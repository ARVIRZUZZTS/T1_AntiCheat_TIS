<?php

/**
 * @file    ListarCursosAuxiliarService.php
 * @author  Alisson D. Alvarado <alvaradoalissondalet@gmail.com>
 * @created 2026-11-24
 * @updated 2026-11-24
 *
 * @description
 * Prepara los cursos asignados al auxiliar para la vista compartida de cursos.
 *
 * @changelog
 * - 2026-11-24  [Alisson D. Alvarado]  feat: listar cursos asignados al auxiliar.
 */

namespace App\Services\Auxiliar;

use App\Services\Curso\ListarCursosDocenteService;
use App\Services\Curso\ListarMateriasConMetricasService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/** Cursos asignados con el mismo formato que la lista del docente. */
class ListarCursosAuxiliarService
{
    /**
     * @param  int  $idAuxiliar  Identificador del auxiliar autenticado.
     * @return Collection  Cursos asignados con gestión y métricas.
     * @author Alisson D. Alvarado <alvaradoalissondalet@gmail.com>
     * @since 2026-11-24
     */
    public function ejecutar(int $idAuxiliar): Collection
    {
        $ids = DB::table('auxiliar_curso')
            ->where('id_auxiliar', $idAuxiliar)
            ->where('estado', AccesoAuxiliarService::ESTADO_ACTIVO)
            ->pluck('id_curso')->map(fn ($id): int => (int) $id)->unique()->values()->all();

        // El servicio de métricas sin IDs listaría todos los cursos.
        if ($ids === []) {
            return collect();
        }

        $gestiones = app(ListarCursosDocenteService::class)->gestionesPorCurso($ids);

        return app(ListarMateriasConMetricasService::class)->ejecutar($ids, true)
            ->map(fn (array $fila): array => $fila + ['gestion' => $gestiones->get($fila['curso']->id_curso)])
            ->sortBy(fn (array $fila): array => [
                $fila['curso']->estado === 'EnCurso' ? 0 : 1,
                -(int) $fila['curso']->id_curso,
            ])->values();
    }
}
