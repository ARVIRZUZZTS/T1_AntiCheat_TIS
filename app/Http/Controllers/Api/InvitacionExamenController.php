<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\BuscarDocentesInvitablesRequest;
use App\Models\Examen;
use App\Models\Usuario;
use App\Services\Invitacion\BuscarDocentesInvitablesService;
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
}
