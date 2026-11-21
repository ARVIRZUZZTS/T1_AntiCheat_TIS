<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Usuario;
use App\Services\Curso\ObtenerCursoEditableService;
use App\Services\Curso\ObtenerDetalleCursoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CursoDetalleController extends Controller
{
    public function mostrar(
        Request $request,
        int $curso,
        ObtenerCursoEditableService $obtener,
        ObtenerDetalleCursoService $detalle,
    ): JsonResponse {
        $docente = $request->user();
        abort_unless($docente instanceof Usuario, 401);

        return response()->json([
            'datos' => $detalle->ejecutar($obtener->ejecutar($curso, $docente))->toArray(),
        ]);
    }
}
