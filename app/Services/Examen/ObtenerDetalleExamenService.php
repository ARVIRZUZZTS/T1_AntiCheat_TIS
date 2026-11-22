<?php

/**
 * @file    ObtenerDetalleExamenService.php
 *
 * @author  Valery D. Ortuno P <valerydariana98@gmail.com>
 *
 * @created 2026-11-19
 *
 * @description
 * Arma el detalle de solo lectura de un examen para "Ver examenes": tipo,
 * fecha, horario y duracion, ambientes, quien lo creo, los docentes del examen
 * (creador, invitados que aceptaron y docentes de sus cursos) con los cursos que
 * cada uno vinculo, y las reglas y los materiales permitidos (del catalogo y los
 * escritos a mano).
 *
 * Las reglas y los materiales salen de la configuracion del docente que
 * consulta (cada docente del examen tiene la suya), como en el monitor en
 * vivo; si todavia no tiene una, se usan las del examen.
 *
 * Cada consulta a la base remota pesa (~380 ms), asi que todo sale en dos:
 * una para los datos del examen, que de paso dice si el docente puede verlo
 * (lo creo, gestiona uno de sus cursos, tiene su configuracion o acepto la
 * invitacion), y otra (UNION ALL) para todas las listas. Asi el endpoint no
 * necesita cargar el examen ni consultar permisos por separado.
 *
 * @changelog
 * - 2026-11-19  [Valery D. Ortuno P]  feat: creacion inicial.
 *
 * @see  App\Http\Controllers\Api\ExamenDetalleController
 * @see  App\Services\Monitoreo\GenerarReporteAsistenciaService
 */

namespace App\Services\Examen;

