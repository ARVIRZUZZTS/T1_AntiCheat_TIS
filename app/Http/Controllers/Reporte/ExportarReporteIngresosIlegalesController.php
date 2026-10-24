<?php

namespace App\Http\Controllers\Reporte;

use App\Http\Controllers\Controller;
use App\Services\Reporte\ExportarReporteIngresosIlegalesService;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportarReporteIngresosIlegalesController extends Controller
{
    public function __construct(
        private readonly ExportarReporteIngresosIlegalesService $servicio,
    ) {}

    public function __invoke(int $examen, ?int $curso = null): StreamedResponse
    {
        $resultado = $this->servicio->ejecutar($examen, $curso);

        return response()->streamDownload(function () use ($resultado): void {
            echo $resultado['contenido'];
        }, $resultado['nombre'], [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
