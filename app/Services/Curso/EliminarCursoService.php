<?php

/**
 * @file    EliminarCursoService.php
 *
 * @author  Alex Candia
 *
 * @created 2026-11-16
 *
 * @description
 * Elimina definitivamente un curso y sus vínculos, en una sola transacción.
 *
 * Las llaves foráneas del esquema no tienen borrado en cascada, así que el
 * servicio limpia primero las tablas que apuntan al curso:
 *
 *   - desvincula sus exámenes con EliminarExamenCursoService, que también
 *     borra las inscripciones de este curso y sus ambientes;
 *   - elimina las asignaciones de auxiliares y estudiantes;
 *   - elimina la gestión con su año y el resto de vínculos con exámenes;
 *   - elimina las notificaciones que referencian el curso;
 *   - deja sin curso (NULL) las incidencias y las inscripciones huérfanas;
 *   - por último, borra la fila de `curso`.
 *
 * Los exámenes compartidos siguen existiendo para los demás cursos: solo se
 * eliminan del curso que se borra. Se fuerza el servicio de examen para que
 * un examen con historia no bloquee la eliminación del curso.
 *
 * Se bloquea la fila del curso dentro de la transacción para que dos
 * eliminaciones simultáneas no compitan por los mismos vínculos.
 *
 * @changelog
 * - 2026-11-16  [Alex Candia]  feat: eliminación transaccional de curso y
 *   limpieza de sus llaves foráneas.
 * - 2026-11-16  [Alex Candia]  feat: los exámenes del curso se eliminan con
 *   EliminarExamenCursoService.
 *
 * @see  App\Services\Curso\ContarAfectadosCursoService
 * @see  App\Services\Examen\EliminarExamenCursoService
 * @see  App\Http\Controllers\CursoController
 */

namespace App\Services\Curso;

use App\Models\Curso;
use App\Models\Usuario;
use App\Services\Examen\EliminarExamenCursoService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

class EliminarCursoService
{
    public function __construct(
        private readonly EliminarExamenCursoService $eliminarExamen,
    ) {}

    /**
     * Borra el curso, sus exámenes y todas sus referencias.
     *
     * @param  Usuario  $docente  Docente autenticado que elimina el curso.
     * @param  Curso  $curso  Curso que se quiere eliminar.
     *
     * @throws ModelNotFoundException Si el curso ya no existe.
     *
     * @author Alex Candia
     *
     * @since  2026-11-16
     */
    public function ejecutar(Usuario $docente, Curso $curso): void
    {
        DB::transaction(function () use ($docente, $curso): void {
            $bloqueado = Curso::query()
                ->whereKey($curso->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $idCurso = (int) $bloqueado->id_curso;

            foreach ($bloqueado->examenes()->get() as $examen) {
                $this->eliminarExamen->ejecutar($docente, $examen, $bloqueado, true);
            }

            DB::table('notificacion_docente')
                ->where('id_curso', $idCurso)
                ->delete();

            DB::table('notificacion_auxiliar')
                ->where('id_curso', $idCurso)
                ->delete();

            DB::table('central_riesgo')
                ->where('id_curso', $idCurso)
                ->update(['id_curso' => null]);

            DB::table('estudiante_examen')
                ->where('id_curso', $idCurso)
                ->update(['id_curso' => null]);

            DB::table('examen_curso')
                ->where('id_curso', $idCurso)
                ->delete();

            DB::table('auxiliar_curso')
                ->where('id_curso', $idCurso)
                ->delete();

            DB::table('estudiante_curso')
                ->where('id_curso', $idCurso)
                ->delete();

            DB::table('curso_tipo_gestion')
                ->where('id_curso', $idCurso)
                ->delete();

            DB::table('curso')
                ->where('id_curso', $idCurso)
                ->delete();
        });
    }
}
