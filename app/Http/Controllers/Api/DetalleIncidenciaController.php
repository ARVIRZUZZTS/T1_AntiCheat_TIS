<?php

/**
 * @file    DetalleIncidenciaController.php
 *
 * @author  Alisson D. Alvarado <alvaradoalissondalet@gmail.com>
 *
 * @created 2026-10-09
 *
 * @updated 2026-10-09
 *
 * @description
 * Endpoint JSON de detalle de una incidencia de la central de riesgos. Es la
 * fuente de datos de la futura vista "ver detalle": devuelve estudiante, SIS,
 * motivo, materia, reportante, fecha y hora, estado y descripción. Si la
 * incidencia no existe responde 404 con un mensaje controlado.
 *
 * @changelog
 * - 2026-10-09  [Alisson D. Alvarado]  feat: endpoint de detalle de incidencia.
 *
 * @see  App\Services\CentralRiesgo\ConsultarDetalleIncidenciaService
 */

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CentralRiesgo\ConsultarDetalleIncidenciaService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;


class DetalleIncidenciaController extends Controller
{
    /**
     * Detalle completo de una incidencia existente.
     *
     * @param  int  $idIncidencia  Identificador de la incidencia (`id_registro`).
     * @param  ConsultarDetalleIncidenciaService  $servicio  Lógica del detalle, compartida.
     * @return JsonResponse El detalle bajo la clave `datos`, igual que otras APIs.
     */
    public function index(int $idIncidencia, ConsultarDetalleIncidenciaService $servicio): JsonResponse
    {
        try {
            $detalle = $servicio->ejecutar($idIncidencia);
        } catch (ModelNotFoundException $error) {
            return response()->json(['mensaje' => 'La incidencia no existe.'], 404);
        }

        return response()->json([
            'datos' => $detalle,
            'mensaje' => 'Detalle de la incidencia obtenido correctamente.',
        ]);
    }
}