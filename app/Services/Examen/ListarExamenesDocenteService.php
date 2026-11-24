<?php

/**
 * @file    ListarExamenesDocenteService.php
 *
 * @author  Alex Candia <alex.leonar.candia@gmail.com>
 *
 * @created 2026-11-14
 *
 * @updated 2026-11-16
 * @updated 2026-11-24
 *
 * @description
 * Lista los examenes a los que tiene acceso el docente autenticado: los que
 * creo y aquellos donde una invitacion fue aceptada. Cada examen incluye los
 * cursos asociados para que la pantalla unica de Examenes pueda mostrar el
 * curso junto al nombre y usarlo en los selectores de edicion y division.
 *
 * Los examenes que ya no tienen ningun curso (huérfanos de una eliminacion
 * logica) no aparecen: sin curso no hay pantalla donde mostrarlos.
 *
 * La lista incluye examenes pasados y futuros; los selectores de la vista
 * filtran los futuros en el navegador. Es de solo lectura y reutiliza el mapeo
 * de ListarExamenesCursoService.
 *
 * @changelog
 * - 2026-11-16  [OchoaCesar]  fix: excluye los examenes sin curso y suma
 *   `incidencias` al payload (igual que el listado por curso).
 *
 * - 2026-11-24  [Valery D. Ortuno P]  fix/perf: una sola consulta y solo los
 *   examenes que estan en un curso propio del docente (anfitrion o invitado).
 *
 * @see  App\Http\Controllers\Api\ExamenDocenteController
 * @see  App\Services\Examen\ListarExamenesCursoService
 */

namespace App\Services\Examen;

use App\Models\Examen;
use Carbon\Carbon;

class ListarExamenesDocenteService
{
    public function __construct(
        private readonly ListarExamenesCursoService $mapeador,
    ) {}

    /**
     * @return list<array<string, mixed>> Examenes ordenados por fecha y hora
     *                                    descendentes.
     */
    public function ejecutar(int $idDocente, ?Carbon $ahora = null): array
    {
        $ahora ??= Carbon::now();

        // Una sola consulta: los examenes que estan en un curso propio del
        // docente, sea anfitrion o invitado (un examen sin curso propio o
        // desvinculado de todos sus cursos no aparece).
        return collect($this->mapeador->listar($idDocente, null, $ahora))
            ->sortBy([
                ['fecha', 'desc'],
                ['hora_inicio', 'desc'],
                ['id', 'desc'],
            ])
            ->values()
            ->all();
    }
}
