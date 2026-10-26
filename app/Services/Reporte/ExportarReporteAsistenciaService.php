<?php

/**
 * @file    ExportarReporteAsistenciaService.php
 *
 * @author  Candy <camitkdos@gmail.com>
 *
 * @created 2026-10-25
 *
 * @updated 2026-10-26
 *
 * @description
 * Servicio que arma el Excel del reporte de asistencia de un examen a partir
 * de `GenerarReporteAsistenciaService`: hoja de resumen por aula y docente,
 * detalle de presentes y detalle de ausentes, empaquetado por
 * `GenerarXlsxService`.
 *
 * @changelog
 * - 2026-10-25  [Candy]  feat: creación inicial con exportación a Excel del
 *   reporte de asistencia (resumen, presentes y ausentes).
 * - 2026-10-26  [OchoaCesar]  fix: la columna "Docente a cargo" toma
 *   `docente_cargo` (el docente real de la configuración del estudiante) en
 *   vez del registrador: salía "Sin registro" para los ingresos aún no
 *   confirmados en el monitor.
 *
 * @see  App\Services\Monitoreo\GenerarReporteAsistenciaService
 * @see  App\Services\Reporte\GenerarXlsxService
 */

namespace App\Services\Reporte;

use App\Services\Monitoreo\GenerarReporteAsistenciaService;
use Illuminate\Support\Str;

class ExportarReporteAsistenciaService
{
    public function __construct(
        private readonly GenerarReporteAsistenciaService $generar,
        private readonly GenerarXlsxService $generador,
    ) {}

    public function ejecutar(int $idExamen, ?int $idCurso = null): array
    {
        $reporte = $this->generar->ejecutar($idExamen);
        $estudiantes = $reporte['estudiantes'];
        $examenModel = $reporte['examen'];
        $materia = $examenModel->cursos?->sortBy('id_curso')->first()?->nombre_curso ?? 'materia';
        $materiaSlug = Str::slug($materia);

        $resumen = [
            ['Resumen por aula/docente'],
            ['Aula', 'Docente a cargo', 'Registrados', 'Presentes', 'Ausentes'],
        ];

        $agrupado = [];
        foreach ($estudiantes as $e) {
            $aula = $e['aula'] ?? ($e['aula_asignada'] ?? 'Sin aula');
            if (is_array($aula)) {
                $aula = implode(', ', $aula);
            }
            $docente = $e['docente_cargo'] ?? ($e['registrador'] ?? '');
            if (is_array($docente)) {
                $docente = trim(($docente['nombre_usuario'] ?? '').' '.($docente['apellido'] ?? ''));
            }
            $clave = $aula.'|'.$docente;
            if (! isset($agrupado[$clave])) {
                $agrupado[$clave] = ['aula' => $aula, 'docente' => $docente ?: 'Sin docente', 'registrados' => 0, 'presentes' => 0, 'ausentes' => 0];
            }
            $agrupado[$clave]['registrados']++;
            if (($e['estado_asistencia'] ?? '') === 'presente') {
                $agrupado[$clave]['presentes']++;
            } else {
                $agrupado[$clave]['ausentes']++;
            }
        }
        foreach ($agrupado as $fila) {
            $resumen[] = [$fila['aula'], $fila['docente'], $fila['registrados'], $fila['presentes'], $fila['ausentes']];
        }

        $detallePresentes = [
            ['Detalle de presentes'],
            ['Nombres', 'Apellidos', 'Código SIS', 'Aula', 'Hora de ingreso'],
        ];
        foreach ($estudiantes as $e) {
            if (($e['estado_asistencia'] ?? '') === 'presente') {
                $aula = $e['aula'] ?? 'Sin aula';
                if (is_array($aula)) {
                    $aula = implode(', ', $aula);
                }
                $detallePresentes[] = [
                    $e['nombre'] ?? '',
                    $e['apellido'] ?? '',
                    $e['sis'] ?? '',
                    $aula,
                    $e['hora_ingreso'] ?? '',
                ];
            }
        }

        $detalleAusentes = [
            ['Detalle de ausentes'],
            ['Nombres', 'Apellidos', 'Código SIS', 'Aula'],
        ];
        foreach ($estudiantes as $e) {
            if (($e['estado_asistencia'] ?? '') !== 'presente') {
                $aula = $e['aula'] ?? 'Sin aula';
                if (is_array($aula)) {
                    $aula = implode(', ', $aula);
                }
                $detalleAusentes[] = [
                    $e['nombre'] ?? '',
                    $e['apellido'] ?? '',
                    $e['sis'] ?? '',
                    $aula,
                ];
            }
        }

        $hojas = [
            ['nombre' => 'Resumen por aula y docente', 'filas' => $resumen],
            ['nombre' => 'Presentes', 'filas' => $detallePresentes],
            ['nombre' => 'Ausentes', 'filas' => $detalleAusentes],
        ];

        return [
            'estudiantes' => $estudiantes,
            'archivo' => [
                'contenido' => $this->generador->generar($hojas),
                'nombre' => 'reporte-asistencia-'.$materiaSlug.'-'.($examenModel->fecha ?? now()->format('Y-m-d')).'.xlsx',
            ],
        ];
    }
}
