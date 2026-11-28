<?php

/**
 * @file    ListarAsignacionesAuxiliaresService.php
 *
 * @author  Valery D. Ortuno P <valerydariana98@gmail.com>
 *
 * @created 2026-11-22
 *
 * @updated 2026-11-24
 *
 * @description
 * Datos de la pagina "Asignar auxiliar" (HU 24):
 *
 *  - `auxiliares`: todos los auxiliares del sistema, cada uno con sus
 *    asignaciones dentro de los cursos del docente (cursos y examenes
 *    sueltos, con las paginas que ve en cada examen) y su proximo examen
 *    (el mas cercano entre sus examenes asignados y los de sus cursos).
 *    Orden: primero el del examen mas proximo, luego los que tienen
 *    asignaciones sin examenes por venir y al final los que no tienen nada.
 *  - `cursos`: los cursos en curso del docente, para asignar a un curso.
 *  - `examenes`: los examenes futuros de esos cursos en curso (no de cursos
 *    archivados), para asignar a uno.
 *
 * Solo se muestran las asignaciones vigentes: cursos activos (no archivados)
 * y examenes que aun no empezaron, de cursos activos. Es solo visual: en la
 * base siguen las asignaciones de cursos archivados y examenes pasados (quien
 * controlo cada examen).
 *
 * Todo sale en dos consultas (la base remota pesa ~380 ms por viaje): los
 * auxiliares y, en un solo UNION, sus asignaciones y las opciones del modal.
 * Las etiquetas de los examenes se arman en PHP para no depender de funciones
 * de agregacion distintas en Postgres y SQLite.
 *
 * @changelog
 * - 2026-11-22  [Valery D. Ortuno P]  feat: creacion inicial (HU 24).
 * - 2026-11-24  [Valery D. Ortuno P]  feat: proximo examen de cada auxiliar y
 *   orden por ese examen (sin asignaciones al final), en las mismas consultas.
 * - 2026-11-24  [Valery D. Ortuno P]  fix/perf: no se muestran asignaciones a
 *   cursos archivados ni a examenes que ya empezaron (siguen en la base); las
 *   asignaciones y las opciones salen en una sola consulta (antes dos).
 *
 * @see  App\Livewire\Auxiliares\AsignarAuxiliar
 */

namespace App\Services\Auxiliar;

