<?php

namespace App\Services\Examen;

use App\Livewire\Monitoreo\MonitorEnVivo;
use App\Models\ConfiguracionExamenDocente;
use App\Models\Examen;
use App\Models\Usuario;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class ConfiguracionExamenDocenteService
{
    public function obtener(Usuario $docente, Examen $examen): ConfiguracionExamenDocente
    {
        Gate::forUser($docente)->authorize('configurar', $examen);

        return ConfiguracionExamenDocente::query()
            ->delDocente((int) $docente->id_usuario)
            ->where('id_examen', $examen->id_examen)
            ->with(['normas', 'materialesPermitidos', 'normasPersonalizadas', 'materialesPersonalizados'])
            ->firstOrFail();
    }

    /** @param array<string, mixed> $datos */
    public function guardar(Usuario $docente, Examen $examen, array $datos): ConfiguracionExamenDocente
    {
        $configuracion = $this->obtener($docente, $examen);

        return DB::transaction(function () use ($configuracion, $datos): ConfiguracionExamenDocente {
            $configuracion->normas()->sync($datos['normas'] ?? []);
            $configuracion->materialesPermitidos()->sync($datos['materiales'] ?? []);

            $configuracion->normasPersonalizadas()->delete();
            foreach (array_values($datos['normas_personalizadas'] ?? []) as $indice => $descripcion) {
                $configuracion->normasPersonalizadas()->create([
                    'numero_norma' => $indice + 1,
                    'descripcion_norma' => trim((string) $descripcion),
                ]);
            }

            $configuracion->materialesPersonalizados()->delete();
            foreach (array_values($datos['materiales_personalizados'] ?? []) as $indice => $descripcion) {
                $configuracion->materialesPersonalizados()->create([
                    'numero_material' => $indice + 1,
                    'descripcion_material' => trim((string) $descripcion),
                ]);
            }

            Cache::forget(
                MonitorEnVivo::CACHE_INFORME_PREFIJO.$configuracion->id_examen
                .'.docente.'.$configuracion->id_docente
            );

            return $configuracion->fresh([
                'normas',
                'materialesPermitidos',
                'normasPersonalizadas',
                'materialesPersonalizados',
            ]);
        });
    }

    public function crearPara(
        Examen $examen,
        int $idDocente,
        ?ConfiguracionExamenDocente $origen = null,
    ): ConfiguracionExamenDocente {
        return DB::transaction(function () use ($examen, $idDocente, $origen): ConfiguracionExamenDocente {
            $configuracion = ConfiguracionExamenDocente::query()->firstOrCreate([
                'id_examen' => $examen->id_examen,
                'id_docente' => $idDocente,
            ]);

            if (! $configuracion->wasRecentlyCreated) {
                return $configuracion;
            }

            if ($origen !== null) {
                $origen->loadMissing([
                    'normas',
                    'materialesPermitidos',
                    'normasPersonalizadas',
                    'materialesPersonalizados',
                ]);
                $configuracion->normas()->sync($origen->normas->modelKeys());
                $configuracion->materialesPermitidos()->sync($origen->materialesPermitidos->modelKeys());

                foreach ($origen->normasPersonalizadas as $norma) {
                    $configuracion->normasPersonalizadas()->create([
                        'numero_norma' => $norma->numero_norma,
                        'descripcion_norma' => $norma->descripcion_norma,
                    ]);
                }
                foreach ($origen->materialesPersonalizados as $material) {
                    $configuracion->materialesPersonalizados()->create([
                        'numero_material' => $material->numero_material,
                        'descripcion_material' => $material->descripcion_material,
                    ]);
                }
            } else {
                $configuracion->normas()->sync($examen->normas()->pluck('norma.id_norma'));
                $configuracion->materialesPermitidos()->sync(
                    $examen->materialesPermitidos()->pluck('material.id_material')
                );

                foreach ($examen->normasPersonalizadas()->orderBy('numero_norma')->get() as $norma) {
                    $configuracion->normasPersonalizadas()->create([
                        'numero_norma' => $norma->numero_norma,
                        'descripcion_norma' => $norma->descripcion_norma,
                    ]);
                }
                foreach ($examen->materialesPersonalizados()->orderBy('numero_material')->get() as $material) {
                    $configuracion->materialesPersonalizados()->create([
                        'numero_material' => $material->numero_material,
                        'descripcion_material' => $material->descripcion_material,
                    ]);
                }
            }

            return $configuracion;
        });
    }
}
