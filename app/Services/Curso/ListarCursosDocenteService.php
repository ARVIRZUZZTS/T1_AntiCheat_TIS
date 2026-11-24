<?php

/**
 * @file    ListarCursosDocenteService.php
 *
 * @author  Valery D. Ortuno P <valerydariana98@gmail.com>
 *
 * @created 2026-10-24
 *
 * @updated 2026-10-26
 * @updated 2026-11-09
 * @updated 2026-11-23
 *
 * @description
 * Lista los cursos de un docente para las secciones Cursos y Exámenes.
 *
 * - `conMetricas()`: solo sus cursos con las métricas de
 *   ListarMateriasConMetricasService, primero los activos y después los
 *   archivados (filtro Archivados de Ver cursos), cada grupo del más reciente
 *   al más antiguo.
 *   Los cursos compartidos por invitación ya no se listan: las invitaciones
 *   son por examen y se manejan en la sección Exámenes (`activos()`).
 * - `activos()`: solo los cursos en curso propios, para elegir uno en
 *   Exámenes. Un examen compartido aparece en el curso propio del invitado
 *   (al aceptar lo asocia a sus cursos), no en el curso del otro docente.
 * - `archivables()`: solo los cursos en curso propios, para la subsección
 *   «Archivar curso» (no se pueden archivar cursos compartidos).
 *
 * El orden se aplica acá y no en ListarMateriasConMetricasService porque ese
 * servicio también alimenta a Reportes, que depende de su orden por id.
 *
 * @changelog
 * - 2026-10-24  [Valery D. Ortuno P]  feat: creación inicial del servicio.
 * - 2026-10-24  [Valery D. Ortuno P]  feat: cada curso de conMetricas() trae su
 *   gestión con año ("Segundo semestre 2026") para la lista de cursos.
 * - 2026-10-26  [Valery D. Ortuno P]  perf: los cursos del docente y sus
 *   gestiones salen de una sola consulta (antes eran dos).
 * - 2026-11-09  [Diegootm]  fix: la lista de Cursos ya no incluye cursos
 *   invitados ni las claves `invitado`/`es_anfitrion` — el criterio era
 *   incorrecto, las invitaciones son por examen y solo se manejan en Exámenes
 *   (ahí `activos()` sigue marcando el curso anfitrión).
 * - 2026-11-21  [Equipo T1]  feat: agrega `archivados()` para la vista
 *   Restaurar curso (Refs #326).
 * - 2026-11-21  [Equipo T1]  feat: `archivables()` para la vista de archivado.
 * - 2026-11-23  [Valery D. Ortuno P]  fix: `activos()` ya no suma los cursos del
 *   otro docente de un examen compartido: el invitado veia en Exámenes todos
 *   los exámenes de esos cursos, incluso los que no le compartieron. El
 *   examen compartido sale en su propio curso. Se quita `es_anfitrion` del
 *   curso (la vista ya no lo usaba).
 * - 2026-11-23  [OchoaCesar]  feat: `gestionesPorCurso()` expone la gestión con
 *   año de cualquier curso propio (activo o archivado) para la lista única de
 *   «Archivar/Restaurar cursos», con el mismo formato que «Ver cursos».
 * - 2026-11-23  [Valery D. Ortuno P]  fix: `conMetricas()` vuelve a incluir los
 *   cursos archivados, para el filtro Archivados de Ver cursos (sale la
 *   subopcion "Cursos archivados" del sidebar). Editar curso ya filtra solo
 *   los activos.
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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ListarCursosDocenteService
{
    public function __construct(
        private readonly ListarMateriasConMetricasService $metricas,
    ) {}

    /**
     * Cursos propios del docente con sus métricas: activos primero, luego
     * finalizados, y dentro de cada grupo del más reciente al más antiguo.
     *
     * Los cursos recibidos por invitación no se listan: las invitaciones son
     * por examen y el docente invitado los gestiona desde Exámenes.
     *
     * @param  int  $idDocente  ID del docente (`curso.sis_doc`).
     * @return Collection<int, array{
     *     gestion: string|null,
     *     curso: Curso,
     *     inscritos: int,
     *     estudiantes: int,
     *     habilitados: int,
     *     deshabilitados: int,
     *     incidencias: int,
     *     examen_activo: bool,
     * }> Filas de ListarMateriasConMetricasService ordenadas, con `gestion`
     *    ("Segundo semestre 2026" o null).
     *
     * @author Valery D. Ortuno P <valerydariana98@gmail.com>
     *
     * @since  2026-10-24
     */
    public function conMetricas(int $idDocente): Collection
    {
        $cursos = $this->cursosConGestion($idDocente);
        $idsCursos = $cursos->keys()->all();

        // Sin cursos no hace falta consultar mActricas. Ojo: pasarle null al
        // servicio de mActricas listarA-a los cursos de toda la instituciA3n.
        if ($idsCursos === []) {
            return collect();
        }

        $gestiones = $cursos->filter();

        return $this->metricas->ejecutar($idsCursos, true)
            ->map(fn (array $fila): array => $fila + [
                'gestion' => $gestiones->get($fila['curso']->id_curso),
            ])
            ->sortBy(fn (array $fila): array => $this->claveDeOrden($fila['curso']))
            ->values();
    }

    /**
     * Cursos propios del docente con la gestión y el año de cada uno, en una
     * sola consulta (cada viaje a la base remota pesa).
     *
     * @param  int  $idDocente  ID del docente (`curso.sis_doc`).
     * @return Collection<int, non-falsy-string|null> id_curso => "Segundo semestre 2026", o
     *                                                null si el curso no tiene gestión.
     */
    private function cursosConGestion(int $idDocente): Collection
    {
        return DB::table('curso as c')
            ->leftJoin('curso_tipo_gestion as ctg', 'ctg.id_curso', '=', 'c.id_curso')
            ->leftJoin('tipo_gestion as tg', 'tg.id_tipo_gestion', '=', 'ctg.id_tg')
            ->where('c.sis_doc', $idDocente)
            ->get(['c.id_curso', 'tg.nombre_tipo_gestion', 'ctg.anio'])
            ->mapWithKeys(fn (object $fila): array => [
                (int) $fila->id_curso => $fila->nombre_tipo_gestion !== null
                    ? Str::ucfirst($fila->nombre_tipo_gestion).' '.$fila->anio
                    : null,
            ]);
    }

    /**
     * Cursos archivados propios del docente, ordenados por nombre.
     *
     * @param  int  $idDocente  ID del docente (`curso.sis_doc`).
     * @return EloquentCollection<int, Curso> Cursos en estado `Archivado`.
     *
     * @author Equipo T1
     *
     * @since  2026-11-21
     */
    public function archivados(int $idDocente): EloquentCollection
    {
        return Curso::query()
            ->where('sis_doc', $idDocente)
            ->where('estado', CursoEstado::Archivado->value)
            ->withCount('estudiantes')
            ->orderBy('nombre_curso')
            ->orderBy('id_curso')
            ->get(['id_curso', 'nombre_curso', 'estado']);
    }

    /**
     * Gestión con año ("Segundo semestre 2026") de los cursos indicados,
     * indexada por id_curso. Cubre también los archivados, que no aparecen en
     * `cursosConGestion()` porque ese método solo alimenta «Ver cursos».
     *
     * @param  array<int, int>  $idsCursos  IDs de los cursos a consultar.
     * @return Collection<int, non-falsy-string|null> id_curso => texto de la gestión,
     *                                                o null si no tiene gestion registrada.
     *
     * @author OchoaCesar <cesareduardonick@gmail.com>
     *
     * @since  2026-11-23
     */
    public function gestionesPorCurso(array $idsCursos): Collection
    {
        if ($idsCursos === []) {
            return collect();
        }

        return DB::table('curso_tipo_gestion as ctg')
            ->join('tipo_gestion as tg', 'tg.id_tipo_gestion', '=', 'ctg.id_tg')
            ->whereIn('ctg.id_curso', $idsCursos)
            ->get(['ctg.id_curso', 'tg.nombre_tipo_gestion', 'ctg.anio'])
            ->mapWithKeys(fn (object $fila): array => [
                (int) $fila->id_curso => $fila->nombre_tipo_gestion !== null
                    ? Str::ucfirst($fila->nombre_tipo_gestion).' '.$fila->anio
                    : null,
            ]);
    }

    /**
     * Cursos en curso propios, del más reciente al más antiguo, con la
     * cantidad de estudiantes y de exámenes de cada uno. Los exámenes
     * compartidos ya están en el curso propio del invitado.
     *
     * @param  int  $idDocente  ID del docente (`curso.sis_doc`).
     * @return EloquentCollection<int, Curso> Cursos con `estudiantes_count` y `examenes_count`.
     *
     * @author Valery D. Ortuno P <valerydariana98@gmail.com>
     *
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

    public function conExamenes(int $idDocente): EloquentCollection
    {
        return $this->activos($idDocente)
            ->filter(fn (Curso $curso): bool => (int) $curso->examenes_count > 0)
            ->values();
    }

    /**
     * Cursos propios en estado `EnCurso`, en orden alfabético, listos para
     * archivar. Los cursos compartidos por invitación quedan afuera: el
     * servicio de archivado solo autoriza al docente propietario.
     *
     * @param  int  $idDocente  ID del docente (`curso.sis_doc`).
     * @return EloquentCollection<int, Curso> Cursos con id, nombre y estado.
     */
    public function archivables(int $idDocente): EloquentCollection
    {
        return Curso::query()
            ->where('sis_doc', $idDocente)
            ->where('estado', CursoEstado::EnCurso->value)
            ->withCount('estudiantes')
            ->orderBy('nombre_curso')
            ->orderBy('id_curso')
            ->get(['id_curso', 'nombre_curso', 'estado']);
    }

    /**
     * Clave de orden: activos antes que finalizados y, dentro del grupo, la
     * fecha de creación más reciente primero (el id desempata).
     *
     * @param  Curso  $curso  Curso a ubicar.
     * @return array<int, int|string> Clave comparable por sortBy.
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
