<?php

/**
 * @file    ListarFiltrosReporteService.php
 *
 * @author  Alex Candia <alex.leonar.candia@gmail.com>
 *
 * @created 2026-10-21
 *
 * @description
 * Arma los datos de los dos selectores de la pantalla de reportes: la lista de
 * materias (`id_curso => nombre_curso`) y los exámenes de cada curso
 * (`id_curso => [id, etiqueta]`). La etiqueta del examen ya viene legible
 * ("Examen final — 20/10/2026"), resuelta con el enum de tipo y la fecha.
 *
 * Todo se resuelve en pocas consultas agrupadas (una por materias, una por
 * exámenes): nunca una consulta por curso, aunque la lista de materias crezca.
 *
 * La lógica vive acá y no en `routes/web.php` porque las rutas son solo la
 * capa HTTP de entrada: no deben contener consultas ni reglas de negocio
 * (ver arquitectura.md, secciones 4 y 8).
 *
 * @see  App\Services\Curso\ListarMateriasConMetricasService
 * @see  App\Enums\TipoExamen
 * @see  resources/views/pages/reportes.blade.php
 *
 * @changelog
 * - 2026-10-21  [Alex Candia]  feat: creación inicial del servicio.
 */

namespace App\Services\Reporte;

use App\Enums\TipoExamen;
use App\Services\Curso\ListarMateriasConMetricasService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ListarFiltrosReporteService
{
    public function __construct(
        private readonly ListarMateriasConMetricasService $listarMaterias,
    ) {}

    /**
     * Datos listos para los selectores de la vista de reportes.
     *
     * @return array{
     *     opcionesMaterias: array<int, string>,
     *     examenesPorCurso: array<int, list<array{id: int, etiqueta: string}>>
     * }
     */
    public function ejecutar(?int $idDocente = null): array
    {
        $idsCursos = null;

        if ($idDocente !== null) {
            $idsCursos = \App\Models\Curso::query()
                ->where('sis_doc', $idDocente)
                ->pluck('id_curso')
                ->all();
        }

        return [
            'opcionesMaterias' => $this->opcionesMaterias($idsCursos),
            'examenesPorCurso' => $this->examenesPorCurso($idsCursos),
        ];
    }

    /**
     * Materias en el formato que espera `x-ui.select` (id => nombre), tomadas
     * del mismo servicio que usa la página `/materias`.
     *
     * @return array<int, string>
     */
    private function opcionesMaterias(?array $idsCursos = null): array
    {
        return $this->listarMaterias->ejecutar()
            ->filter(fn (array $fila): bool => $idsCursos === null || in_array($fila['curso']->id_curso, $idsCursos, true))
            ->mapWithKeys(fn (array $fila): array => [
                $fila['curso']->id_curso => $fila['curso']->nombre_curso,
            ])
            ->all();
    }

    /**
     * Exámenes de todos los cursos en una sola consulta, agrupados por curso.
     * Un examen puede estar compartido entre cursos, por eso se agrupa por la
     * fila de `examen_curso` y no por `id_examen`.
     *
     * @return array<int, list<array{id: int, etiqueta: string}>>
     */
    private function examenesPorCurso(?array $idsCursos = null): array
    {
        $consulta = DB::table('examen')
            ->join('examen_curso', 'examen_curso.id_examen', '=', 'examen.id_examen')
            ->leftJoin('tipo_examen', 'tipo_examen.id_tipo_examen', '=', 'examen.tipo_examen')
            ->select(
                'examen_curso.id_curso',
                'examen.id_examen',
                'examen.fecha',
                'tipo_examen.nombre_tipo_examen',
            );

        if ($idsCursos !== null) {
            $consulta->whereIn('examen_curso.id_curso', $idsCursos);
        }

        return $consulta
            ->orderByDesc('examen.fecha')
            ->get()
            ->groupBy('id_curso')
            ->map(fn ($examenesDelCurso): array => $examenesDelCurso
                ->map(fn (object $examen): array => [
                    'id' => (int) $examen->id_examen,
                    'etiqueta' => $this->etiquetaExamen($examen),
                ])
                ->values()
                ->all())
            ->all();
    }

    /**
     * Etiqueta legible del examen: tipo (mayúscula inicial) + fecha.
     *
     * @param  object{nombre_tipo_examen: ?string, fecha: ?string}  $examen
     */
    private function etiquetaExamen(object $examen): string
    {
        $tipo = TipoExamen::etiquetaDe($examen->nombre_tipo_examen) ?? 'Sin tipo';
        $fecha = $examen->fecha === null
            ? 'Sin fecha'
            : Carbon::parse($examen->fecha)->format('d/m/Y');

        return $tipo.' — '.$fecha;
    }
}
