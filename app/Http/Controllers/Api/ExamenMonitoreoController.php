<?php

/**
 * @file    ExamenMonitoreoController.php
 *
 * @author  OchoaCesar <cesareduardonick@gmail.com>
 *
 * @created 2026-09-25
 *
 * @updated 2026-10-10
 *
 * @description
 * Controlador HTTP del endpoint de monitoreo de asistencia de un examen.
 * Delega el cálculo en la lógica de dominio y arma la respuesta JSON con el
 * examen, la hora del servidor y los estudiantes con su estado de asistencia.
 *
 * @changelog
 * - 2026-09-25  [OchoaCesar]  feat:  creación inicial del controlador.
 * - 2026-10-10  [Alex Candia]  refactor: el examen se arma campo a campo porque
 *   `hora_fin` es un accesor del modelo y `only()` solo devuelve atributos.
 *
 * @see  GenerarReporteAsistenciaService
 */

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Examen;
use App\Models\Rol;
use App\Services\Monitoreo\GenerarReporteAsistenciaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class ExamenMonitoreoController extends Controller
{
    public function asistencia(
        int $idExamen,
        Request $request,
        GenerarReporteAsistenciaService $servicio
    ): JsonResponse {
        $examenAutorizado = Examen::query()->findOrFail($idExamen);
        Gate::forUser($request->user())->authorize('monitorear', $examenAutorizado);

        $idDocente = (int) $request->user()->id_usuario;
        if ($request->user()->roles->contains('nombre_rol', Rol::NOMBRE_AUXILIAR)) {
            $idDocente = (int) DB::table('examen_curso as ec')
                ->join('auxiliar_curso as ac', 'ac.id_curso', '=', 'ec.id_curso')
                ->join('curso as c', 'c.id_curso', '=', 'ec.id_curso')
                ->where('ec.id_examen', $idExamen)
                ->where('ac.id_auxiliar', $request->user()->id_usuario)
                ->orderBy('c.id_curso')
                ->value('c.sis_doc');
        }

        $reporte = $servicio->ejecutar(
            $idExamen,
            idDocente: $idDocente,
        );
        $examen = $reporte['examen'];

        return response()->json([
            // `hora_fin` no es columna: la resuelve el accesor del modelo, así
            // que no se puede armar con `only()`, que solo devuelve atributos.
            'examen' => [
                'id_examen' => $examen->id_examen,
                'fecha' => $examen->fecha,
                'hora_inicio' => $examen->hora_inicio,
                'hora_fin' => $examen->hora_fin,
                'duracion' => $examen->duracion,
            ],
            'server_time' => now()->toDateTimeString(),
            'estudiantes' => $reporte['estudiantes'],
        ]);
    }
}
