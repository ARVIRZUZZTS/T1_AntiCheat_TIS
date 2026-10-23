<?php

namespace App\Http\Controllers\Reporte;

use App\Http\Controllers\Controller;
use App\Services\Reporte\GenerarReporteIngresosIlegalesService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ListarReporteIngresosIlegalesController extends Controller
{
    public function __construct(
        private readonly GenerarReporteIngresosIlegalesService $servicio,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $examen = (int) $request->query('examen');
        $curso = $request->query('curso') !== null ? (int) $request->query('curso') : null;

        return response()->json($this->servicio->ejecutar($examen, $curso)->values());
    }
}
