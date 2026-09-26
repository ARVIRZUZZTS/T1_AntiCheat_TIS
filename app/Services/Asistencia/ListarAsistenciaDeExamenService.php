<?php

namespace App\Services\Asistencia;

use App\Models\Examen;
use App\Models\Estudiante;
use Illuminate\Support\Collection;

class ListarAsistenciaDeExamenService
{
    /**
     * @return Collection<int, array{estudiante: Estudiante, presente: bool, hora: ?string}>
     */
    public function ejecutar(int $idExamen): Collection
    {
        $examen = Examen::with(['cursos.estudiantes', 'registrosAsistencia'])
            ->findOrFail($idExamen);

        // Un examen puede estar en varios cursos. Por ahora tomamos el primero.
        $curso = $examen->cursos->first();

        if (! $curso) {
            return collect();
        }

        $ingresos = $examen->registrosAsistencia->keyBy('id_estudiante');

        return $curso->estudiantes->map(fn (Estudiante $estudiante) => [
            'estudiante' => $estudiante,
            'presente'   => $ingresos->has($estudiante->sis_estudiante),
            'hora'       => $ingresos->get($estudiante->sis_estudiante)?->hora_ingreso,
        ]);
    }
}