use App\Enums\CursoEstado;
use App\Enums\EstadoCuenta;
use App\Models\Rol;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ListarAsignacionesAuxiliaresService
{
    private const NOMBRE_USUARIO = "trim(coalesce(nullif(u.nombre, ''), u.nombre_usuario, '') || ' ' || coalesce(u.apellido, ''))";

    /** Usuario de acceso que se muestra: el nombre de usuario (generado). */
    private const USUARIO = 'u.nombre_usuario';

    /**
     * @return array{
     *     auxiliares: list<array{id: int, nombre: string, sis: string, proximo: ?string, cursos: list<array{id: int, nombre: string}>, examenes: list<array{id: int, etiqueta: string, ingreso: bool, monitor: bool}>}>,
     *     cursos: list<array{id: int, nombre: string}>,
     *     examenes: list<array{id: int, etiqueta: string}>
     * }
     */
    public function ejecutar(int $idDocente, ?int $idCoordinador = null): array
    {
        $auxiliares = DB::table('usuario as u')
            ->join('rol_usuario as ru', 'ru.id_usuario', '=', 'u.id_usuario')
            ->join('rol as r', 'r.id_rol', '=', 'ru.id_rol')
            ->where('r.nombre_rol', Rol::NOMBRE_AUXILIAR)
            // Los auxiliares eliminados (borrado lógico) no se pueden asignar.
            ->where('u.estado_cuenta', '!=', EstadoCuenta::Eliminada->value)
            // Un coordinador no puede asignarse a si mismo: no aparece en la lista.
            ->when($idCoordinador !== null, fn ($consulta) => $consulta->where('u.id_usuario', '!=', $idCoordinador))
            ->selectRaw('u.id_usuario as id, u.cod_sis as sis, '.self::USUARIO.' as usuario, '.self::NOMBRE_USUARIO.' as nombre')
            ->orderBy('nombre')
            ->get();

        $ahora = now();
        // Asignaciones y opciones del modal en un solo viaje.
        $consultas = [...$this->asignaciones($idDocente, $ahora), ...$this->opciones($idDocente, $ahora, $idCoordinador)];
        $union = array_shift($consultas);
        foreach ($consultas as $consulta) {
            $union->unionAll($consulta);
        }
        $filas = $union->get();
        $asignaciones = $filas->where('origen', 'asignacion');
        $opciones = $filas->where('origen', 'opcion');

        $cursosPorAuxiliar = $asignaciones->where('tipo', 'curso')->groupBy('id_auxiliar');
        $examenesPorAuxiliar = $asignaciones->where('tipo', 'examen')->groupBy('id_auxiliar');

        $ahoraTexto = $ahora->format('Y-m-d H:i:s');

        return [
            'auxiliares' => $auxiliares->map(fn (object $auxiliar): array => [
                'id' => (int) $auxiliar->id,
                'nombre' => trim((string) $auxiliar->nombre),
                'sis' => trim((string) $auxiliar->sis),
                'usuario' => trim((string) $auxiliar->usuario),
                // El examen por venir mas cercano: el proximo de cada curso
                // asignado o un examen asignado que aun no empezo.
                'proximo' => $asignaciones->where('id_auxiliar', $auxiliar->id)
                    ->map(fn (object $fila): ?string => $fila->tipo === 'curso'
                        ? ($fila->proximo === null ? null : substr((string) $fila->proximo, 0, 19))
                        : ($fila->fecha === null ? null : substr((string) $fila->fecha, 0, 10).' '.($fila->hora_inicio ?? '00:00:00')))
                    ->filter(fn (?string $cuando): bool => $cuando !== null && $cuando > $ahoraTexto)
                    ->min(),
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
            ])
                // Examen mas proximo primero; luego con asignaciones y sin
                // examenes por venir; al final los que no tienen nada.
                ->sortBy(fn (array $auxiliar): string => match (true) {
                    $auxiliar['proximo'] !== null => '0 '.$auxiliar['proximo'],
                    $auxiliar['cursos'] !== [] || $auxiliar['examenes'] !== [] => '1',
                    default => '2',
                }.' '.mb_strtolower($auxiliar['nombre']))
                ->values()
                ->all(),
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
     * curso activo asignado y una por (examen por venir asignado, curso activo
     * del docente). Lo archivado o pasado no se muestra (sigue en la base).
     *
     * @return list<\Illuminate\Database\Query\Builder>
     */
    private function asignaciones(int $idDocente, Carbon $ahora): array
    {
        // Proximo examen de cada curso asignado (texto 'Y-m-d H:i:s').
        $proximoDelCurso = "(select min(e2.fecha || ' ' || coalesce(e2.hora_inicio, '00:00:00')) from examen e2"
            .' join examen_curso ec2 on ec2.id_examen = e2.id_examen where ec2.id_curso = c.id_curso'
            .' and (e2.fecha > ? or (e2.fecha = ? and e2.hora_inicio > ?)))';

        $cursos = DB::table('auxiliar_curso as ac')
            ->join('curso as c', 'c.id_curso', '=', 'ac.id_curso')
            ->where('c.sis_doc', $idDocente)
            ->where('c.estado', CursoEstado::EnCurso->value)
            ->where('ac.estado', AccesoAuxiliarService::ESTADO_ACTIVO)
            ->selectRaw(
                "'asignacion' as origen, 'curso' as tipo, ac.id_auxiliar, c.id_curso as id, c.nombre_curso as texto, null as tipo_examen, null as fecha, null as hora_inicio, null as ver_ingreso, null as ver_monitor, {$proximoDelCurso} as proximo",
                [$ahora->toDateString(), $ahora->toDateString(), $ahora->format('H:i:s')],
            );

        $examenes = DB::table('auxiliar_examen as ae')
            ->join('examen as e', 'e.id_examen', '=', 'ae.id_examen')
            ->join('examen_curso as ec', 'ec.id_examen', '=', 'e.id_examen')
            ->join('curso as c', 'c.id_curso', '=', 'ec.id_curso')
            ->leftJoin('tipo_examen as t', 't.id_tipo_examen', '=', 'e.tipo_examen')
            ->where('c.sis_doc', $idDocente)
            ->where('c.estado', CursoEstado::EnCurso->value)
            ->where(fn ($consulta) => $this->futuro($consulta, $ahora))
            ->selectRaw("'asignacion' as origen, 'examen' as tipo, ae.id_auxiliar, e.id_examen as id, c.nombre_curso as texto, t.nombre_tipo_examen as tipo_examen, e.fecha, e.hora_inicio, ae.ver_ingreso, ae.ver_monitor, null as proximo");

        return [$cursos, $examenes];
    }

    /**
     * Opciones del modal: cursos en curso del docente y examenes futuros de
     * sus cursos (una fila por examen y curso).
     *
     * Si actua un coordinador (`$idCoordinador`), solo se ofrecen los cursos del
     * docente responsable en los que el coordinador mismo esta asignado, y los
     * examenes de esos cursos.
     *
     * @return list<\Illuminate\Database\Query\Builder>
     */
    private function opciones(int $idDocente, Carbon $ahora, ?int $idCoordinador = null): array
    {
        $cursos = DB::table('curso as c')
            ->where('c.sis_doc', $idDocente)
            ->where('c.estado', CursoEstado::EnCurso->value)
            ->when($idCoordinador !== null, fn ($consulta) => $this->soloCursosDelCoordinador($consulta, $idCoordinador))
            ->selectRaw("'opcion' as origen, 'curso' as tipo, null as id_auxiliar, c.id_curso as id, c.nombre_curso as texto, null as tipo_examen, null as fecha, null as hora_inicio, null as ver_ingreso, null as ver_monitor, null as proximo");

        $examenes = DB::table('examen as e')
            ->join('examen_curso as ec', 'ec.id_examen', '=', 'e.id_examen')
            ->join('curso as c', 'c.id_curso', '=', 'ec.id_curso')
            ->leftJoin('tipo_examen as t', 't.id_tipo_examen', '=', 'e.tipo_examen')
            ->where('c.sis_doc', $idDocente)
            ->where('c.estado', CursoEstado::EnCurso->value)
            ->when($idCoordinador !== null, fn ($consulta) => $this->soloCursosDelCoordinador($consulta, $idCoordinador))
            ->where(fn ($consulta) => $this->futuro($consulta, $ahora))
            ->selectRaw("'opcion' as origen, 'examen' as tipo, null as id_auxiliar, e.id_examen as id, c.nombre_curso as texto, t.nombre_tipo_examen as tipo_examen, e.fecha, e.hora_inicio, null as ver_ingreso, null as ver_monitor, null as proximo");

        return [$cursos, $examenes];
    }

    /** Limita a los cursos (`c`) en los que el coordinador tiene asignacion activa. */
    private function soloCursosDelCoordinador($consulta, int $idCoordinador)
    {
        return $consulta->whereExists(fn ($sub) => $sub->from('auxiliar_curso as acc')
            ->whereColumn('acc.id_curso', 'c.id_curso')
            ->where('acc.id_auxiliar', $idCoordinador)
            ->where('acc.estado', AccesoAuxiliarService::ESTADO_ACTIVO)
            ->selectRaw('1'));
    }

    /** Examen que aun no empezo: fecha posterior a hoy, o de hoy con hora por venir. */
    private function futuro($consulta, Carbon $ahora)
    {
        return $consulta
            ->where('e.fecha', '>', $ahora->toDateString())
            ->orWhere(fn ($hoy) => $hoy
                ->where('e.fecha', $ahora->toDateString())
                ->where('e.hora_inicio', '>', $ahora->format('H:i:s')));
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
