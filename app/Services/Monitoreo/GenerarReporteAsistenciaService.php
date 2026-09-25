<?php

namespace App\Services\Monitoreo;

use App\Models\EstudianteExamen;
use App\Models\Examen;
use App\Models\RegistroAsistencia;
use Carbon\Carbon;

class GenerarReporteAsistenciaService
{
    public function ejecutar(int $idExamen, ?Carbon $ahora = null): array
    {
        $examen = Examen::findOrFail($idExamen);
        $ahora = $ahora ?? Carbon::now();
        $inicio = Carbon::parse($examen->fecha.' '.$examen->hora_inicio);
        $fin = Carbon::parse($examen->fecha.' '.$examen->hora_fin);

        $registros = RegistroAsistencia::query()
            ->where('id_examen', $examen->id_examen)
            ->with('registrador')
            ->get()
            ->keyBy('id_estudiante');

        $estudiantes = EstudianteExamen::query()
            ->where('id_examen', $examen->id_examen)
            ->with('estudiante')
            ->get()
            ->map(function (EstudianteExamen $inscripcion) use ($registros, $ahora, $inicio, $fin) {
                $estudiante = $inscripcion->estudiante;
                $registro = $registros->get($estudiante->sis_estudiante);

                [$estado, $observacion] = $this->resolverAsistencia($inscripcion, $registro, $ahora, $inicio, $fin);

                return [
                    'sis' => $estudiante->sis_estudiante,
                    'nombre' => $estudiante->nombre_estudiante,
                    'apellido' => $estudiante->apellido_estudiante,
                    'registrador' => $estado === 'presente' && $registro !== null && $registro->registrador !== null
                        ? trim($registro->registrador->nombre_usuario.' '.$registro->registrador->apellido)
                        : null,
                    'hora_ingreso' => $estado === 'presente' && $registro !== null
                        ? $registro->hora_ingreso
                        : null,
                    'estado_asistencia' => $estado,
                    'observaciones' => $observacion,
                ];
            });

        return [
            'examen' => $examen,
            'estudiantes' => $estudiantes,
        ];
    }

    private function resolverAsistencia(EstudianteExamen $inscripcion, ?RegistroAsistencia $registro, Carbon $ahora, Carbon $inicio, Carbon $fin): array
    {
        $observacion = $inscripcion->estado === 'deshabilitado' ? 'deshabilitado' : 'habilitado';

        if ($ahora->lt($inicio)) {
            return ['ausente', $observacion];
        }

        if ($registro !== null) {
            return ['presente', $observacion];
        }

        if ($ahora->lte($fin)) {
            return ['pendiente', $observacion];
        }

        return ['ausente', $observacion];
    }
}
