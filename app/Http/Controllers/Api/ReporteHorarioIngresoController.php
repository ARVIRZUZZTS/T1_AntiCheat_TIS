<?php

/**
 * @file    ReporteHorarioIngresoController.php
 *
 * @author  Alex Candia <alex.leonar.candia@gmail.com>
 *
 * @created 2026-10-23
 *
 * @description Endpoints del reporte de horarios de ingreso: consulta JSON
 * para la pestana de reportes y descarga del libro .xlsx.
 */

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReporteHorarioIngresoRequest;
use App\Models\Usuario;
use App\Services\Reporte\ConsultarReporteHorarioIngresoService;
use App\Services\Reporte\ExportarReporteHorarioIngresoService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReporteHorarioIngresoController extends Controller
{
    public function index(
        ReporteHorarioIngresoRequest $request,
        ConsultarReporteHorarioIngresoService $servicio,
    ): JsonResponse {
        $datos = $this->reporte($request, $servicio);

        return response()->json(['datos' => $datos]);
    }

    public function exportar(
        ReporteHorarioIngresoRequest $request,
        ConsultarReporteHorarioIngresoService $servicio,
        ExportarReporteHorarioIngresoService $exportador,
    ): JsonResponse|StreamedResponse {
        $datos = $this->reporte($request, $servicio);

        if ($datos['sin_datos']) {
            return response()->json([
                'message' => 'No hay ingresos registrados para los filtros seleccionados.',
            ], 422);
        }

        $archivo = $exportador->ejecutar($datos);

        return response()->streamDownload(
            fn () => print $archivo['contenido'],
            $archivo['nombre'],
            [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Cache-Control' => 'no-store, no-cache',
            ],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function reporte(
        ReporteHorarioIngresoRequest $request,
        ConsultarReporteHorarioIngresoService $servicio,
    ): array {
        $datos = $request->validated();
        $usuario = $request->user();

        abort_unless($usuario instanceof Usuario, 401);

        return $servicio->ejecutar(
            $usuario,
            (int) $datos['id_curso'],
            (int) $datos['id_examen'],
            $datos['id_aula'] === null ? null : (int) $datos['id_aula'],
            $datos['hora_desde'],
            $datos['hora_hasta'],
        );
    }
}
