<?php

/**
 * @file    ExportarReporteIngresosIlegalesService.php
 *
 * @author  Alex Candia <alex.leonar.candia@gmail.com>
 *
 * @created 2026-10-23
 * @updated 2026-10-23
 *
 * @description Exporta el reporte de intentos no autorizados usando el
 * GenerarXlsxService compartido para no duplicar la lógica de generación.
 */

namespace App\Services\Reporte;

use App\Models\Examen;
use Illuminate\Support\Str;

class ExportarReporteIngresosIlegalesService
{
    public function __construct(
        private readonly GenerarReporteIngresosIlegalesService $listar,
        private readonly GenerarXlsxService $generador,
    ) {}

    /**
     * @return array{contenido: string, nombre: string}
     */
    public function ejecutar(int $idExamen, ?int $idCurso = null): array
    {
        $filas = $this->listar->ejecutar($idExamen, $idCurso);
        $resumen = $this->listar->resumen($idExamen, $idCurso);

        $examenModel = Examen::with('cursos')->find($idExamen);
        $materia = $examenModel?->cursos?->sortBy('id_curso')->first()?->nombre_curso ?? 'materia';
        $materiaSlug = Str::slug($materia);

        $hojas = [
            [
                'nombre' => 'Resumen',
                'filas' => [
                    ['Total de intentos', $resumen['total_intentos']],
                    ['Estudiantes distintos', $resumen['estudiantes_distintos']],
                ],
            ],
            [
                'nombre' => 'Intentos',
                'filas' => array_merge([
                    ['SIS', 'Estudiante', 'Carrera', 'Materia', 'Motivo', 'Tipo', 'Estado', 'Fecha', 'Detalle'],
                ], $filas->map(fn (array $registro): array => [
                    $registro['sis'],
                    $registro['estudiante'],
                    $registro['carrera'],
                    $registro['materia'],
                    $registro['motivo'],
                    $registro['tipo'],
                    $registro['estado'],
                    $registro['fecha'],
                    $registro['detalle'],
                ])->all()),
            ],
        ];

        return [
            'contenido' => $this->generador->generar($hojas),
            'nombre' => 'reporte-ingresos-ilegales-'.$materiaSlug.'-'.now()->format('Y-m-d').'.xlsx',
        ];
    }
}
