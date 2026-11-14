<?php

/**
 * @file    ListarExamenesDocenteService.php
 *
 * @author  Alex Candia <alex.leonar.candia@gmail.com>
 *
 * @created 2026-11-14
 *
 * @description
 * Lista los examenes a los que tiene acceso el docente autenticado: los que
 * creo y aquellos donde una invitacion fue aceptada. Cada examen incluye los
 * cursos asociados para que la pantalla unica de Examenes pueda mostrar el
 * curso junto al nombre y usarlo en los selectores de edicion y division.
 *
 * La lista incluye examenes pasados y futuros; los selectores de la vista
 * filtran los futuros en el navegador. Es de solo lectura y reutiliza el mapeo
 * de ListarExamenesCursoService.
 *
 * @see  App\Http\Controllers\Api\ExamenDocenteController
 * @see  App\Services\Examen\ListarExamenesCursoService
 */

namespace App\Services\Examen;

use App\Enums\InvitacionEstado;
use App\Models\Examen;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

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
        $examenesInvitados = DB::table('invitacion_examen_compartido')
            ->where('id_docente_invitador', $idDocente)
            ->pluck('id_examen')
            ->all();

        return Examen::query()
            ->with(['tipoExamen', 'ambientes', 'cursos'])
            ->withCount([
                'estudianteExamenes',
                'registrosAsistencia as ingresados' => function (Builder $consulta): Builder {
                    return $consulta->select(DB::raw('COUNT(DISTINCT id_estudiante)'));
                },
            ])
            ->where(function (Builder $consulta) use ($idDocente): Builder {
                return $consulta
                    ->where('creador', $idDocente)
                    ->orWhereHas('invitaciones', function (Builder $invitaciones) use ($idDocente): Builder {
                        return $invitaciones
                            ->where('id_docente_invitado', $idDocente)
                            ->where('estado', InvitacionEstado::Aceptada->value);
                    });
            })
            ->get()
            ->map(fn (Examen $examen): array => $this->mapeador->mapearExamen(
                $examen,
                $ahora,
                $examenesInvitados,
            ))
            ->sortBy([
                ['fecha', 'desc'],
                ['hora_inicio', 'desc'],
                ['id', 'desc'],
            ])
            ->values()
            ->all();
    }
}
