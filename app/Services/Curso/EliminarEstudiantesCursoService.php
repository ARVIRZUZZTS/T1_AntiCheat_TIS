<?php

/**
 * @file    EliminarEstudiantesCursoService.php
 *
 * @author  Valery D. Ortuno P <valerydariana98@gmail.com>
 *
 * @created 2026-11-28
 *
 * @description
 * Elimina la lista de estudiantes de un curso (HU 34), por ejemplo cuando se
 * cargó un CSV equivocado. Deshace lo que hace la carga:
 *
 *   - borra las habilitaciones del curso en `estudiante_examen` y sus
 *     ambientes (`estudiante_examen_ambiente` no tiene borrado en cascada);
 *   - borra las inscripciones del curso en `estudiante_curso`;
 *   - borra de `estudiante` a los que quedaron sin ningún otro curso ni
 *     historial (habilitaciones, ingresos o incidencias).
 *
 * Solo el docente dueño de un curso activo puede hacerlo (CursoPolicy
 * `eliminar` y estado EnCurso), y nunca si el curso ya tomó algún examen o
 * hay ingresos o incidencias de sus estudiantes: se vuelve a revisar dentro
 * de la transacción, con la fila del curso bloqueada.
 *
 * @see  App\Services\Curso\ListarCursosEliminarEstudiantesService
 * @see  App\Services\Curso\CargarListaEstudiantesService
 * @see  App\Livewire\Cursos\EliminarEstudiantes
 */

namespace App\Services\Curso;

use App\Enums\CursoEstado;
use App\Models\Curso;
use App\Models\Usuario;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class EliminarEstudiantesCursoService
{
    public const MENSAJE_EXITO = 'Lista de estudiantes eliminada con exito';

    public const MENSAJE_NO_ACTIVO = 'Solo se puede eliminar la lista de un curso activo';

    public const MENSAJE_SIN_ESTUDIANTES = 'El curso no tiene estudiantes cargados';

    public function __construct(
        private readonly ListarCursosEliminarEstudiantesService $listar,
    ) {}

    /**
     * Quita la lista de estudiantes del curso.
     *
     * @param  Usuario  $docente  Docente autenticado (dueño del curso).
     * @param  Curso  $curso  Curso cuya lista se elimina.
     * @return int Cantidad de estudiantes quitados del curso.
     *
     * @throws AuthorizationException Si el curso no es del docente.
     * @throws ValidationException Si el curso no está activo, no tiene
     *                             estudiantes o la lista tiene historial.
     *
     * @author Valery D. Ortuno P <valerydariana98@gmail.com>
     *
     * @since  2026-11-28
     */
    public function ejecutar(Usuario $docente, Curso $curso): int
    {
        Gate::forUser($docente)->authorize('eliminar', $curso);

        return DB::transaction(function () use ($curso): int {
            $bloqueado = Curso::query()->whereKey($curso->getKey())->lockForUpdate()->firstOrFail();
            $idCurso = (int) $bloqueado->id_curso;

            if ($bloqueado->estado !== CursoEstado::EnCurso->value) {
                $this->rechazar(self::MENSAJE_NO_ACTIVO);
            }

            $motivo = $this->listar->motivo($this->listar->estadoCursos([$idCurso])[$idCurso] ?? null);
            if ($motivo !== '') {
                $this->rechazar($motivo);
            }

            DB::table('estudiante_examen_ambiente')
                ->whereIn('id_ee', DB::table('estudiante_examen')->where('id_curso', $idCurso)->select('id_estudiante_examen'))
                ->delete();
            DB::table('estudiante_examen')->where('id_curso', $idCurso)->delete();

            // RETURNING evita una consulta extra para saber a quienes se quito.
            $sis = array_map(
                fn (object $fila): string => (string) $fila->sis_estudiante,
                DB::select('delete from estudiante_curso where id_curso = ? returning sis_estudiante', [$idCurso]),
            );

            if ($sis === []) {
                $this->rechazar(self::MENSAJE_SIN_ESTUDIANTES);
            }

            $this->borrarHuerfanos($sis);

            return count($sis);
        });
    }

    /**
     * Borra de `estudiante` a los que ya no están en ningún curso ni tienen
     * habilitaciones, ingresos o incidencias.
     *
     * @param  list<string>  $sis
     *
     * @author Valery D. Ortuno P <valerydariana98@gmail.com>
     *
     * @since  2026-11-28
     */
    private function borrarHuerfanos(array $sis): void
    {
        foreach (array_chunk($sis, 500) as $lote) {
            DB::table('estudiante as s')
                ->whereIn('s.sis_estudiante', $lote)
                ->whereNotExists(fn ($q) => $q->from('estudiante_curso')->whereColumn('estudiante_curso.sis_estudiante', 's.sis_estudiante'))
                ->whereNotExists(fn ($q) => $q->from('estudiante_examen')->whereColumn('estudiante_examen.sis_estudiante', 's.sis_estudiante'))
                ->whereNotExists(fn ($q) => $q->from('registro_asistencia')->whereColumn('registro_asistencia.id_estudiante', 's.sis_estudiante'))
                ->whereNotExists(fn ($q) => $q->from('central_riesgo')->whereColumn('central_riesgo.sis_estudiante', 's.sis_estudiante'))
                ->delete();
        }
    }

    /** @throws ValidationException */
    private function rechazar(string $mensaje): never
    {
        throw ValidationException::withMessages(['curso' => $mensaje]);
    }
}
