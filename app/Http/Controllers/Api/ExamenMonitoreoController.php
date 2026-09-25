<?php

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
