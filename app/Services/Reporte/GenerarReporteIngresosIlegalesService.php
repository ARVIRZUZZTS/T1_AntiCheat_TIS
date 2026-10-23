<?php

namespace App\Services\Reporte;

use App\Models\CentralRiesgo;
use Illuminate\Support\Collection;

class GenerarReporteIngresosIlegalesService
{
    public function ejecutar(int $idExamen, ?int $idCurso = null): Collection
    {
        $query = CentralRiesgo::query()
            ->with(['estudiante', 'examen.ambientes'])
            ->where('id_examen', $idExamen);

        if ($idCurso !== null) {
            $query->where(function ($q) use ($idCurso): void {
                $q->whereNull('id_curso')
                    ->orWhere('id_curso', $idCurso);
            });
        }

        return $query
            ->orderByDesc('fecha_registro')
            ->get()
            ->map(function (CentralRiesgo $incidencia): array {
            $estudiante = $incidencia->estudiante;
            $examen = $incidencia->examen;

            return [
                'sis' => $incidencia->sis_estudiante,
                'nombre' => $estudiante?->nombre_estudiante,
                'apellido' => $estudiante?->apellido_estudiante,
                'estudiante' => trim(($estudiante?->nombre_estudiante ?? '').' '.($estudiante?->apellido_estudiante ?? '')),
                'carrera' => $estudiante?->carrera,
                'aula' => $examen?->ambientes->first()?->nombre_ambiente,
                'motivo' => $incidencia->motivo->etiqueta(),
                'tipo' => $incidencia->tipo_infraccion->etiqueta(),
                'estado' => $incidencia->estado_incidencia->etiqueta(),
                'fecha' => $incidencia->fecha_registro?->format('d/m/Y H:i'),
                'detalle' => $incidencia->detalle_motivo,
                'materia' => $incidencia->materia(),
            ];
        });
    }

    public function resumen(int $idExamen, ?int $idCurso = null): array
    {
        $filas = $this->ejecutar($idExamen, $idCurso);

        return [
            'total_intentos' => $filas->count(),
            'estudiantes_distintos' => $filas->pluck('sis')->unique()->count(),
        ];
    }
}
