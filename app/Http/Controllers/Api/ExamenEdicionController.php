<?php

/**
 * @file    ExamenEdicionController.php
 *
 * @author  Alex Candia <alex.leonar.candia@gmail.com>
 *
 * @created 2026-11-05
 *
 * @updated 2026-11-24
 *
 * @description
 * Endpoints de la pestana Editar Examen:
 *
 *  - `mostrar`: lectura que entrega los datos base de un examen ya formateados
 *    para el formulario.
 *  - `actualizar`: guarda los cambios del examen (Task #261). Valida con
 *    UpdateExamenRequest (que reutiliza las reglas del alta) y delega el
 *    guardado en ActualizarExamenService, que también avisa a los usuarios
 *    involucrados si cambiaron ambientes, fecha u hora; devuelve los datos ya
 *    actualizados para que el formulario quede en sincronia sin recargar.
 *
 * Solo el creador del examen puede usarlos: la lectura lo comprueba con el Gate
 * y la escritura con UpdateExamenRequest::authorize(); el listado de la pestana
 * ya oculta los examenes ajenos.
 *
 * @see  App\Services\Examen\ObtenerExamenEdicionService
 * @see  App\Services\Examen\ActualizarExamenService
 * @see  App\Http\Requests\UpdateExamenRequest
 * @see  App\Policies\ExamenPolicy::editar()
 *
 * @changelog
 * - 2026-11-24  [Equipo T1]  feat: pasar el editor al servicio para notificar
 *   cambios de ambientes, fecha y hora.
 * - 2026-11-24  [Equipo T1]  fix: una ValidationException del servicio (aula
 *   ocupada, por ejemplo) vuelve como 422 con `errors` para el formulario.
 */

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateExamenRequest;
use App\Models\Examen;
use App\Models\Usuario;
use App\Services\Examen\ActualizarExamenService;
use App\Services\Examen\ObtenerExamenEdicionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

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
     * y la validacion del formulario viven en UpdateExamenRequest; si fallan,
     * Laravel responde 403 o 422 y este metodo no llega a ejecutarse. El
     * servicio tambien puede rechazar el guardado (por ejemplo, un ambiente
     * ocupado): esa ValidationException se convierte en 422 para que el modal
     * muestre el error en el campo, igual que en el alta.
     */
    public function actualizar(
        UpdateExamenRequest $request,
        Examen $examen,
        ActualizarExamenService $actualizar,
        ObtenerExamenEdicionService $datos,
    ): JsonResponse {
        /** @var Usuario $editor */
        $editor = $request->user('web');

        try {
            $actualizar->ejecutar($examen, $request->validated(), $editor);
        } catch (ValidationException $error) {
            return response()->json([
                'message' => 'No se pudo guardar el examen.',
                'errors' => $error->errors(),
            ], 422);
        }

        return response()->json([
            'mensaje' => 'Examen actualizado con exito',
            'datos' => $datos->ejecutar($examen->refresh()),
        ]);
    }
}
