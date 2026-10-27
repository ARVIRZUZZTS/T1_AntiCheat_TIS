<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\BuscarDocentesInvitablesRequest;
use App\Http\Requests\EnviarInvitacionExamenRequest;
use App\Models\Examen;
use App\Models\InvitacionExamenCompartido;
use App\Models\Usuario;
use App\Services\Invitacion\BuscarDocentesInvitablesService;
use App\Services\Invitacion\CancelarInvitacionExamenService;
use App\Services\Invitacion\ListarInvitacionesExamenService;
use App\Services\Invitacion\EnviarInvitacionExamenService;
use App\Services\Invitacion\ResponderInvitacionExamenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InvitacionExamenController extends Controller
{
    public function index(
        Request $request,
        Examen $examen,
        ListarInvitacionesExamenService $servicio,
    ): JsonResponse {
        /** @var Usuario $usuario */
        $usuario = $request->user('web');

        return response()->json([
            'data' => $servicio->ejecutar($usuario, $examen)->values(),
        ]);
    }

    public function docentesInvitables(
        BuscarDocentesInvitablesRequest $request,
        Examen $examen,
        BuscarDocentesInvitablesService $servicio,
    ): JsonResponse {
        /** @var Usuario $usuario */
        $usuario = $request->user('web');

        return response()->json([
            'data' => $servicio->ejecutar(
                $usuario,
                $examen,
                (string) $request->validated('busqueda'),
            )->values(),
        ]);
    }

    public function enviar(
        EnviarInvitacionExamenRequest $request,
        Examen $examen,
        EnviarInvitacionExamenService $servicio,
    ): JsonResponse {
        /** @var Usuario $usuario */
        $usuario = $request->user('web');

        return response()->json([
            'mensaje' => 'Invitación enviada correctamente.',
            'data' => $servicio->ejecutar(
                $usuario,
                $examen,
                (int) $request->validated('docente_invitado_id'),
            ),
        ], 201);
    }

    public function aceptar(
        Request $request,
        InvitacionExamenCompartido $invitacion,
        ResponderInvitacionExamenService $servicio,
    ): JsonResponse {
        /** @var Usuario $usuario */
        $usuario = $request->user('web');

        return response()->json([
            'mensaje' => 'Invitación aceptada.',
            'data' => $servicio->aceptar($usuario, $invitacion),
        ]);
    }

    public function rechazar(
        Request $request,
        InvitacionExamenCompartido $invitacion,
        ResponderInvitacionExamenService $servicio,
    ): JsonResponse {
        /** @var Usuario $usuario */
        $usuario = $request->user('web');

        return response()->json([
            'mensaje' => 'Invitación rechazada.',
            'data' => $servicio->rechazar($usuario, $invitacion),
        ]);
    }

    public function cancelar(
        Request $request,
        InvitacionExamenCompartido $invitacion,
        CancelarInvitacionExamenService $servicio,
    ): JsonResponse {
        /** @var Usuario $usuario */
        $usuario = $request->user('web');

        return response()->json([
            'mensaje' => 'Invitación cancelada.',
            'data' => $servicio->cancelar($usuario, $invitacion),
        ]);
    }
}
