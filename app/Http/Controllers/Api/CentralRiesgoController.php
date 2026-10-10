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
 * incidencias `tramposo` con filtro por alcance y búsqueda, en orden
 * alfabético (A-Z) y sin paginación: la respuesta trae la lista completa.
 * Es delgado: solo orquesta los parámetros de query y delega en el servicio
 * de dominio.
 *
 * Query string:
 * - `usuario`  ID del usuario, obligatorio con alcance mis-materias.
 * - `alcance`  mis-materias | toda-la-institucion (por defecto toda-la-institucion).
 * - `busqueda` término por nombre o código SIS.
 *
 * @see  App\Services\CentralRiesgo\ListarIncidenciasTramposoService
 *
 * @changelog
 * - 2026-10-09  [T1]  feat: creación inicial del controlador.
 * - 2026-10-09  [T1]  refactor: quitar la paginación y los parámetros de
 *   orden; la respuesta es la lista completa en orden A-Z.
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
                $busqueda ?: null,
            );
        } catch (InvalidArgumentException $e) {
            return response()->json(['mensaje' => $e->getMessage()], 422);
        }

        $sinResultados = $busqueda && $incidencias->isEmpty();

        return response()->json([
            'datos' => $incidencias->values(),
            'mensaje' => $sinResultados ? 'No se encontraron resultados para la búsqueda' : null,
        ]);
    }
}
