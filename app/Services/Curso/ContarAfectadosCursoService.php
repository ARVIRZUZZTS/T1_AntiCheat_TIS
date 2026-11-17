<?php

/**
 * @file    ContarAfectadosCursoService.php
 *
 * @author  Alex Candia
 *
 * @created 2026-11-16
 *
 * @description
 * Cuenta los auxiliares y estudiantes que quedarían desvinculados si se
 * elimina un curso. El modal "Eliminar curso" usa estos números para avisar
 * a quiénes afecta antes de confirmar el borrado.
 *
 * Los auxiliares se cuentan distintos porque la tabla `auxiliar_curso` puede
 * tener una fila por asignación; los estudiantes se cuentan por su fila en
 * `estudiante_curso`. No se filtra por estado: al borrar el curso se van
 * todas las asignaciones, incluidas las de usuarios dados de baja.
 *
 * @changelog
 * - 2026-11-16  [Alex Candia]  feat: conteo de afectados para eliminar curso.
 *
 * @see  App\Services\Curso\EliminarCursoService
 * @see  App\Http\Controllers\CursoController
 */

namespace App\Services\Curso;

use App\Models\Curso;
use Illuminate\Support\Facades\DB;

class ContarAfectadosCursoService
{
    /**
     * Cuenta auxiliares y estudiantes vinculados al curso.
     *
     * @param  Curso  $curso  Curso que se quiere eliminar.
     * @return array{auxiliares: int, estudiantes: int} Totales afectados.
     *
     * @author Alex Candia
     *
     * @since  2026-11-16
     */
    public function ejecutar(Curso $curso): array
    {
        return [
            'auxiliares' => (int) DB::table('auxiliar_curso')
                ->where('id_curso', $curso->id_curso)
                ->distinct()
                ->count('id_auxiliar'),
            'estudiantes' => (int) DB::table('estudiante_curso')
                ->where('id_curso', $curso->id_curso)
                ->count(),
        ];
    }
}
