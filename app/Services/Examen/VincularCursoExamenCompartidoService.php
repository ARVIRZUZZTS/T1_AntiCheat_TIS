<?php

namespace App\Services\Examen;

use App\Enums\EstadoEstudianteExamen;
use App\Livewire\Monitoreo\MonitorEnVivo;
use App\Models\ConfiguracionExamenDocente;
use App\Models\Curso;
use App\Models\EstudianteExamen;
use App\Models\Examen;
use App\Models\Usuario;
use App\Services\Curso\AsegurarCursoEditableService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class VincularCursoExamenCompartidoService
{
    public function __construct(
        private readonly AsegurarCursoEditableService $editable,
    ) {}

    public function ejecutar(Usuario $docente, Examen $examen, Curso $curso): int
    {
        Gate::forUser($docente)->authorize('configurar', $examen);
        Gate::forUser($docente)->authorize('gestionar', $curso);
        $this->editable->asegurarExamenEditable($examen, 'examen');
        $this->editable->asegurarCursoEditable($curso, 'curso');

        $configuracion = ConfiguracionExamenDocente::query()
            ->where('id_examen', $examen->id_examen)
            ->delDocente((int) $docente->id_usuario)
            ->firstOrFail();

        return DB::transaction(function () use ($examen, $curso, $configuracion): int {
            $examen->cursos()->syncWithoutDetaching([$curso->id_curso]);

            // Inscripcion en bloque: unas pocas consultas sin importar cuantos
            // estudiantes tenga el curso (antes eran dos por estudiante).
            $yaInscritos = EstudianteExamen::query()
                ->where('id_examen', $examen->id_examen)
                ->where('id_configuracion_examen', $configuracion->id_configuracion_examen)
                ->pluck('sis_estudiante')
                ->flip();

            $nuevos = $curso->estudiantes()
                ->orderBy('estudiante.sis_estudiante')
                ->pluck('estudiante.sis_estudiante')
                ->reject(fn (string $sis): bool => $yaInscritos->has($sis))
                ->values();

            if ($nuevos->isEmpty()) {
                $creadas = 0;
            } else {
                $siguienteId = (int) EstudianteExamen::query()->max('id_estudiante_examen') + 1;

                EstudianteExamen::query()->insert($nuevos->map(fn (string $sis, int $indice): array => [
                    'id_estudiante_examen' => $siguienteId + $indice,
                    'sis_estudiante' => $sis,
                    'id_examen' => $examen->id_examen,
                    'id_curso' => $curso->id_curso,
                    'id_configuracion_examen' => $configuracion->id_configuracion_examen,
                    'estado' => EstadoEstudianteExamen::Habilitado->value,
                ])->all());

                $creadas = $nuevos->count();
            }

            Cache::forget(
                MonitorEnVivo::CACHE_INFORME_PREFIJO.$examen->id_examen
                .'.docente.'.$configuracion->id_docente
            );

            return $creadas;
        });
    }
}
