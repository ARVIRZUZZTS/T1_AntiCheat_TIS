<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateCursoRequest;
use App\Services\Curso\ObtenerDetalleCursoService;
use Illuminate\Http\JsonResponse;

class CursoEdicionController extends Controller
{
    public function actualizar(
        UpdateCursoRequest $request,
        ObtenerDetalleCursoService $datos,
    ): JsonResponse {
        return response()->json([
            'datos' => $datos->ejecutar($request->curso())->toArray(),
        ]);
    }
}
