<?php

namespace App\Http\Controllers\Reporte;

use App\Http\Controllers\Controller;
use App\Services\Reporte\ExportarReporteAsistenciaService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportarReporteAsistenciaController extends Controller
{
    public function __construct(
        private readonly ExportarReporteAsistenciaService $servicio,
    ) {}

    public function exportar(Request $request, int $examen): StreamedResponse|\Illuminate\Http\JsonResponse
    {
        $curso = $request->query('curso') !== null ? (int) $request->query('curso') : null;
        $datos = $this->servicio->ejecutar($examen, $curso);

        if (empty($datos['estudiantes'])) {
            return response()->json([
                'message' => 'No hay datos para exportar.',
            ], 422);
        }

        $archivo = $datos['archivo'];

        return response()->streamDownload(
            fn () => print $archivo['contenido'],
            $archivo['nombre'],
            [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Cache-Control' => 'no-store, no-cache',
            ],
        );
    }
}
