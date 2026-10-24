<?php

/**
 * @file    ListarMateriasConMetricasService.php
 *
 * @author  Diego Tejerina <josediegotejerinamolina@gmail.com>
 *
 * @created 2026-10-12
 *
 * @description
 * Arma la lista de materias para `/materias` con las métricas de su examen
 * actual: inscritos, habilitados, deshabilitados e incidencias registradas
 * en la central de riesgos, más si el curso tiene un examen "activo" ahora
 * mismo. Todo en un puñado de consultas agrupadas (nunca una por curso), así
 * que no importa si la lista de materias crece.
 *
 * "Examen actual" de un curso es el mismo criterio que ya usa
 * `Curso::examenActual()` (el de fecha más reciente), pero resuelto para
 * TODOS los cursos de una sola pasada en vez de una consulta por curso.
 *
 * "Examen activo" es un dato derivado, no una columna: el examen actual del
 * curso tiene actividad ahora mismo si la hora actual cae entre su hora de
 * inicio y su hora de fin (inicio + duración), el mismo mismo cálculo de
 * `hora_fin` que ya usa `Examen::getHoraFinAttribute()`. No existe un campo
 * "activo" en la base.
 *
 * "SIS-3340 · Grupo 2" del diseño NO se puede mostrar: la tabla `curso` no
 * tiene columnas de sigla ni de grupo (solo `nombre_curso`, `sis_doc`,
 * `fecha_creacion`, `estado`). La vista usa solo el nombre del curso.
 *
 * @see  App\Models\Curso
 * @see  App\Http\Controllers (ruta /materias en routes/web.php)
 *
 * @changelog
 * - 2026-10-12  [Diego Tejerina]  feat: creación inicial del servicio.
 * - 2026-10-21  [Alex Candia]  feat: parámetro opcional `$idsCursos` para
 *   acotar el listado a un subconjunto (usado por los reportes por usuario).
 *   Sin argumento conserva el comportamiento anterior (todos los cursos).
 */

namespace App\Services\Curso;

