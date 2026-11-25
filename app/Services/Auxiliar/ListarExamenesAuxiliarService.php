<?php

/**
 * @file    ListarExamenesAuxiliarService.php
 *
 * @author  Valery D. Ortuno P <valerydariana98@gmail.com>
 *
 * @created 2026-11-22
 *
 * @description
 * Examenes del auxiliar para su seccion "Examenes > Ver examenes" (HU 24):
 * todos los examenes de sus cursos activos y los examenes a los que lo
 * asignaron sueltos. Cada uno trae sus datos (tipo, cursos, fecha, horario,
 * ambientes y estado) y el acceso del auxiliar: por curso ve Ingreso y
 * Monitor; por examen, las paginas que el docente marco.
 *
 * Sale en dos consultas (la base remota pesa ~380 ms por viaje): una con los
 * examenes y sus cursos, y otra con los ambientes.
 *
 * @changelog
 * - 2026-11-22  [Valery D. Ortuno P]  feat: creacion inicial (HU 24).
 * - 2026-11-24  [Alisson D. Alvarado <alvaradoalissondalet@gmail.com>]  fix:
 *   excluir asignaciones a examenes sin permisos habilitados.
 * - 2026-11-24  [Alisson D. Alvarado <alvaradoalissondalet@gmail.com>]  feat:
 *   entregar los nombres por separado para filtrar examenes compartidos.
 *
 * @see  App\Livewire\Auxiliares\ExamenesAuxiliar
 * @see  App\Services\Auxiliar\AccesoAuxiliarService
 */

namespace App\Services\Auxiliar;

