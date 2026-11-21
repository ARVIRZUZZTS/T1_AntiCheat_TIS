<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\CursoDuplicadoException;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateCursoRequest;
use App\Services\Curso\ActualizarCursoService;
use App\Services\Curso\ObtenerDetalleCursoService;
use Illuminate\Http\JsonResponse;

class CursoEdicionController extends Controller
{
    public function actualizar(
        UpdateCursoRequest $request,
        ActualizarCursoService $actualizar,
        ObtenerDetalleCursoService $datos,
    ): JsonResponse {
        $curso = $request->curso();
        $cambios = $request->datos();

        if ($actualizar->existeDuplicado($curso, $cambios)) {
            $error = new CursoDuplicadoException($cambios);

            return response()->json([
                'message' => $error->getMessage(),
                'errors' => $error->errores(),
                'campos' => $error->campos(),
            ], 409);
        }

        return response()->json([
            'datos' => $datos->ejecutar($curso)->toArray(),
        ]);
    }
}
