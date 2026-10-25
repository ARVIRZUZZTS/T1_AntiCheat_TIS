<?php

/**
 * @file    ResolverCursoIncidenciaService.php
 *
 * @author  Valery D. Ortuno P <valerydariana98@gmail.com>
 *
 * @created 2026-10-24
 *
 * @description
 * Decide en qué curso se registra una incidencia (`central_riesgo.id_curso`)
 * cuando nadie lo eligió: la central de riesgos lista por ese curso, así que
 * una incidencia sin curso queda "Sin materia" y fuera de "Mis materias".
 *
 * Regla, en orden:
 *  1. El curso del examen en el que está inscrito el estudiante (si son
 *     varios, el de menor id).
 *  2. Si no está inscrito en ninguno y el examen tiene un solo curso, ese.
 *  3. Si no, ninguno (null).
 *
 * Es la misma regla que usa la migración que completó las incidencias que ya
 * existían sin curso (2026_10_24_000002_completar_curso_en_central_riesgo).
 *
 * @changelog
 * - 2026-10-24  [Valery D. Ortuno P]  feat: creación inicial del servicio.
 *
 * @see  App\Services\Asistencia\RegistroIngresoService
 * @see  App\Livewire\Monitoreo\RegistrarIncidencia
 */

namespace App\Services\CentralRiesgo;

use Illuminate\Support\Facades\DB;

class ResolverCursoIncidenciaService
{
    /**
     * Curso en el que corresponde registrar la incidencia del estudiante en el examen.
     *
     * @param  string  $sisEstudiante  Código SIS del estudiante.
     * @param  int  $idExamen  Examen en el que ocurrió la incidencia.
     * @return ?int id_curso, o null si no se puede decidir.
     *
     * @author Valery D. Ortuno P <valerydariana98@gmail.com>
     *
     * @since  2026-10-24
     */
    public function ejecutar(string $sisEstudiante, int $idExamen, ?int $idDocente = null): ?int
    {
        $inscrito = DB::table('examen_curso as ec')
            ->join('estudiante_curso as ecur', 'ecur.id_curso', '=', 'ec.id_curso')
            ->join('curso as c', 'c.id_curso', '=', 'ec.id_curso')
            ->where('ec.id_examen', $idExamen)
            ->where('ecur.sis_estudiante', $sisEstudiante)
            ->when($idDocente !== null, fn ($query) => $query->where('c.sis_doc', $idDocente))
            ->orderBy('ec.id_curso')
            ->value('ec.id_curso');

        if ($inscrito !== null) {
            return (int) $inscrito;
        }

        $cursosDelExamen = DB::table('examen_curso')
            ->join('curso', 'curso.id_curso', '=', 'examen_curso.id_curso')
            ->where('id_examen', $idExamen)
            ->when($idDocente !== null, fn ($query) => $query->where('curso.sis_doc', $idDocente))
            ->pluck('examen_curso.id_curso');

        return $cursosDelExamen->count() === 1 ? (int) $cursosDelExamen->first() : null;
    }
}
