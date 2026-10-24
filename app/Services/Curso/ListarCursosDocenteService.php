<?php

/**
 * @file    ListarCursosDocenteService.php
 *
 * @author  Valery D. Ortuno P <valerydariana98@gmail.com>
 *
 * @created 2026-10-24
 *
 * @description
 * Lista los cursos de un docente para las secciones Cursos y Exámenes.
 *
 * - `conMetricas()`: todos sus cursos con las métricas de
 *   ListarMateriasConMetricasService, primero los activos y después los
 *   finalizados (cursos pasados), cada grupo del más reciente al más antiguo.
 * - `activos()`: solo los cursos en curso, para elegir uno en Exámenes.
 *
 * El orden se aplica acá y no en ListarMateriasConMetricasService porque ese
 * servicio también alimenta a Reportes, que depende de su orden por id.
 *
 * @changelog
 * - 2026-10-24  [Valery D. Ortuno P]  feat: creación inicial del servicio.
 *
 * @see  App\Services\Curso\ListarMateriasConMetricasService
 * @see  App\Http\Controllers\CursoController
 * @see  App\Http\Controllers\ExamenController
 */

namespace App\Services\Curso;

use App\Enums\CursoEstado;
use App\Models\Curso;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

class ListarCursosDocenteService
{
    public function __construct(
        private readonly ListarMateriasConMetricasService $metricas,
    ) {
    }

    /**
     * Cursos del docente con sus métricas: activos primero, luego finalizados,
     * y dentro de cada grupo del más reciente al más antiguo.
     *
     * @param  int  $idDocente  ID del docente (`curso.sis_doc`).
     *
     * @return Collection<int, array<string, mixed>>  Filas de ListarMateriasConMetricasService ordenadas.
     *
     * @author Valery D. Ortuno P <valerydariana98@gmail.com>
     * @since  2026-10-24
     */
    public function conMetricas(int $idDocente): Collection
    {
        $idsCursos = Curso::query()
            ->where('sis_doc', $idDocente)
            ->pluck('id_curso')
            ->all();

        // Sin cursos no hace falta consultar métricas. Ojo: pasarle null al
        // servicio de métricas listaría los cursos de toda la institución.
        if ($idsCursos === []) {
            return collect();
        }

        return $this->metricas->ejecutar($idsCursos)
            ->sortBy(fn (array $fila): array => $this->claveDeOrden($fila['curso']))
            ->values();
    }

    /**
     * Cursos en curso del docente, del más reciente al más antiguo, con la
     * cantidad de estudiantes y de exámenes de cada uno.
     *
     * @param  int  $idDocente  ID del docente (`curso.sis_doc`).
     *
     * @return EloquentCollection<int, Curso>  Cursos con `estudiantes_count` y `examenes_count`.
     *
     * @author Valery D. Ortuno P <valerydariana98@gmail.com>
     * @since  2026-10-24
     */
    public function activos(int $idDocente): EloquentCollection
    {
        return Curso::query()
            ->where('sis_doc', $idDocente)
            ->where('estado', CursoEstado::EnCurso->value)
            ->withCount(['estudiantes', 'examenes'])
            ->orderByRaw('fecha_creacion desc nulls last')
            ->orderByDesc('id_curso')
            ->get();
    }

    /**
     * Clave de orden: activos antes que finalizados y, dentro del grupo, la
     * fecha de creación más reciente primero (el id desempata).
     *
     * @param  Curso  $curso  Curso a ubicar.
     *
     * @return array<int, int|string>  Clave comparable por sortBy.
     */
    private function claveDeOrden(Curso $curso): array
    {
        $esActivo = $curso->estado === CursoEstado::EnCurso->value;

        // sortBy es ascendente: la fecha se invierte para que la más
        // reciente quede primero; sin fecha va al final de su grupo.
        $fecha = $curso->fecha_creacion !== null
            ? -strtotime((string) $curso->fecha_creacion)
            : PHP_INT_MAX;

        return [$esActivo ? 0 : 1, $fecha, -$curso->id_curso];
    }
}
