<?php

/**
 * @file    ExamenEdicionController.php
 *
 * @author  Alex Candia <alex.leonar.candia@gmail.com>
 *
 * @created 2026-11-05
 *
 * @description
 * Endpoints de la pestana Editar Examen:
 *
 *  - `mostrar`: lectura que entrega los datos base de un examen ya formateados
 *    para el formulario.
 *  - `actualizar`: guarda los cambios del examen (Task #261). Valida con
 *    UpdateExamenRequest (que reutiliza las reglas del alta) y delega el
 *    guardado en ActualizarExamenService; devuelve los datos ya actualizados
 *    para que el formulario quede en sincronia sin recargar.
 *
 * Solo el creador del examen puede usarlos: la lectura lo comprueba con el Gate
 * y la escritura con UpdateExamenRequest::authorize(); el listado de la pestana
 * ya oculta los examenes ajenos.
 *
 * @see  App\Services\Examen\ObtenerExamenEdicionService
 * @see  App\Services\Examen\ActualizarExamenService
 * @see  App\Http\Requests\UpdateExamenRequest
 * @see  App\Policies\ExamenPolicy::editar()
 */

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateExamenRequest;
use App\Models\Examen;
use App\Services\Examen\ActualizarExamenService;
use App\Services\Examen\ObtenerExamenEdicionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class ExamenEdicionController extends Controller
{
    public function mostrar(Examen $examen, ObtenerExamenEdicionService $servicio): JsonResponse
    {
        Gate::authorize('editar', $examen);

        return response()->json([
            'datos' => $servicio->ejecutar($examen),
        ]);
    }

    /**
     * Guarda los cambios del examen editado. La autorizacion (solo el creador)
     * y la validacion viven en UpdateExamenRequest; si fallan, Laravel responde
     * 403 o 422 y este metodo no llega a ejecutarse.
     */
    public function actualizar(
        UpdateExamenRequest $request,
        Examen $examen,
        ActualizarExamenService $actualizar,
        ObtenerExamenEdicionService $datos,
    ): JsonResponse {
        $actualizar->ejecutar($examen, $request->validated());

        return response()->json([
            'mensaje' => 'Examen actualizado con exito',
            'datos' => $datos->ejecutar($examen->refresh()),
        ]);
    }
}
