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
 *   - elimina las asignaciones de auxiliares y estudiantes;
 *   - elimina la gestión con su año y los vínculos con exámenes;
 *   - elimina las notificaciones que referencian el curso;
 *   - deja sin curso (NULL) las incidencias y las inscripciones a exámenes;
 *   - por último, borra la fila de `curso`.
 *
 * Los exámenes no se eliminan: dejan de estar vinculados a este curso y
 * siguen existiendo para los demás cursos con los que se compartieron.
 *
 * Se bloquea la fila del curso dentro de la transacción para que dos
 * eliminaciones simultáneas no compitan por los mismos vínculos.
 *
 * @changelog
 * - 2026-11-16  [Alex Candia]  feat: eliminación transaccional de curso y
 *   limpieza de sus llaves foráneas.
 *
 * @see  App\Services\Curso\ContarAfectadosCursoService
 * @see  App\Http\Controllers\CursoController
 */

namespace App\Services\Curso;

use App\Models\Curso;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

class EliminarCursoService
{
    /**
     * Borra el curso y todas sus referencias.
     *
     * @param  Curso  $curso  Curso que se quiere eliminar.
     *
     * @throws ModelNotFoundException Si el curso ya no existe.
     *
     * @author Alex Candia
     *
     * @since  2026-11-16
     */
    public function ejecutar(Curso $curso): void
    {
        DB::transaction(function () use ($curso): void {
            $bloqueado = Curso::query()
                ->whereKey($curso->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $idCurso = (int) $bloqueado->id_curso;

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
