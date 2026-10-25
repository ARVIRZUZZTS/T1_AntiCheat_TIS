<?php

/**
 * @file    ConsultarReporteHorarioIngresoService.php
 *
 * @author  Alex Candia <alex.leonar.candia@gmail.com>
 *
 * @created 2026-10-23
 *
 * @description Arma el reporte de horarios de ingreso de un examen: resumen
 * (total, primer y ultimo ingreso), aulas disponibles y estudiantes ordenados
 * por hora de ingreso, con filtros opcionales por aula y rango horario.
 *
 * La hora se toma de `registro_asistencia.hora_ingreso` (solo hora). Si un
 * estudiante no tiene aula asignada en `estudiante_examen_ambiente` se usan
 * los ambientes del examen, para que el reporte nunca quede sin aula.
 *
 * @see  App\Policies\ExamenPolicy
 * @see  App\Services\Reporte\ExportarReporteHorarioIngresoService
 */

namespace App\Services\Reporte;

use App\Enums\TipoExamen;
use App\Models\Ambiente;
use App\Models\Curso;
use App\Models\Examen;
use App\Models\Usuario;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class ConsultarReporteHorarioIngresoService
{
    /**
     * @return array{
     *     materia: array{id: int, nombre: string},
     *     examen: array{id: int, tipo: string, fecha: ?string, fecha_formateada: ?string, hora_inicio: ?string, duracion: ?int},
     *     aulas: list<array{id: int, nombre: string}>,
     *     filtros: array{id_aula: ?int, hora_desde: ?string, hora_hasta: ?string},
     *     resumen: array{total: int, primer_ingreso: ?string, ultimo_ingreso: ?string},
     *     estudiantes: list<array{sis: string, nombres: string, apellidos: string, aula: string, hora_ingreso: ?string, hora_salida: ?string, fecha_ingreso: ?string}>,
     *     sin_datos: bool
     * }
     */
    public function ejecutar(
        Usuario $docente,
        int $idCurso,
        int $idExamen,
        ?int $idAula = null,
        ?string $horaDesde = null,
        ?string $horaHasta = null,
    ): array {
        $curso = Curso::query()->findOrFail($idCurso);
        $examen = Examen::query()->with('tipoExamen')->findOrFail($idExamen);

        Gate::forUser($docente)->authorize('reporteHorarioIngreso', [$examen, $idCurso]);

        $aulasExamen = $this->aulasDelExamen($examen);
        $estudiantes = $this->estudiantes($idExamen, $idCurso, $aulasExamen, $idAula, $horaDesde, $horaHasta);
        $horas = $estudiantes->pluck('hora_ingreso_orden')->filter()->values();

        return [
            'materia' => [
                'id' => (int) $curso->id_curso,
                'nombre' => $curso->nombre_curso,
            ],
            'examen' => [
                'id' => (int) $examen->id_examen,
                'tipo' => TipoExamen::etiquetaDe($examen->tipoExamen?->nombre_tipo_examen) ?? 'Sin tipo',
                'fecha' => $examen->fecha,
                'fecha_formateada' => $examen->fecha === null
                    ? null
                    : Carbon::parse($examen->fecha)->format('d/m/Y'),
                'hora_inicio' => $examen->hora_inicio,
                'duracion' => $examen->duracion === null ? null : (int) $examen->duracion,
            ],
            'aulas' => $aulasExamen
                ->map(fn (Ambiente $aula): array => [
                    'id' => (int) $aula->id_ambiente,
                    'nombre' => $aula->nombre_ambiente,
                ])
                ->values()
                ->all(),
            'filtros' => [
                'id_aula' => $idAula,
                'hora_desde' => $horaDesde,
                'hora_hasta' => $horaHasta,
            ],
            'resumen' => [
                'total' => $estudiantes->count(),
                'primer_ingreso' => $this->formatearHora($horas->min()),
                'ultimo_ingreso' => $this->formatearHora($horas->max()),
            ],
            'estudiantes' => $estudiantes
            ->map(fn (array $estudiante): array => [
                'sis' => $estudiante['sis'],
                'nombres' => $estudiante['nombres'],
                'apellidos' => $estudiante['apellidos'],
                'aula' => $estudiante['aula'],
                'hora_ingreso' => $estudiante['hora_ingreso'],
                'hora_salida' => $estudiante['hora_salida'] ?? null,
                'fecha_ingreso' => $examen->fecha,
            ])
                ->values()
                ->all(),
            'sin_datos' => $estudiantes->isEmpty(),
        ];
    }

    /**
     * @return Collection<int, Ambiente>
     */
    private function aulasDelExamen(Examen $examen): Collection
    {
        return $examen->ambientes()
            ->orderBy('nombre_ambiente')
            ->get();
    }

    /**
     * @param  Collection<int, Ambiente>  $aulasExamen
     * @return Collection<int, array{sis: string, nombres: string, apellidos: string, aula: string, hora_ingreso: ?string, hora_ingreso_orden: ?string, aulas_ids: list<int>}>
     */
    private function estudiantes(
        int $idExamen,
        int $idCurso,
        Collection $aulasExamen,
        ?int $idAula,
        ?string $horaDesde,
        ?string $horaHasta,
    ): Collection {
        $filas = DB::table('registro_asistencia as ra')
            ->join('estudiante as e', 'e.sis_estudiante', '=', 'ra.id_estudiante')
            ->join('estudiante_examen as ee', 'ee.id_estudiante_examen', '=', 'ra.id_estudiante_examen')
            ->leftJoin('estudiante_examen_ambiente as eea', 'eea.id_ee', '=', 'ee.id_estudiante_examen')
            ->leftJoin('ambiente as a', 'a.id_ambiente', '=', 'eea.id_ambiente')
            ->where('ra.id_examen', $idExamen)
            ->where('ee.id_curso', $idCurso)
            ->when($horaDesde, fn ($consulta, $hora) => $consulta->where('ra.hora_ingreso', '>=', $hora.':00'))
            ->when($horaHasta, fn ($consulta, $hora) => $consulta->where('ra.hora_ingreso', '<=', $hora.':00'))
            ->orderBy('ra.hora_ingreso')
            ->get([
                'ra.id_estudiante',
                'e.nombre_estudiante',
                'e.apellido_estudiante',
                'ra.hora_ingreso',
                'a.id_ambiente',
                'a.nombre_ambiente',
            ])
            ->map(fn (object $fila): array => [
                'id_estudiante' => (string) $fila->id_estudiante,
                'nombre_estudiante' => (string) $fila->nombre_estudiante,
                'apellido_estudiante' => (string) $fila->apellido_estudiante,
                'hora_ingreso' => $fila->hora_ingreso === null ? null : (string) $fila->hora_ingreso,
                'id_ambiente' => $fila->id_ambiente === null ? null : (int) $fila->id_ambiente,
                'nombre_ambiente' => $fila->nombre_ambiente === null ? null : (string) $fila->nombre_ambiente,
            ]);

        $estudiantes = $filas
            ->groupBy('id_estudiante')
            ->map(function (Collection $filasEstudiante) use ($aulasExamen): array {
                $primera = $filasEstudiante->sortBy('hora_ingreso')->first();

                $aulasAsignadas = $filasEstudiante
                    ->filter(fn (array $fila): bool => $fila['id_ambiente'] !== null)
                    ->map(fn (array $fila): array => [
                        'id' => $fila['id_ambiente'],
                        'nombre' => $fila['nombre_ambiente'],
                    ])
                    ->unique('id')
                    ->values();

                $aulas = $aulasAsignadas->isEmpty()
                    ? $aulasExamen->map(fn (Ambiente $aula): array => [
                        'id' => (int) $aula->id_ambiente,
                        'nombre' => $aula->nombre_ambiente,
                    ])->values()
                    : $aulasAsignadas;

                return [
                    'sis' => $primera['id_estudiante'],
                    'nombres' => $primera['nombre_estudiante'],
                    'apellidos' => $primera['apellido_estudiante'],
                    'aula' => $aulas->map(fn (array $aula): string => $aula['nombre'])->implode(', '),
                    'hora_ingreso' => $this->formatearHora($primera['hora_ingreso']),
                    'hora_salida' => null,
                    'hora_ingreso_orden' => $primera['hora_ingreso'],
                    'aulas_ids' => array_values($aulas->map(fn (array $aula): int => $aula['id'])->all()),
                ];
            });

        if ($idAula !== null) {
            $estudiantes = $estudiantes->filter(
                fn (array $estudiante): bool => in_array($idAula, $estudiante['aulas_ids'], true)
            );
        }

        return $estudiantes->sortBy('hora_ingreso_orden')->values();
    }

    private function formatearHora(?string $hora): ?string
    {
        if ($hora === null || $hora === '') {
            return null;
        }

        return strlen($hora) >= 8 ? substr($hora, 0, 8) : $hora;
    }
}
