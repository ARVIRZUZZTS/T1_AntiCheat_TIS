<?php

namespace App\Services\Examen;

use App\Enums\EstadoEstudianteExamen;
use App\Livewire\Monitoreo\MonitorEnVivo;
use App\Models\ConfiguracionExamenDocente;
use App\Models\Curso;
use App\Models\EstudianteExamen;
use App\Models\Examen;
use App\Models\Usuario;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class VincularCursoExamenCompartidoService
{
    public function ejecutar(Usuario $docente, Examen $examen, Curso $curso): int
    {
        Gate::forUser($docente)->authorize('configurar', $examen);
        Gate::forUser($docente)->authorize('gestionar', $curso);

        $configuracion = ConfiguracionExamenDocente::query()
            ->where('id_examen', $examen->id_examen)
            ->delDocente((int) $docente->id_usuario)
            ->firstOrFail();

        return DB::transaction(function () use ($examen, $curso, $configuracion): int {
            $examen->cursos()->syncWithoutDetaching([$curso->id_curso]);

            $siguienteId = (int) EstudianteExamen::query()->max('id_estudiante_examen') + 1;
            $creadas = 0;

            foreach ($curso->estudiantes()->orderBy('estudiante.sis_estudiante')->get() as $estudiante) {
                $existe = EstudianteExamen::query()
                    ->where('id_examen', $examen->id_examen)
                    ->where('sis_estudiante', $estudiante->sis_estudiante)
                    ->where('id_configuracion_examen', $configuracion->id_configuracion_examen)
                    ->exists();

                if ($existe) {
                    continue;
                }

                EstudianteExamen::query()->create([
                    'id_estudiante_examen' => $siguienteId++,
                    'sis_estudiante' => $estudiante->sis_estudiante,
                    'id_examen' => $examen->id_examen,
                    'id_curso' => $curso->id_curso,
                    'id_configuracion_examen' => $configuracion->id_configuracion_examen,
                    'estado' => EstadoEstudianteExamen::Habilitado->value,
                ]);
                $creadas++;
            }

            Cache::forget(
                MonitorEnVivo::CACHE_INFORME_PREFIJO.$examen->id_examen
                .'.docente.'.$configuracion->id_docente
            );

            return $creadas;
        });
    }
}
