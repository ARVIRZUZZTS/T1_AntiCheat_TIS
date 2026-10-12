<?php

/**
 * @file    NotificacionController.php
 *
 * @author  T1 - Tech One SRL
 *
 * @created 2026-10-11
 *
 * @description
 * Endpoints JSON de las notificaciones del docente. Sirven a las dos HU que
 * generan avisos (el campo `tipo` del payload indica cuál):
 *
 *   - `posible_tramposo` (HU 12): el auxiliar reportó a un sospechoso.
 *   - `central_riesgo` (HU 6): el estudiante ingresó o fue confirmado en la
 *     central de riesgos.
 *
 * - `index`   GET  `/api/notificaciones` — lista las notificaciones no ocultas
 *   del usuario autenticado, de la más reciente a la más antigua. Lista vacía
 *   (200, `[]`) cuando no hay ninguna.
 * - `ocultar` PATCH `/api/notificaciones/{id}/ocultar` — borrado lógico de una
 *   notificación propia. 404 si no existe, 403 si es de otro usuario.
 *
 * Es delgado: resuelve el usuario de la sesión y delega en el servicio.
 *
 * @see  App\Services\Notificacion\NotificacionService
 *
 * @changelog
 * - 2026-10-11  [T1]  feat: endpoints de notificaciones del docente (#HU12).
 * - 2026-10-11  [David E. Chavez T.]  docs: los endpoints también sirven los
 *   avisos "central_riesgo" de la HU 6 (#HU6).
 */

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Usuario;
use App\Services\Notificacion\NotificacionService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificacionController extends Controller
{
    /**
     * Notificaciones del usuario autenticado, más reciente primero.
     */
    public function index(Request $request, NotificacionService $servicio): JsonResponse
    {
        /** @var Usuario $usuario */
        $usuario = $request->user('web');

        return response()->json($servicio->listarPara($usuario)->values());
    }

    /**
     * Oculta (soft) una notificación propia.
     */
    public function ocultar(int $idNotificacion, Request $request, NotificacionService $servicio): JsonResponse
    {
        /** @var Usuario $usuario */
        $usuario = $request->user('web');

        try {
            $servicio->ocultar($idNotificacion, $usuario);
        } catch (ModelNotFoundException) {
            return response()->json(['mensaje' => 'La notificación no existe.'], 404);
        } catch (AuthorizationException) {
            return response()->json(['mensaje' => 'No puede ocultar una notificación que no es suya.'], 403);
        }

        return response()->json(['mensaje' => 'Notificación ocultada correctamente.']);
    }
}
