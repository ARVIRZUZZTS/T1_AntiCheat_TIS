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
use App\Services\Monitoreo\GenerarReporteAsistenciaService;
use Illuminate\Http\JsonResponse;

class ExamenMonitoreoController extends Controller
{
    public function asistencia(
        int $idExamen,
        GenerarReporteAsistenciaService $servicio
    ): JsonResponse {
        $reporte = $servicio->ejecutar($idExamen);
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