use App\Enums\InvitacionEstado;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ObtenerDetalleExamenService
{
    /**
     * Nombre visible de un usuario: nombre (o el de usuario) y apellido.
     */
    private const NOMBRE_USUARIO = "trim(coalesce(nullif(%1\$s.nombre, ''), %1\$s.nombre_usuario, '') || ' ' || coalesce(%1\$s.apellido, ''))";

    /**
     * @param  int  $idExamen  Examen a mostrar.
     * @param  int  $idDocente  Docente que consulta (define la configuracion).
     * @return array{
     *     id: int,
     *     tipo: string|null,
     *     fecha: string|null,
     *     hora_inicio: string|null,
     *     hora_fin: string|null,
     *     duracion: int|null,
     *     creador: string|null,
     *     participantes: list<array{docente: string, rol: string, cursos: list<string>}>,
     *     ambientes: list<string>,
     *     normas: list<string>,
     *     materiales: list<string>
     * }|null  null si el examen no existe.
     *
     * @throws AuthorizationException Si el docente no participa del examen.
     */
    public function ejecutar(int $idExamen, int $idDocente): ?array
    {
        $cabecera = DB::table('examen as e')
            ->leftJoin('tipo_examen as t', 't.id_tipo_examen', '=', 'e.tipo_examen')
            ->leftJoin('usuario as u', 'u.id_usuario', '=', 'e.creador')
            ->where('e.id_examen', $idExamen)
            ->selectRaw(
                't.nombre_tipo_examen as tipo, e.fecha, e.hora_inicio, e.duracion, e.creador as id_creador, '
                .sprintf(self::NOMBRE_USUARIO, 'u').' as creador, '
                .'case when e.creador = ?'
                .' or exists (select 1 from examen_curso ec join curso c on c.id_curso = ec.id_curso where ec.id_examen = e.id_examen and c.sis_doc = ?)'
                .' or exists (select 1 from configuracion_examen_docente ced where ced.id_examen = e.id_examen and ced.id_docente = ?)'
                .' or exists (select 1 from invitacion_examen_compartido i where i.id_examen = e.id_examen and i.id_docente_invitado = ? and i.estado = ?)'
                .' then 1 else 0 end as puede_ver',
                [$idDocente, $idDocente, $idDocente, $idDocente, InvitacionEstado::Aceptada->value],
            )
            ->first();

        if ($cabecera === null) {
            return null;
        }

        if ((int) $cabecera->puede_ver !== 1) {
            throw new AuthorizationException('No puedes ver este examen.');
        }

        $inicio = $cabecera->hora_inicio === null ? null : Carbon::parse('2000-01-01 '.$cabecera->hora_inicio);
        $duracion = $cabecera->duracion === null ? null : (int) $cabecera->duracion;

        $filas = $this->listas($idExamen, $idDocente)->get();
        $de = fn (string $tipo) => $filas->where('tipo', $tipo)->sortBy('orden', SORT_NATURAL);

        return [
            'id' => $idExamen,
            'tipo' => $this->texto($cabecera?->tipo) === null ? null : Str::ucfirst($this->texto($cabecera->tipo)),
            'fecha' => $cabecera->fecha === null ? null : Carbon::parse($cabecera->fecha)->format('d/m/Y'),
            'hora_inicio' => $inicio?->format('H:i'),
            // Misma regla que Examen::hora_fin: inicio + duracion.
            'hora_fin' => $inicio !== null && $duracion !== null ? $inicio->copy()->addMinutes($duracion)->format('H:i') : null,
            'duracion' => $duracion,
            'creador' => $this->texto($cabecera?->creador),
            'participantes' => $this->participantes(
                $cabecera,
                $de('curso'),
                $filas->where('tipo', 'invitado'),
            ),
            'ambientes' => $this->textos($de('ambiente')),
            'normas' => $this->textos($de('norma')),
            'materiales' => $this->textos($de('material')),
        ];
    }

    /**
     * Todas las listas del detalle en una sola consulta. Cada rama trae
     * (tipo, texto, extra, orden, id_docente). Las reglas y materiales del
     * catalogo van antes que los escritos a mano (prefijo del orden).
     */
    private function listas(int $idExamen, int $idDocente): Builder
    {
        $configuracion = fn (Builder $consulta, string $columna) => $consulta
            ->join('configuracion_examen_docente as ced', 'ced.id_configuracion_examen', '=', $columna)
            ->where('ced.id_examen', $idExamen)
            ->where('ced.id_docente', $idDocente);

        // Sin configuracion propia del docente se usa la del examen.
        $sinConfiguracion = fn (Builder $consulta) => $consulta->whereNotExists(
            fn (Builder $existe) => $existe->from('configuracion_examen_docente')
                ->where('id_examen', $idExamen)
                ->where('id_docente', $idDocente)
                ->selectRaw('1')
        );

        $cursos = DB::table('examen_curso as ec')
            ->join('curso as c', 'c.id_curso', '=', 'ec.id_curso')
            ->leftJoin('usuario as d', 'd.id_usuario', '=', 'c.sis_doc')
            ->where('ec.id_examen', $idExamen)
            ->selectRaw("'curso' as tipo, c.nombre_curso as texto, ".sprintf(self::NOMBRE_USUARIO, 'd').' as extra, c.nombre_curso as orden, c.sis_doc as id_docente');

        // Invitados que aceptaron, aunque aun no hayan vinculado un curso.
        $invitados = DB::table('invitacion_examen_compartido as i')
            ->join('usuario as d', 'd.id_usuario', '=', 'i.id_docente_invitado')
            ->where('i.id_examen', $idExamen)
            ->where('i.estado', InvitacionEstado::Aceptada->value)
            ->selectRaw("'invitado' as tipo, ".sprintf(self::NOMBRE_USUARIO, 'd')." as texto, null as extra, '' as orden, i.id_docente_invitado as id_docente");

        $ambientes = DB::table('examen_ambiente as ea')
            ->join('ambiente as a', 'a.id_ambiente', '=', 'ea.id_ambiente')
            ->where('ea.id_examen', $idExamen)
            ->selectRaw("'ambiente' as tipo, a.nombre_ambiente as texto, null as extra, a.nombre_ambiente as orden, null as id_docente");

        $normasConfig = $configuracion(DB::table('configuracion_examen_norma as cn'), 'cn.id_configuracion_examen')
            ->join('norma as n', 'n.id_norma', '=', 'cn.id_norma')
            ->selectRaw("'norma' as tipo, n.detalle_norma as texto, null as extra, '1-' || n.id_norma as orden, null as id_docente");

        $normasPropiasConfig = $configuracion(DB::table('configuracion_norma_personalizada as cnp'), 'cnp.id_configuracion_examen')
            ->selectRaw("'norma' as tipo, cnp.descripcion_norma as texto, null as extra, '2-' || cnp.numero_norma as orden, null as id_docente");

        $materialesConfig = $configuracion(DB::table('configuracion_examen_material as cm'), 'cm.id_configuracion_examen')
            ->join('material as m', 'm.id_material', '=', 'cm.id_material')
            ->selectRaw("'material' as tipo, m.descripcion as texto, null as extra, '1-' || m.id_material as orden, null as id_docente");

        $materialesPropiosConfig = $configuracion(DB::table('configuracion_material_personalizado as cmp'), 'cmp.id_configuracion_examen')
            ->selectRaw("'material' as tipo, cmp.descripcion_material as texto, null as extra, '2-' || cmp.numero_material as orden, null as id_docente");

        $normasExamen = $sinConfiguracion(DB::table('examen_norma as en')
            ->join('norma as n', 'n.id_norma', '=', 'en.id_norma')
            ->where('en.id_examen', $idExamen)
            ->selectRaw("'norma' as tipo, n.detalle_norma as texto, null as extra, '1-' || n.id_norma as orden, null as id_docente"));

        $normasPropiasExamen = $sinConfiguracion(DB::table('norma_personalizada as np')
            ->where('np.id_examen', $idExamen)
            ->selectRaw("'norma' as tipo, np.descripcion_norma as texto, null as extra, '2-' || np.numero_norma as orden, null as id_docente"));

        $materialesExamen = $sinConfiguracion(DB::table('examen_material_permitido as emp')
            ->join('material as m', 'm.id_material', '=', 'emp.id_material_permitido')
            ->where('emp.id_examen', $idExamen)
            ->selectRaw("'material' as tipo, m.descripcion as texto, null as extra, '1-' || m.id_material as orden, null as id_docente"));

        $materialesPropiosExamen = $sinConfiguracion(DB::table('material_personalizado as mp')
            ->where('mp.id_examen', $idExamen)
            ->selectRaw("'material' as tipo, mp.descripcion_material as texto, null as extra, '2-' || mp.numero_material as orden, null as id_docente"));

        return $cursos
            ->unionAll($invitados)
            ->unionAll($ambientes)
            ->unionAll($normasConfig)
            ->unionAll($normasPropiasConfig)
            ->unionAll($materialesConfig)
            ->unionAll($materialesPropiosConfig)
            ->unionAll($normasExamen)
            ->unionAll($normasPropiasExamen)
            ->unionAll($materialesExamen)
            ->unionAll($materialesPropiosExamen);
    }

    /**
     * Docentes del examen con su rol y los cursos que vincularon: primero el
     * creador y despues el resto por nombre.
     *
     * @return list<array{docente: string, rol: string, cursos: list<string>}>
     */
    private function participantes(?object $cabecera, iterable $cursos, iterable $invitados): array
    {
        $idCreador = (int) ($cabecera?->id_creador ?? 0);
        $docentes = collect();

        $sumar = function (int $id, ?string $nombre, string $rol) use ($docentes): void {
            if (! $docentes->has($id)) {
                $docentes->put($id, ['docente' => $this->texto($nombre) ?? 'Sin nombre', 'rol' => $rol, 'cursos' => []]);
            }
        };

        if ($idCreador > 0) {
            $sumar($idCreador, $cabecera->creador, 'Creador');
        }
        foreach ($invitados as $invitado) {
            $sumar((int) $invitado->id_docente, $invitado->texto, 'Invitado');
        }
        foreach ($cursos as $curso) {
            $id = (int) $curso->id_docente;
            $sumar($id, $curso->extra, 'Docente del curso');
            $fila = $docentes->get($id);
            $fila['cursos'][] = trim((string) $curso->texto);
            $docentes->put($id, $fila);
        }

        return $docentes
            ->sortBy(fn (array $fila, int $id): string => ($id === $idCreador ? '0' : '1').mb_strtolower($fila['docente']))
            ->values()
            ->all();
    }

    /**
     * Textos de una lista, sin vacios ni repetidos.
     *
     * @return list<string>
     */
    private function textos(iterable $filas): array
    {
        return collect($filas)
            ->map(fn (object $fila): string => trim((string) $fila->texto))
            ->filter(fn (string $texto): bool => $texto !== '')
            ->unique()
            ->values()
            ->all();
    }

    private function texto(?string $valor): ?string
    {
        $valor = trim((string) $valor);

        return $valor === '' ? null : $valor;
    }
}