use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ListarExamenesAuxiliarService
{
    public const PROGRAMADO = 'Programado';

    public const EN_CURSO = 'En curso';

    public const FINALIZADO = 'Finalizado';

    /**
     * @return list<array{id: int, tipo: string, cursos: string, fecha: string, horario: string, ambientes: string, estado: string, acceso: string, origen: string}>
     */
    public function ejecutar(int $idAuxiliar): array
    {
        // Una fila por (examen, curso del examen), con la marca de si el
        // auxiliar tiene ese curso activo y las paginas de su asignacion suelta.
        $filas = DB::table('examen as e')
            ->join('examen_curso as ec', 'ec.id_examen', '=', 'e.id_examen')
            ->join('curso as c', 'c.id_curso', '=', 'ec.id_curso')
            ->leftJoin('tipo_examen as t', 't.id_tipo_examen', '=', 'e.tipo_examen')
            ->leftJoin('auxiliar_curso as ac', fn (JoinClause $union) => $union
                ->on('ac.id_curso', '=', 'c.id_curso')
                ->where('ac.id_auxiliar', '=', $idAuxiliar)
                ->where('ac.estado', '=', AccesoAuxiliarService::ESTADO_ACTIVO))
            ->leftJoin('auxiliar_examen as ae', fn (JoinClause $union) => $union
                ->on('ae.id_examen', '=', 'e.id_examen')
                ->where('ae.id_auxiliar', '=', $idAuxiliar))
            ->whereIn('e.id_examen', $this->idsAsignados($idAuxiliar))
            ->select([
                'e.id_examen', 'e.fecha', 'e.hora_inicio', 'e.duracion', 't.nombre_tipo_examen',
                'c.nombre_curso', 'ac.id_auxiliar as curso_asignado', 'ae.ver_ingreso', 'ae.ver_monitor',
            ])
            ->get();

        if ($filas->isEmpty()) {
            return [];
        }

        $ambientes = DB::table('examen_ambiente as ea')
            ->join('ambiente as a', 'a.id_ambiente', '=', 'ea.id_ambiente')
            ->whereIn('ea.id_examen', $filas->pluck('id_examen')->unique()->values())
            ->orderBy('a.nombre_ambiente')
            ->get(['ea.id_examen', 'a.nombre_ambiente'])
            ->groupBy('id_examen');

        $ahora = now();

        return $filas->groupBy('id_examen')
            ->map(fn (Collection $grupo): array => $this->examen($grupo, $ambientes->get($grupo->first()->id_examen, collect()), $ahora))
            ->sortBy([['grupo', 'asc'], ['clave', 'asc']])
            ->map(fn (array $examen): array => collect($examen)->except(['grupo', 'clave'])->all())
            ->values()
            ->all();
    }

    /**
     * Ids de los examenes asignados: los de sus cursos activos y los sueltos.
     */
    private function idsAsignados(int $idAuxiliar): Builder
    {
        $porCurso = DB::table('examen_curso as ec2')
            ->join('auxiliar_curso as ac2', 'ac2.id_curso', '=', 'ec2.id_curso')
            ->where('ac2.id_auxiliar', $idAuxiliar)
            ->where('ac2.estado', AccesoAuxiliarService::ESTADO_ACTIVO)
            ->select('ec2.id_examen');

        return DB::table('auxiliar_examen')
            ->where('id_auxiliar', $idAuxiliar)
            ->where(fn (Builder $consulta) => $consulta
                ->where('ver_ingreso', true)
                ->orWhere('ver_monitor', true))
            ->select('id_examen')
            ->union($porCurso);
    }

    /**
     * @param  Collection<int, object>  $grupo  Filas del examen (una por curso).
     * @param  Collection<int, object>  $ambientes
     * @return array<string, mixed>
     */
    private function examen(Collection $grupo, Collection $ambientes, Carbon $ahora): array
    {
        $fila = $grupo->first();
        $cursosAsignados = $grupo->whereNotNull('curso_asignado')->pluck('nombre_curso')->unique()->sort()->values();

        $inicio = $fila->fecha && $fila->hora_inicio ? Carbon::parse($fila->fecha.' '.$fila->hora_inicio) : null;
        $fin = $inicio && $fila->duracion ? $inicio->copy()->addMinutes((int) $fila->duracion) : null;

        $estado = match (true) {
            $inicio === null || $ahora->lt($inicio) => self::PROGRAMADO,
            $fin === null || $ahora->lte($fin) => self::EN_CURSO,
            default => self::FINALIZADO,
        };

        // Por curso ve las dos paginas; si no, lo que marco el docente.
        if ($cursosAsignados->isNotEmpty()) {
            $acceso = 'Ingreso y Monitor';
            $origen = 'Por el curso '.$cursosAsignados->implode(', ');
        } else {
            $ingreso = (bool) $fila->ver_ingreso;
            $monitor = (bool) $fila->ver_monitor;
            $acceso = $ingreso && $monitor ? 'Ingreso y Monitor' : ($ingreso ? 'Solo Ingreso' : 'Solo Monitor');
            $origen = 'Asignado a este examen';
        }

        $tipo = trim((string) $fila->nombre_tipo_examen);

        return [
            'id' => (int) $fila->id_examen,
            'tipo' => $tipo === '' ? 'Examen' : Str::ucfirst($tipo),
            'cursos' => $grupo->pluck('nombre_curso')->unique()->sort()->implode(', '),
            'nombres_cursos' => $grupo->pluck('nombre_curso')->unique()->sort()->values()->all(),
            'fecha' => $inicio?->format('d/m/Y') ?? ($fila->fecha ? Carbon::parse($fila->fecha)->format('d/m/Y') : 'Sin fecha'),
            'horario' => $inicio ? $inicio->format('H:i').($fin ? ' - '.$fin->format('H:i') : '') : '—',
            'ambientes' => $ambientes->pluck('nombre_ambiente')->implode(', ') ?: '—',
            'estado' => $estado,
            'acceso' => $acceso,
            'origen' => $origen,
            // Proximos primero (del mas cercano al mas lejano); los finalizados
            // al final, del mas reciente al mas antiguo. Orden numerico: hay
            // fechas viejas (1900) cuyo timestamp es negativo.
            'grupo' => $estado === self::FINALIZADO ? 1 : 0,
            'clave' => $estado === self::FINALIZADO
                ? -($inicio?->timestamp ?? PHP_INT_MIN + 1)
                : ($inicio?->timestamp ?? PHP_INT_MAX),
        ];
    }
}
