<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\BuscarDocentesInvitablesRequest;
use App\Http\Requests\EnviarInvitacionExamenRequest;
use App\Models\Examen;
use App\Models\Usuario;
use App\Services\Invitacion\BuscarDocentesInvitablesService;
use App\Services\Invitacion\EnviarInvitacionExamenService;
use Illuminate\Http\JsonResponse;

class InvitacionExamenController extends Controller
{
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
}
