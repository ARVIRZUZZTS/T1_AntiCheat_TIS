<?php

/**
 * @file    ListarAsignacionesAuxiliaresService.php
 *
 * @author  Valery D. Ortuno P <valerydariana98@gmail.com>
 *
 * @created 2026-11-22
 *
 * @description
 * Datos de la pagina "Asignar auxiliar" (HU 24):
 *
 *  - `auxiliares`: todos los auxiliares del sistema, cada uno con sus
 *    asignaciones dentro de los cursos del docente (cursos y examenes
 *    sueltos, con las paginas que ve en cada examen).
 *  - `cursos`: los cursos en curso del docente, para asignar a un curso.
 *  - `examenes`: los examenes futuros de esos cursos en curso (no de cursos
 *    archivados), para asignar a uno.
 *
 * Todo sale en tres consultas (la base remota pesa ~380 ms por viaje); las
 * etiquetas de los examenes se arman en PHP para no depender de funciones de
 * agregacion distintas en Postgres y SQLite.
 *
 * @changelog
 * - 2026-11-22  [Valery D. Ortuno P]  feat: creacion inicial (HU 24).
 *
 * @see  App\Livewire\Auxiliares\AsignarAuxiliar
 */

namespace App\Services\Auxiliar;

use App\Enums\CursoEstado;
use App\Models\Rol;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ListarAsignacionesAuxiliaresService
{
    private const NOMBRE_USUARIO = "trim(coalesce(nullif(u.nombre, ''), u.nombre_usuario, '') || ' ' || coalesce(u.apellido, ''))";

    /**
     * @return array{
     *     auxiliares: list<array{id: int, nombre: string, sis: string, cursos: list<array{id: int, nombre: string}>, examenes: list<array{id: int, etiqueta: string, ingreso: bool, monitor: bool}>}>,
     *     cursos: list<array{id: int, nombre: string}>,
     *     examenes: list<array{id: int, etiqueta: string}>
     * }
     */
    public function ejecutar(int $idDocente): array
    {
        $auxiliares = DB::table('usuario as u')
            ->join('rol_usuario as ru', 'ru.id_usuario', '=', 'u.id_usuario')
            ->join('rol as r', 'r.id_rol', '=', 'ru.id_rol')
            ->where('r.nombre_rol', Rol::NOMBRE_AUXILIAR)
            ->selectRaw('u.id_usuario as id, u.cod_sis as sis, '.self::NOMBRE_USUARIO.' as nombre')
            ->orderBy('nombre')
            ->get();

        $asignaciones = $this->asignaciones($idDocente)->get();
        $opciones = $this->opciones($idDocente)->get();

        $cursosPorAuxiliar = $asignaciones->where('tipo', 'curso')->groupBy('id_auxiliar');
        $examenesPorAuxiliar = $asignaciones->where('tipo', 'examen')->groupBy('id_auxiliar');

        return [
            'auxiliares' => $auxiliares->map(fn (object $auxiliar): array => [
                'id' => (int) $auxiliar->id,
                'nombre' => trim((string) $auxiliar->nombre),
                'sis' => trim((string) $auxiliar->sis),
                'cursos' => $cursosPorAuxiliar->get($auxiliar->id, collect())
                    ->sortBy('texto')
                    ->map(fn (object $fila): array => ['id' => (int) $fila->id, 'nombre' => (string) $fila->texto])
                    ->values()
                    ->all(),
                'examenes' => $this->examenes($examenesPorAuxiliar->get($auxiliar->id, collect()))
                    ->map(fn (array $examen): array => $examen + [
                        'ingreso' => (bool) $examen['fila']->ver_ingreso,
                        'monitor' => (bool) $examen['fila']->ver_monitor,
                    ])
                    ->map(fn (array $examen): array => collect($examen)->except('fila')->all())
                    ->values()
                    ->all(),
            ])->values()->all(),
            'cursos' => $opciones->where('tipo', 'curso')
                ->sortBy('texto')
                ->map(fn (object $fila): array => ['id' => (int) $fila->id, 'nombre' => (string) $fila->texto])
                ->values()
                ->all(),
            'examenes' => $this->examenes($opciones->where('tipo', 'examen'))
                ->map(fn (array $examen): array => collect($examen)->except('fila')->all())
                ->values()
                ->all(),
        ];
    }

    /**
     * Asignaciones vigentes dentro de los cursos del docente: una fila por
     * curso asignado y una por (examen asignado, curso del docente).
     */
    private function asignaciones(int $idDocente)
    {
        $cursos = DB::table('auxiliar_curso as ac')
            ->join('curso as c', 'c.id_curso', '=', 'ac.id_curso')
            ->where('c.sis_doc', $idDocente)
            ->where('ac.estado', AccesoAuxiliarService::ESTADO_ACTIVO)
            ->selectRaw("'curso' as tipo, ac.id_auxiliar, c.id_curso as id, c.nombre_curso as texto, null as tipo_examen, null as fecha, null as hora_inicio, null as ver_ingreso, null as ver_monitor");

        $examenes = DB::table('auxiliar_examen as ae')
            ->join('examen as e', 'e.id_examen', '=', 'ae.id_examen')
            ->join('examen_curso as ec', 'ec.id_examen', '=', 'e.id_examen')
            ->join('curso as c', 'c.id_curso', '=', 'ec.id_curso')
            ->leftJoin('tipo_examen as t', 't.id_tipo_examen', '=', 'e.tipo_examen')
            ->where('c.sis_doc', $idDocente)
            ->selectRaw("'examen' as tipo, ae.id_auxiliar, e.id_examen as id, c.nombre_curso as texto, t.nombre_tipo_examen as tipo_examen, e.fecha, e.hora_inicio, ae.ver_ingreso, ae.ver_monitor");

        return $cursos->unionAll($examenes);
    }

    /**
     * Opciones del modal: cursos en curso del docente y examenes futuros de
     * sus cursos (una fila por examen y curso).
     */
    private function opciones(int $idDocente)
    {
        $ahora = now();

        $cursos = DB::table('curso as c')
            ->where('c.sis_doc', $idDocente)
            ->where('c.estado', CursoEstado::EnCurso->value)
            ->selectRaw("'curso' as tipo, null as id_auxiliar, c.id_curso as id, c.nombre_curso as texto, null as tipo_examen, null as fecha, null as hora_inicio, null as ver_ingreso, null as ver_monitor");

        $examenes = DB::table('examen as e')
            ->join('examen_curso as ec', 'ec.id_examen', '=', 'e.id_examen')
            ->join('curso as c', 'c.id_curso', '=', 'ec.id_curso')
            ->leftJoin('tipo_examen as t', 't.id_tipo_examen', '=', 'e.tipo_examen')
            ->where('c.sis_doc', $idDocente)
            ->where('c.estado', CursoEstado::EnCurso->value)
            ->where(fn ($consulta) => $consulta
                ->where('e.fecha', '>', $ahora->toDateString())
                ->orWhere(fn ($hoy) => $hoy
                    ->where('e.fecha', $ahora->toDateString())
                    ->where('e.hora_inicio', '>', $ahora->format('H:i:s'))))
            ->selectRaw("'examen' as tipo, null as id_auxiliar, e.id_examen as id, c.nombre_curso as texto, t.nombre_tipo_examen as tipo_examen, e.fecha, e.hora_inicio, null as ver_ingreso, null as ver_monitor");

        return $cursos->unionAll($examenes);
    }

    /**
     * Agrupa las filas (examen, curso) en un examen con su etiqueta:
     * "Examen parcial - Calculo I, Calculo II · 02/12/2026 08:15". Ordena por
     * fecha y hora.
     *
     * @param  Collection<int, object>  $filas
     * @return Collection<int, array{id: int, etiqueta: string, fila: object}>
     */
    private function examenes(Collection $filas): Collection
    {
        return $filas->groupBy('id')
            ->map(function (Collection $grupo): array {
                $fila = $grupo->first();
                $tipo = trim((string) $fila->tipo_examen);
                $cursos = $grupo->pluck('texto')->filter()->unique()->sort()->implode(', ');
                $cuando = $fila->fecha === null
                    ? 'Sin fecha'
                    : Carbon::parse($fila->fecha)->format('d/m/Y').($fila->hora_inicio ? ' '.substr((string) $fila->hora_inicio, 0, 5) : '');

                return [
                    'id' => (int) $fila->id,
                    'etiqueta' => ($tipo === '' ? 'Examen' : Str::ucfirst($tipo)).' - '.$cursos.' · '.$cuando,
                    'orden' => ($fila->fecha ?? '9999-12-31').' '.($fila->hora_inicio ?? ''),
                    'fila' => $fila,
                ];
            })
            ->sortBy('orden')
            ->map(fn (array $examen): array => collect($examen)->except('orden')->all())
            ->values();
    }
}