use App\Models\Curso;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ListarMateriasConMetricasService
{
    /**
     * @param  array<int, int>|null  $idsCursos  Si se pasa, solo se listan
     *         esos cursos; `null` lista todos (comportamiento de /materias).
     * @return Collection<int, array{
     *     curso: Curso,
     *     inscritos: int,
     *     habilitados: int,
     *     deshabilitados: int,
     *     incidencias: int,
     *     examen_activo: bool,
     * }>
     */
    public function ejecutar(?array $idsCursos = null): Collection
    {
        $consulta = Curso::query()
            ->withCount(['estudiantes as inscritos'])
            ->orderBy('id_curso');

        if ($idsCursos !== null) {
            $consulta->whereIn('id_curso', $idsCursos);
        }

        $cursos = $consulta->get();

        if ($cursos->isEmpty()) {
            return collect();
        }

        $idsCurso = $cursos->pluck('id_curso');

        $examenActualPorCurso = $this->examenActualPorCurso($idsCurso);

        $idsExamenActual = $examenActualPorCurso->pluck('id_examen')->filter()->unique()->values();

        $conteosEstadoPorCurso = $this->conteosEstadoPorCurso($idsCurso, $idsExamenActual, $examenActualPorCurso);

        $incidenciasPorCurso = DB::table('central_riesgo')
            ->whereIn('id_curso', $idsCurso)
            ->select('id_curso', DB::raw('count(*) as total'))
            ->groupBy('id_curso')
            ->pluck('total', 'id_curso');

        $ahora = Carbon::now();

        return $cursos->map(function (Curso $curso) use ($examenActualPorCurso, $conteosEstadoPorCurso, $incidenciasPorCurso, $ahora): array {
            $examenActual = $examenActualPorCurso->get($curso->id_curso);
            $estados = $conteosEstadoPorCurso->get($curso->id_curso, collect());

            return [
                'curso' => $curso,
                'inscritos' => (int) $curso->getAttribute('inscritos'),
                'habilitados' => (int) ($estados->get('habilitado')?->total ?? 0),
                'deshabilitados' => (int) ($estados->get('deshabilitado')?->total ?? 0),
                'incidencias' => (int) ($incidenciasPorCurso->get($curso->id_curso) ?? 0),
                'examen_activo' => $examenActual !== null && $this->examenEnCurso($examenActual, $ahora),
            ];
        })->values();
    }

    /**
     * El examen más reciente de cada curso, en una sola consulta (join +
     * agrupación en PHP), en vez de `Curso::examenActual()` por curso.
     *
     * @param  Collection<int, int>  $idsCurso
     * @return Collection<int, object{id_curso: int, id_examen: int, fecha: ?string, hora_inicio: ?string, duracion: ?int}>
     *         Indexada por id_curso.
     */
    private function examenActualPorCurso(Collection $idsCurso): Collection
    {
        return DB::table('examen_curso as ec')
            ->join('examen as e', 'e.id_examen', '=', 'ec.id_examen')
            ->whereIn('ec.id_curso', $idsCurso)
            ->select('ec.id_curso', 'e.id_examen', 'e.fecha', 'e.hora_inicio', 'e.duracion')
            ->get()
            ->groupBy('id_curso')
            ->map(function (Collection $examenesDelCurso) {
                // Mismo criterio que Curso::examenActual(): fecha más
                // reciente y, en empate, el id_examen más alto.
                return $examenesDelCurso
                    ->sortByDesc(fn ($fila) => $fila->fecha.'-'.str_pad((string) $fila->id_examen, 10, '0', STR_PAD_LEFT))
                    ->first();
            });
    }

    /**
     * Cuenta estudiante_examen por estado (habilitado/deshabilitado), SOLO
     * de estudiantes inscritos en cada curso (`estudiante_curso`) y SOLO
     * contra el examen "actual" de ESE curso puntual.
     *
     * Un examen se puede compartir entre varios cursos (#70): agrupar
     * directo por id_examen contaría, para cada curso, también a los
     * estudiantes de los OTROS cursos que comparten ese mismo examen. Por
     * eso el join pasa por `estudiante_curso` y el filtro final compara,
     * fila por fila, contra el id_examen que le tocó a CADA curso en
     * `$examenActualPorCurso` (no alcanza con un único `whereIn`, porque
     * cursos distintos pueden tener id_examen "actual" distintos).
     *
     * @param  Collection<int, int>  $idsCurso
     * @param  Collection<int, int>  $idsExamen
     * @param  Collection<int, object{id_curso:int,id_examen:int}>  $examenActualPorCurso  Indexada por id_curso.
     * @return Collection<int, Collection<string, object{estado: string, total: int}>>
     *         Indexada por id_curso, y dentro por estado.
     */
    private function conteosEstadoPorCurso(Collection $idsCurso, Collection $idsExamen, Collection $examenActualPorCurso): Collection
    {
        if ($idsExamen->isEmpty()) {
            return collect();
        }

        return DB::table('estudiante_curso as ecurso')
            ->join('estudiante_examen as ee', 'ee.sis_estudiante', '=', 'ecurso.sis_estudiante')
            ->whereIn('ecurso.id_curso', $idsCurso)
            ->whereIn('ee.id_examen', $idsExamen)
            ->select('ecurso.id_curso', 'ee.id_examen', 'ee.estado')
            ->get()
            // El examen puede estar compartido con otro curso: se descarta
            // la fila si no es el examen "actual" de ESE id_curso puntual.
            ->filter(fn ($fila) => $examenActualPorCurso->get($fila->id_curso)?->id_examen === $fila->id_examen)
            ->groupBy('id_curso')
            ->map(function (Collection $filas) {
                return $filas
                    ->countBy(fn ($fila) => $fila->estado)
                    ->map(fn (int $total, string $estado) => (object) ['estado' => $estado, 'total' => $total]);
            });
    }

    /**
     * true si "ahora" cae dentro de la ventana [hora_inicio, hora_fin] del
     * examen (hora_fin = hora_inicio + duracion, igual que
     * Examen::getHoraFinAttribute()).
     */
    private function examenEnCurso(object $examen, Carbon $ahora): bool
    {
        if (! $examen->fecha || ! $examen->hora_inicio || ! $examen->duracion) {
            return false;
        }

        $inicio = Carbon::parse($examen->fecha.' '.$examen->hora_inicio);
        $fin = $inicio->copy()->addMinutes((int) $examen->duracion);

        return $ahora->between($inicio, $fin);
    }
}
