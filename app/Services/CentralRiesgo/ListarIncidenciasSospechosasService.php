<?php

/**
 * @file    ListarIncidenciasSospechosasService.php
 *
 * @author  Alisson D. Alvarado <alvaradoalissondalet@gmail.com>
 *
 * @created 2026-11-09
 *
 * @updated 2026-11-09
 *
 * @description Lista las incidencias pendientes de revisión del docente.
 *
 * @changelog
 * - 2026-11-09  [Alisson D. Alvarado]  feat: listar sospechosos de las materias del docente.
 */

namespace App\Services\CentralRiesgo;

use App\Enums\Motivo;
use App\Enums\TipoInfraccion;
use App\Models\CentralRiesgo;
use App\Services\Examen\BusquedaEstudianteService;
use App\Support\NombreCurso;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Reúne los casos que el docente del curso puede revisar.
 *
 * @author Alisson D. Alvarado <alvaradoalissondalet@gmail.com>
 */
class ListarIncidenciasSospechosasService
{
    /**
     * @param  int  $docente  Identificador del docente autenticado.
     * @param  ?string  $busqueda  Nombre o código SIS saneado.
     * @param  bool  $ordenAlfabetico  Indica si se ordena por apellido.
     * @return Collection<int, array<string, mixed>> Incidencias pendientes.
     *
     * @throws \InvalidArgumentException Si la búsqueda no es válida.
     *
     * @author Alisson D. Alvarado <alvaradoalissondalet@gmail.com>
     */
    public function ejecutar(int $docente, ?string $busqueda, bool $ordenAlfabetico): Collection
    {
        $modo = app(BusquedaEstudianteService::class)->validar($busqueda);
        $query = CentralRiesgo::query()
            ->join('estudiante', 'estudiante.sis_estudiante', '=', 'central_riesgo.sis_estudiante')
            ->join('curso', 'curso.id_curso', '=', 'central_riesgo.id_curso')
            ->where('curso.sis_doc', $docente)
            ->where('central_riesgo.tipo_infraccion', TipoInfraccion::Sospechoso->value)
            ->select('central_riesgo.*')
            ->selectRaw('estudiante.nombre_estudiante as lista_est_nombre')
            ->selectRaw('estudiante.apellido_estudiante as lista_est_apellido')
            ->selectRaw('estudiante.verificado as lista_est_verificado')
            ->selectRaw('curso.nombre_curso as lista_curso');

        if ($modo === BusquedaEstudianteService::MODO_SIS) {
            $query->whereLike('estudiante.sis_estudiante', '%'.$busqueda.'%');
        } elseif ($modo === BusquedaEstudianteService::MODO_NOMBRE) {
            $patron = '%'.$busqueda.'%';
            $query->where(function (Builder $consulta) use ($patron): void {
                $consulta->whereLike('estudiante.nombre_estudiante', $patron)
                    ->orWhereLike('estudiante.apellido_estudiante', $patron)
                    ->orWhereRaw(
                        "lower(estudiante.nombre_estudiante || ' ' || estudiante.apellido_estudiante) like ?",
                        [mb_strtolower($patron)],
                    );
            });
        }

        MotivosIncidencia::seleccionar($query, 'lista_motivos');
        $incidencias = $query->orderByDesc('central_riesgo.fecha_registro')
            ->orderByDesc('central_riesgo.id_registro')->get();

        if ($ordenAlfabetico) {
            $incidencias = $incidencias->sortBy(fn (CentralRiesgo $incidencia): array => [
                mb_strtolower(Str::ascii((string) $incidencia->getAttribute('lista_est_apellido'))),
                mb_strtolower(Str::ascii((string) $incidencia->getAttribute('lista_est_nombre'))),
            ]);
        }

        return $incidencias->map(fn (CentralRiesgo $incidencia): array => [
            'id_registro' => $incidencia->id_registro,
            'nombre' => $incidencia->getAttribute('lista_est_nombre'),
            'apellido' => $incidencia->getAttribute('lista_est_apellido'),
            'verificado' => (bool) ($incidencia->getAttribute('lista_est_verificado') ?? true),
            'sis' => $incidencia->sis_estudiante,
            'materia' => NombreCurso::materia($incidencia->getAttribute('lista_curso')),
            'motivo_etiqueta' => Motivo::etiquetas($incidencia->getAttribute('lista_motivos')),
        ])->values();
    }
}
