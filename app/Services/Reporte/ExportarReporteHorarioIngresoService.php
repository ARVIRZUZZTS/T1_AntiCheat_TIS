<?php

/**
 * @file    ExportarReporteHorarioIngresoService.php
 *
 * @author  Alex Candia <alex.leonar.candia@gmail.com>
 *
 * @created 2026-10-23
 *
 * @description Convierte el reporte de horarios de ingreso en un libro .xlsx
 * con hoja Resumen y hoja Detalle, y arma el nombre del archivo con la materia
 * y la fecha del examen.
 */

namespace App\Services\Reporte;

use Illuminate\Support\Str;

class ExportarReporteHorarioIngresoService
{
    public function __construct(
        private readonly GenerarXlsxService $generarXlsx,
    ) {}

    /**
     * @param  array{
     *     materia: array{id: int, nombre: string},
     *     examen: array{id: int, tipo: string, fecha: ?string, fecha_formateada: ?string, hora_inicio: ?string, duracion: ?int},
     *     aulas: list<array{id: int, nombre: string}>,
     *     filtros: array{id_aula: ?int, hora_desde: ?string, hora_hasta: ?string},
     *     resumen: array{total: int, primer_ingreso: ?string, ultimo_ingreso: ?string},
     *     estudiantes: list<array{sis: string, nombres: string, apellidos: string, aula: string, hora_ingreso: ?string, fecha_ingreso: ?string}>,
     *     sin_datos: bool
     * }  $reporte
     * @return array{nombre: string, contenido: string}
     */
    public function ejecutar(array $reporte): array
    {
        $aula = $this->filtroAula($reporte);
        $horario = $this->filtroHorario($reporte['filtros']);

        $resumen = [
            ['Reporte de horarios de ingreso'],
            [],
            ['Materia', $reporte['materia']['nombre']],
            ['Examen', $reporte['examen']['tipo'].($reporte['examen']['fecha_formateada'] === null ? '' : ' - '.$reporte['examen']['fecha_formateada'])],
            ['Fecha del examen', $reporte['examen']['fecha_formateada'] ?? 'Sin fecha'],
            ['Aula', $aula],
            ['Rango de horario', $horario],
            [],
            ['Total de estudiantes que ingresaron', $reporte['resumen']['total']],
            ['Hora del primer ingreso', $reporte['resumen']['primer_ingreso'] ?? 'Sin registros'],
            ['Hora del ultimo ingreso', $reporte['resumen']['ultimo_ingreso'] ?? 'Sin registros'],
        ];

        $detalle = [
            ['Nombres', 'Apellidos', 'Codigo SIS', 'Aula', 'Hora de ingreso', 'Fecha del examen'],
        ];

        foreach ($reporte['estudiantes'] as $estudiante) {
            $detalle[] = [
                $estudiante['nombres'],
                $estudiante['apellidos'],
                $estudiante['sis'],
                $estudiante['aula'],
                $estudiante['hora_ingreso'] ?? 'Sin registro',
                $reporte['examen']['fecha_formateada'] ?? 'Sin fecha',
            ];
        }

        return [
            'nombre' => $this->nombreArchivo($reporte),
            'contenido' => $this->generarXlsx->generar([
                ['nombre' => 'Resumen', 'filas' => $resumen],
                ['nombre' => 'Detalle', 'filas' => $detalle],
            ]),
        ];
    }

    /**
     * @param  array{materia: array{nombre: string}, examen: array{fecha: ?string}}  $reporte
     */
    private function nombreArchivo(array $reporte): string
    {
        $materia = Str::slug($reporte['materia']['nombre']);
        $fecha = $reporte['examen']['fecha'] ?? 'sin-fecha';

        return 'reporte-horarios-'.$materia.'-'.$fecha.'.xlsx';
    }

    /**
     * @param  array{
     *     aulas: list<array{id: int, nombre: string}>,
     *     filtros: array{id_aula: ?int, hora_desde: ?string, hora_hasta: ?string}
     * }  $reporte
     */
    private function filtroAula(array $reporte): string
    {
        if ($reporte['filtros']['id_aula'] === null) {
            return 'Todas las aulas';
        }

        foreach ($reporte['aulas'] as $aula) {
            if ($aula['id'] === $reporte['filtros']['id_aula']) {
                return $aula['nombre'];
            }
        }

        return 'Aula seleccionada';
    }

    /**
     * @param  array{id_aula: ?int, hora_desde: ?string, hora_hasta: ?string}  $filtros
     */
    private function filtroHorario(array $filtros): string
    {
        if ($filtros['hora_desde'] === null && $filtros['hora_hasta'] === null) {
            return 'Todos los ingresos del examen';
        }

        return ($filtros['hora_desde'] ?? 'Inicio').' a '.($filtros['hora_hasta'] ?? 'Fin');
    }
}
