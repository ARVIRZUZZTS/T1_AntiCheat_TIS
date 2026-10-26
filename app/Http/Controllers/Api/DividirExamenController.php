<?php

/**
 * @file    DividirExamenController.php
 *
 * @author  Alisson D. Alvarado <alvaradoalissondalet@gmail.com>
 *
 * @created 2026-10-25
 * @updated 2026-10-26
 *
 * @description
 * Controlador HTTP de division de estudiantes por ambientes para un examen.
 *
 * @changelog
 * - 2026-10-25  [Alisson D. Alvarado]  feat: creacion inicial.
 * - 2026-10-26  [Alisson D. Alvarado]  fix: usa la habilidad "configurar" de
 *   ExamenPolicy, que es la que existe para gestionar un examen.
 *
 * @see  App\Services\Examen\DividirExamenService
 */

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EstudianteExamen;
use App\Models\Examen;
use App\Services\Examen\DividirExamenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class DividirExamenController extends Controller
{
    public function preview(Request $request, Examen $examen): JsonResponse
    {
        Gate::forUser($request->user())->authorize('configurar', $examen);

        $validated = $request->validate([
            'ambientes' => ['required', 'array', 'min:1'],
            'ambientes.*' => ['required', 'integer'],
            'division_por' => ['required', 'string', 'in:apellido,carrera'],
        ]);

        $ambientes = array_values(array_map(static fn ($id): int => (int) $id, $validated['ambientes']));
        $divisionPor = $validated['division_por'];

        if (count($ambientes) < 2) {
            return response()->json([
                'exito' => false,
                'mensaje' => 'Se requieren al menos dos ambientes para dividir.',
            ], 422);
        }

        $inscripciones = EstudianteExamen::query()
            ->with('estudiante')
            ->where('id_examen', $examen->id_examen)
            ->get();

        if ($inscripciones->isEmpty()) {
            return response()->json([
                'exito' => false,
                'mensaje' => 'No hay estudiantes inscritos en este examen.',
            ], 422);
        }

        if ($divisionPor === 'apellido') {
            $inscripcionesOrdenadas = $inscripciones->sortBy(function (EstudianteExamen $inscripcion): string {
                $estudiante = $inscripcion->estudiante;
                $apellido = $estudiante?->apellido_estudiante ?? '';
                $nombre = $estudiante?->nombre_estudiante ?? '';
                $sis = $inscripcion->sis_estudiante ?? '';
                return mb_strtolower($apellido.' '.$nombre.' '.$sis);
            })->values();
        } else {
            $inscripcionesOrdenadas = $inscripciones->sortBy(function (EstudianteExamen $inscripcion): string {
                $estudiante = $inscripcion->estudiante;
                $carrera = $estudiante?->carrera ?? '';
                $apellido = $estudiante?->apellido_estudiante ?? '';
                $nombre = $estudiante?->nombre_estudiante ?? '';
                $sis = $inscripcion->sis_estudiante ?? '';
                return mb_strtolower($carrera.'|'.$apellido.' '.$nombre.' '.$sis);
            })->values();
        }

        $ambientesInfo = DB::table('ambiente')
            ->whereIn('id_ambiente', $ambientes)
            ->get()
            ->keyBy('id_ambiente');

        $cantidadAmbientes = count($ambientes);
        $total = $inscripcionesOrdenadas->count();
        $base = intdiv($total, $cantidadAmbientes);
        $resto = $total % $cantidadAmbientes;

        $distribucion = [];
        $inicio = 0;
        foreach ($ambientes as $i => $ambienteId) {
            $cantidad = $base + ($i < $resto ? 1 : 0);
            $grupo = $inscripcionesOrdenadas->slice($inicio, $cantidad)->values();
            $inicio += $cantidad;

            $rango = null;
            if ($divisionPor === 'apellido' && $grupo->isNotEmpty()) {
                $primero = $grupo->first()->estudiante;
                $ultimo = $grupo->last()->estudiante;
                $rango = mb_strtoupper(mb_substr($primero?->apellido_estudiante ?? '', 0, 1)) . ' - ' . mb_strtoupper(mb_substr($ultimo?->apellido_estudiante ?? '', 0, 1));
            } elseif ($divisionPor === 'carrera' && $grupo->isNotEmpty()) {
                $primeroCarrera = $grupo->first()->estudiante?->carrera ?? '';
                $ultimoCarrera = $grupo->last()->estudiante?->carrera ?? '';
                if ($primeroCarrera === $ultimoCarrera) {
                    $rango = $primeroCarrera;
                } else {
                    $rango = $primeroCarrera.' / '.$ultimoCarrera;
                }
            }

            $distribucion[] = [
                'id_ambiente' => $ambienteId,
                'nombre_ambiente' => $ambientesInfo[$ambienteId]->nombre_ambiente ?? ('Ambiente '.$ambienteId),
                'rango' => $rango,
                'criterio' => $divisionPor === 'apellido' ? 'Por apellido' : 'Por carrera',
                'cantidad' => $cantidad,
            ];
        }

        return response()->json([
            'exito' => true,
            'division_por' => $divisionPor,
            'ambientes' => $distribucion,
            'total_estudiantes' => $total,
        ]);
    }

    public function confirmar(Request $request, Examen $examen, DividirExamenService $servicio): JsonResponse
    {
        Gate::forUser($request->user())->authorize('configurar', $examen);

        $validated = $request->validate([
            'ambientes' => ['required', 'array', 'min:2'],
            'ambientes.*' => ['required', 'integer'],
            'division_por' => ['required', 'string', 'in:apellido,carrera'],
        ]);

        DB::table('estudiante_examen_ambiente')
            ->whereIn('id_ee', function ($q) use ($examen) {
                $q->select('id_estudiante_examen')
                    ->from('estudiante_examen')
                    ->where('id_examen', $examen->id_examen);
            })
            ->delete();

        $servicio->dividir($examen, $validated['ambientes'], $validated['division_por']);

        $resumen = DB::table('estudiante_examen_ambiente as eea')
            ->join('estudiante_examen as ee', 'ee.id_estudiante_examen', '=', 'eea.id_ee')
            ->join('ambiente as a', 'a.id_ambiente', '=', 'eea.id_ambiente')
            ->where('ee.id_examen', $examen->id_examen)
            ->select('a.nombre_ambiente', DB::raw('count(*) as cantidad'))
            ->groupBy('a.nombre_ambiente', 'a.id_ambiente')
            ->orderBy('a.id_ambiente')
            ->get();

        return response()->json([
            'exito' => true,
            'mensaje' => 'Examen dividido con éxito',
            'resumen' => $resumen,
        ]);
    }
}
