<?php

/**
 * @file    ExamenMonitoreoController.php
 *
 * @author  OchoaCesar <cesareduardonick@gmail.com>
 *
 * @created 2026-09-25
 *
 * @updated 2026-09-25
 *
 * @description
 * Controlador HTTP del endpoint de monitoreo de asistencia de un examen.
 * Delega el cálculo en la lógica de dominio y arma la respuesta JSON con el
 * examen, la hora del servidor y los estudiantes con su estado de asistencia.
 *
 * @changelog
 * - 2026-09-25  [OchoaCesar]  feat:  creación inicial del controlador.
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

        return response()->json([
            'examen' => $reporte['examen']->only(['id_examen', 'fecha', 'hora_inicio', 'hora_fin', 'duracion']),
            'server_time' => now()->toDateTimeString(),
            'estudiantes' => $reporte['estudiantes'],
        ]);
    }
}
