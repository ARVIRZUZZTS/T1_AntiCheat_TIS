<?php

/**
 * @file    ResolverIncidenciaController.php
 *
 * @author  Diego Tejerina <josediegotejerinamolina@gmail.com>
 *
 * @created 2026-10-09
 *
 * @updated 2026-10-09
 *
 * @description Endpoints JSON de confirmación y rechazo de incidencias pendientes.
 *
 * @changelog
 * - 2026-10-09  [Diego Tejerina]  feat: agregar endpoints de resolución (#142).
 */

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Usuario;
use App\Services\CentralRiesgo\ResolverIncidenciaService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class ResolverIncidenciaController extends Controller
{
    /**
     * @param  int  $idIncidencia  Identificador de la incidencia.
     * @param  Request  $request  Sesión del docente autenticado.
     * @param  ResolverIncidenciaService  $servicio  Reglas y persistencia transaccional.
     * @return JsonResponse Datos confirmados o mensaje de validación (422).
     *
     * @throws AuthorizationException Si no tiene permiso.
     * @throws ModelNotFoundException Si no existe.
     */
    public function confirmar(int $idIncidencia, Request $request, ResolverIncidenciaService $servicio): JsonResponse
    {
        /** @var Usuario $docente */
        $docente = $request->user('web');

        try {
            $incidencia = $servicio->confirmar($idIncidencia, $docente);
        } catch (InvalidArgumentException $error) {
            return response()->json(['mensaje' => $error->getMessage()], 422);
        }

        return response()->json([
            'datos' => $incidencia->only(['id_registro', 'estado_incidencia', 'id_confirmador']),
            'mensaje' => 'Incidencia confirmada correctamente.',
        ]);
    }

    /**
     * @param  int  $idIncidencia  Identificador de la incidencia.
     * @param  Request  $request  Sesión del docente autenticado.
     * @param  ResolverIncidenciaService  $servicio  Reglas y persistencia transaccional.
     * @return JsonResponse Resultado del descarte o mensaje de validación (422).
     *
     * @throws AuthorizationException Si no tiene permiso.
     * @throws ModelNotFoundException Si no existe.
     */
    public function rechazar(int $idIncidencia, Request $request, ResolverIncidenciaService $servicio): JsonResponse
    {
        /** @var Usuario $docente */
        $docente = $request->user('web');

        try {
            $servicio->rechazar($idIncidencia, $docente);
        } catch (InvalidArgumentException $error) {
            return response()->json(['mensaje' => $error->getMessage()], 422);
        }

        return response()->json([
            'datos' => ['id_registro' => $idIncidencia, 'estado_incidencia' => 'Rechazado'],
            'mensaje' => 'Incidencia rechazada correctamente.',
        ]);
    }
}
