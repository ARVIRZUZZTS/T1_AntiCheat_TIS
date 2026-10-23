<?php

namespace App\Http\Controllers\Reporte;

use App\Http\Controllers\Controller;
use App\Models\Examen;
use App\Services\Reporte\GenerarReporteIngresosIlegalesService;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportarReporteIngresosIlegalesController extends Controller
{
    public function __construct(
        private readonly GenerarReporteIngresosIlegalesService $servicio,
    ) {}

    public function __invoke(int $examen, ?int $curso = null): StreamedResponse
    {
        $filas = $this->servicio->ejecutar($examen, $curso);
        $resumen = $this->servicio->resumen($examen, $curso);

        $examenModel = Examen::with('cursos')->find($examen);
        $materia = $examenModel?->cursos?->sortBy('id_curso')->first()?->nombre_curso ?? 'materia';
        $horaExamen = $examenModel?->hora_inicio ? substr((string) $examenModel->hora_inicio, 0, 5) : now()->format('H:i');

        $spreadsheet = new Spreadsheet();
        $hoja = $spreadsheet->getActiveSheet();
        $hoja->setTitle('Reporte');

        $columnas = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I'];
        $encabezados = ['SIS', 'Estudiante', 'Carrera', 'Materia', 'Motivo', 'Tipo', 'Estado', 'Fecha', 'Detalle'];
        foreach ($encabezados as $i => $encabezado) {
            $hoja->setCellValue($columnas[$i].'1', $encabezado);
        }

        $fila = 2;
        foreach ($filas as $registro) {
            $hoja->setCellValue('A'.$fila, $registro['sis']);
            $hoja->setCellValue('B'.$fila, $registro['estudiante']);
            $hoja->setCellValue('C'.$fila, $registro['carrera']);
            $hoja->setCellValue('D'.$fila, $registro['materia']);
            $hoja->setCellValue('E'.$fila, $registro['motivo']);
            $hoja->setCellValue('F'.$fila, $registro['tipo']);
            $hoja->setCellValue('G'.$fila, $registro['estado']);
            $hoja->setCellValue('H'.$fila, $registro['fecha']);
            $hoja->setCellValue('I'.$fila, $registro['detalle']);
            $fila++;
        }

        $hoja->setCellValue('A'.($fila + 1), 'Total de intentos');
        $hoja->setCellValue('B'.($fila + 1), $resumen['total_intentos']);
        $hoja->setCellValue('A'.($fila + 2), 'Estudiantes distintos');
        $hoja->setCellValue('B'.($fila + 2), $resumen['estudiantes_distintos']);

        foreach (range('A', 'I') as $columna) {
            $hoja->getColumnDimension($columna)->setAutoSize(true);
        }

        $nombreArchivo = 'reporte-ingresos-ilegales-'.$materia.'-'.$horaExamen.'.xlsx';

        return response()->streamDownload(function () use ($spreadsheet): void {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $nombreArchivo, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
