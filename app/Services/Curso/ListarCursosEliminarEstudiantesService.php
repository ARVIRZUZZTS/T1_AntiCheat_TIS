<?php

/**
 * @file    ListarCursosEliminarEstudiantesService.php
 *
 * @author  Valery D. Ortuno P <valerydariana98@gmail.com>
 *
 * @created 2026-11-28
 *
 * @description
 * Datos de la página "Eliminar estudiantes" (HU 34): los cursos activos del
 * docente que tienen una lista de estudiantes cargada, con su gestión, la
 * cantidad de estudiantes y si la lista se puede eliminar.
 *
 * La lista no se puede eliminar cuando el curso ya tomó algún examen (su
 * fecha y hora de inicio ya pasaron) o cuando hay ingresos o incidencias de
 * sus estudiantes en los exámenes del curso. `estadoCursos()` lo resuelve en
 * una sola consulta y también la usa EliminarEstudiantesCursoService como
 * guarda del servidor.
 *
 * @see  App\Services\Curso\EliminarEstudiantesCursoService
 * @see  App\Livewire\Cursos\EliminarEstudiantes
 */

namespace App\Services\Curso;

use App\Models\Curso;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ListarCursosEliminarEstudiantesService
{
    public const MOTIVO_EXAMEN_TOMADO = 'No se puede eliminar la lista de un curso que ya tomo un examen';

    public const MOTIVO_CON_INGRESOS = 'No se puede eliminar la lista de un curso con ingresos o incidencias registradas';

    public function __construct(
        private readonly ListarCursosDocenteService $cursos,
    ) {}

    /**
     * Cursos activos del docente con estudiantes, listos para la tabla.
     *
     * @param  int  $idDocente  Docente dueño de los cursos (`curso.sis_doc`).
     * @return list<array{id: int, nombre: string, gestion: ?string, estudiantes: int, eliminable: bool, motivo: string}>
     *
     * @author Valery D. Ortuno P <valerydariana98@gmail.com>
     *
     * @since  2026-11-28
     */
    public function ejecutar(int $idDocente): array
    {
        $cursos = $this->cursos->activos($idDocente)
            ->filter(fn (Curso $curso): bool => (int) $curso->estudiantes_count > 0)
            ->values();

        $estados = $this->estadoCursos($cursos->pluck('id_curso')->map(fn ($id): int => (int) $id)->all());

        return $cursos->map(function (Curso $curso) use ($estados): array {
            $motivo = $this->motivo($estados[(int) $curso->id_curso] ?? null);

            return [
                'id' => (int) $curso->id_curso,
                'nombre' => (string) $curso->nombre_curso,
                'gestion' => $curso->gestion,
                'estudiantes' => (int) $curso->estudiantes_count,
                'eliminable' => $motivo === '',
                'motivo' => $motivo,
            ];
        })->all();
    }

    /**
     * Por curso: si ya tomó algún examen y si tiene ingresos o incidencias de
     * sus estudiantes en los exámenes del curso. Una sola consulta.
     *
     * @param  list<int>  $idsCursos
     * @return array<int, array{tomado: bool, ingresos: bool}>
     *
     * @author Valery D. Ortuno P <valerydariana98@gmail.com>
     *
     * @since  2026-11-28
     */
    public function estadoCursos(array $idsCursos): array
    {
        if ($idsCursos === []) {
            return [];
        }

        $filas = DB::table('examen_curso as ec')
            ->join('examen as e', 'e.id_examen', '=', 'ec.id_examen')
            ->whereIn('ec.id_curso', $idsCursos)
            ->select('ec.id_curso', 'e.fecha', 'e.hora_inicio')
            ->selectRaw('(exists (select 1 from registro_asistencia ra
                    join estudiante_curso es on es.sis_estudiante = ra.id_estudiante
                    where ra.id_examen = e.id_examen and es.id_curso = ec.id_curso)
                or exists (select 1 from central_riesgo cr
                    join estudiante_curso es2 on es2.sis_estudiante = cr.sis_estudiante
                    where cr.id_examen = e.id_examen and es2.id_curso = ec.id_curso)) as con_registros')
            ->get();

        $estados = [];
        $ahora = Carbon::now();
        foreach ($filas as $fila) {
            $id = (int) $fila->id_curso;
            $estados[$id] ??= ['tomado' => false, 'ingresos' => false];

            if ($fila->fecha !== null) {
                $inicio = Carbon::parse(substr((string) $fila->fecha, 0, 10).' '.($fila->hora_inicio ?? '00:00:00'));
                $estados[$id]['tomado'] = $estados[$id]['tomado'] || $ahora->gte($inicio);
            }

            $estados[$id]['ingresos'] = $estados[$id]['ingresos'] || (bool) $fila->con_registros;
        }

        return $estados;
    }

    /**
     * Mensaje de bloqueo del curso, o '' si la lista se puede eliminar.
     *
     * @param  array{tomado: bool, ingresos: bool}|null  $estado
     *
     * @author Valery D. Ortuno P <valerydariana98@gmail.com>
     *
     * @since  2026-11-28
     */
    public function motivo(?array $estado): string
    {
        return match (true) {
            $estado === null => '',
            $estado['tomado'] => self::MOTIVO_EXAMEN_TOMADO,
            $estado['ingresos'] => self::MOTIVO_CON_INGRESOS,
            default => '',
        };
    }
}
