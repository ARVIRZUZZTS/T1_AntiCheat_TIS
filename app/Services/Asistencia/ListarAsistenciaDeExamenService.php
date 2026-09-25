<?php

namespace App\Services\Asistencia;

use App\Models\Examen;
use App\Models\Estudiante;
use Illuminate\Support\Collection;

class ListarAsistenciaDeExamenService
{
    /**
     * Devuelve los estudiantes del examen con su estado de asistencia.
     *
     * @return Collection<int, array{estudiante: Estudiante, presente: bool, hora: ?string}>
     */
    public function ejecutar(int $idExamen): Collection
    {
        $examen = Examen::with(['curso.estudiantes', 'registrosAsistencia'])
            ->findOrFail($idExamen);

        $ingresos = $examen->registrosAsistencia
            ->keyBy('id_estudiante');

        return $examen->curso->estudiantes->map(fn (Estudiante $estudiante) => [
            'estudiante' => $estudiante,
            'presente'   => $ingresos->has($estudiante->sis_estudiante),
            'hora'       => $ingresos->get($estudiante->sis_estudiante)?->hora_ingreso,
        ]);
    }
}
