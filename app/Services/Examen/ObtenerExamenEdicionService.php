<?php

/**
 * @file    ObtenerExamenEdicionService.php
 *
 * @author  Alex Candia <alex.leonar.candia@gmail.com>
 *
 * @created 2026-11-05
 *
 * @description
 * Devuelve los datos de un examen en el mismo formato que emite el formulario
 * de alta (fecha dd/mm/aaaa, hora H:i, ids de ambientes/materiales/normas y un
 * renglon por material/norma personalizada), para precargar la pestana Editar
 * Examen sin duplicar en el navegador la conversion de fechas ni el orden de lo
 * escrito a mano.
 *
 * Es de solo lectura.
 *
 * @see  App\Http\Controllers\Api\ExamenEdicionController
 * @see  resources/views/components/ui/form-crear-examen.blade.php
 */

namespace App\Services\Examen;

use App\Models\Ambiente;
use App\Models\Examen;
use App\Models\Material;
use App\Models\Norma;
use Carbon\Carbon;
use InvalidArgumentException;

class ObtenerExamenEdicionService
{
    /**
     * @return array{
     *     id: int,
     *     tipo_examen: string|null,
     *     fecha: string,
     *     hora_inicio: string|null,
     *     duracion: int|null,
     *     ambientes: list<int>,
     *     materiales: list<int>,
     *     normas: list<int>,
     *     materiales_personalizados: string,
     *     normas_personalizadas: string
     * }
     *
     * @throws InvalidArgumentException Si la fecha guardada no se puede interpretar.
     */
    public function ejecutar(Examen $examen): array
    {
        $examen->load([
            'tipoExamen',
            'ambientes',
            'materialesPermitidos',
            'normas',
            'materialesPersonalizados',
            'normasPersonalizadas',
        ]);

        return [
            'id' => (int) $examen->id_examen,
            'tipo_examen' => $examen->tipoExamen?->nombre_tipo_examen,
            'fecha' => $this->fechaFormulario($examen->fecha),
            'hora_inicio' => $examen->hora_inicio === null
                ? null
                : substr($examen->hora_inicio, 0, 5),
            'duracion' => $examen->duracion === null ? null : (int) $examen->duracion,
            'ambientes' => $examen->ambientes
                ->sortBy('id_ambiente')
                ->map(fn (Ambiente $ambiente): int => (int) $ambiente->id_ambiente)
                ->values()
                ->all(),
            'materiales' => $examen->materialesPermitidos
                ->sortBy('id_material')
                ->map(fn (Material $material): int => (int) $material->id_material)
                ->values()
                ->all(),
            'normas' => $examen->normas
                ->sortBy('id_norma')
                ->map(fn (Norma $norma): int => (int) $norma->id_norma)
                ->values()
                ->all(),
            'materiales_personalizados' => $examen->materialesPersonalizados
                ->sortBy('numero_material')
                ->pluck('descripcion_material')
                ->implode("\n"),
            'normas_personalizadas' => $examen->normasPersonalizadas
                ->sortBy('numero_norma')
                ->pluck('descripcion_norma')
                ->implode("\n"),
        ];
    }

    private function fechaFormulario(?string $fecha): string
    {
        if ($fecha === null || $fecha === '') {
            return '';
        }

        try {
            return Carbon::parse($fecha)->format('d/m/Y');
        } catch (\Throwable) {
            throw new InvalidArgumentException("La fecha {$fecha} no se puede interpretar.");
        }
    }
}
