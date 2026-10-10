<?php

/**
 * @file    CentralRiesgoController.php
 *
 * @author  T1 - Tech One SRL
 *
 * @created 2026-10-09
 *
 * @updated 2026-10-09
 *
 * @description
 * Controlador HTTP del endpoint de la central de riesgos: lista las
 * incidencias `tramposo` con ordenamiento, filtro por alcance, búsqueda y
 * paginación de 8 en 8. Es delgado: solo orquesta los parámetros de query y
 * delega en el servicio de dominio.
 *
 * Query string:
 * - `usuario`  ID del usuario, obligatorio con alcance mis-materias.
 * - `alcance`  mis-materias | toda-la-institucion (por defecto toda-la-institucion).
 * - `orden`    az para orden alfabético; ausente ordena por fecha desc.
 * - `busqueda` término por nombre o código SIS.
 * - `pagina`   número de página, arranca en 1.
 *
 * @see  App\Services\CentralRiesgo\ListarIncidenciasTramposoService
 *
 * @changelog
 * - 2026-10-09  [T1]  feat: creación inicial del controlador.
 */

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CentralRiesgo\ListarIncidenciasTramposoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class CentralRiesgoController extends Controller
{
    public function index(
        Request $request,
        ListarIncidenciasTramposoService $servicio
    ): JsonResponse {
        $usuario = $request->query('usuario');
        $busqueda = $request->query('busqueda');

        try {
            $incidencias = $servicio->ejecutar(
                $usuario !== null && $usuario !== '' ? (int) $usuario : null,
                $request->query('alcance') ?: null,
                $request->query('orden') === 'az',
                $busqueda ?: null,
                max(1, (int) $request->query('pagina', 1)),
            );
        } catch (InvalidArgumentException $e) {
            return response()->json(['mensaje' => $e->getMessage()], 422);
        }

        $sinResultados = $busqueda && $incidencias->total() === 0;

        return response()->json([
            'datos' => $incidencias->items(),
            'paginacion' => [
                'pagina' => $incidencias->currentPage(),
                'por_pagina' => $incidencias->perPage(),
                'total' => $incidencias->total(),
                'ultima_pagina' => $incidencias->lastPage(),
                'de' => $incidencias->firstItem(),
                'a' => $incidencias->lastItem(),
            ],
            'mensaje' => $sinResultados ? 'No se encontraron resultados para la búsqueda' : null,
        ]);
    }
}
