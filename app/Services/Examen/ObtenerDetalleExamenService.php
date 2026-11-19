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
 * fecha, horario y duracion, cursos (con su docente), ambientes, quien lo creo,
 * las reglas y los materiales permitidos (del catalogo y los escritos a mano).
 *
 * Las reglas y los materiales salen de la configuracion del docente que
 * consulta (cada docente del examen tiene la suya), como en el monitor en
 * vivo; si todavia no tiene una, se usan las del examen.
 *
 * Cada consulta a la base remota pesa (~380 ms), asi que todo sale en dos:
 * una para los datos del examen y otra (UNION ALL) para todas las listas.
 *
 * @changelog
 * - 2026-11-19  [Valery D. Ortuno P]  feat: creacion inicial.
 *
 * @see  App\Http\Controllers\Api\ExamenDetalleController
 * @see  App\Services\Monitoreo\GenerarReporteAsistenciaService
 */

namespace App\Services\Examen;

use App\Models\Examen;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ObtenerDetalleExamenService
{
    /**
     * Nombre visible de un usuario: nombre (o el de usuario) y apellido.
     */
    private const NOMBRE_USUARIO = "trim(coalesce(nullif(%1\$s.nombre, ''), %1\$s.nombre_usuario, '') || ' ' || coalesce(%1\$s.apellido, ''))";

    /**
     * @param  Examen  $examen  Examen a mostrar.
     * @param  int  $idDocente  Docente que consulta (define la configuracion).
     * @return array{
     *     id: int,
     *     tipo: string|null,
     *     fecha: string|null,
     *     hora_inicio: string|null,
     *     hora_fin: string|null,
     *     duracion: int|null,
     *     creador: string|null,
     *     cursos: list<array{nombre: string, docente: string|null}>,
     *     ambientes: list<string>,
     *     normas: list<string>,
     *     materiales: list<string>
     * }
     */
    public function ejecutar(Examen $examen, int $idDocente): array
    {
        $idExamen = (int) $examen->id_examen;

        $cabecera = DB::table('examen as e')
            ->leftJoin('tipo_examen as t', 't.id_tipo_examen', '=', 'e.tipo_examen')
            ->leftJoin('usuario as u', 'u.id_usuario', '=', 'e.creador')
            ->where('e.id_examen', $idExamen)
            ->selectRaw('t.nombre_tipo_examen as tipo, '.sprintf(self::NOMBRE_USUARIO, 'u').' as creador')
            ->first();

        $filas = $this->listas($idExamen, $idDocente)->get();
        $de = fn (string $tipo) => $filas->where('tipo', $tipo)->sortBy('orden', SORT_NATURAL);

        return [
            'id' => $idExamen,
            'tipo' => $cabecera?->tipo,
            'fecha' => $examen->fecha === null ? null : Carbon::parse($examen->fecha)->format('d/m/Y'),
            'hora_inicio' => $examen->hora_inicio === null ? null : substr($examen->hora_inicio, 0, 5),
            'hora_fin' => $examen->hora_fin === null ? null : substr($examen->hora_fin, 0, 5),
            'duracion' => $examen->duracion === null ? null : (int) $examen->duracion,
            'creador' => $this->texto($cabecera?->creador),
            'cursos' => $de('curso')
                ->map(fn (object $fila): array => ['nombre' => $fila->texto, 'docente' => $this->texto($fila->extra)])
                ->values()
                ->all(),
            'ambientes' => $this->textos($de('ambiente')),
            'normas' => $this->textos($de('norma')),
            'materiales' => $this->textos($de('material')),
        ];
    }

    /**
     * Todas las listas del detalle en una sola consulta. Cada rama trae
     * (tipo, texto, extra, orden). Las reglas y materiales del catalogo van
     * antes que los escritos a mano (prefijo del orden).
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
            ->selectRaw("'curso' as tipo, c.nombre_curso as texto, ".sprintf(self::NOMBRE_USUARIO, 'd').' as extra, c.nombre_curso as orden');

        $ambientes = DB::table('examen_ambiente as ea')
            ->join('ambiente as a', 'a.id_ambiente', '=', 'ea.id_ambiente')
            ->where('ea.id_examen', $idExamen)
            ->selectRaw("'ambiente' as tipo, a.nombre_ambiente as texto, null as extra, a.nombre_ambiente as orden");

        $normasConfig = $configuracion(DB::table('configuracion_examen_norma as cn'), 'cn.id_configuracion_examen')
            ->join('norma as n', 'n.id_norma', '=', 'cn.id_norma')
            ->selectRaw("'norma' as tipo, n.detalle_norma as texto, null as extra, '1-' || n.id_norma as orden");

        $normasPropiasConfig = $configuracion(DB::table('configuracion_norma_personalizada as cnp'), 'cnp.id_configuracion_examen')
            ->selectRaw("'norma' as tipo, cnp.descripcion_norma as texto, null as extra, '2-' || cnp.numero_norma as orden");

        $materialesConfig = $configuracion(DB::table('configuracion_examen_material as cm'), 'cm.id_configuracion_examen')
            ->join('material as m', 'm.id_material', '=', 'cm.id_material')
            ->selectRaw("'material' as tipo, m.descripcion as texto, null as extra, '1-' || m.id_material as orden");

        $materialesPropiosConfig = $configuracion(DB::table('configuracion_material_personalizado as cmp'), 'cmp.id_configuracion_examen')
            ->selectRaw("'material' as tipo, cmp.descripcion_material as texto, null as extra, '2-' || cmp.numero_material as orden");

        $normasExamen = $sinConfiguracion(DB::table('examen_norma as en')
            ->join('norma as n', 'n.id_norma', '=', 'en.id_norma')
            ->where('en.id_examen', $idExamen)
            ->selectRaw("'norma' as tipo, n.detalle_norma as texto, null as extra, '1-' || n.id_norma as orden"));

        $normasPropiasExamen = $sinConfiguracion(DB::table('norma_personalizada as np')
            ->where('np.id_examen', $idExamen)
            ->selectRaw("'norma' as tipo, np.descripcion_norma as texto, null as extra, '2-' || np.numero_norma as orden"));

        $materialesExamen = $sinConfiguracion(DB::table('examen_material_permitido as emp')
            ->join('material as m', 'm.id_material', '=', 'emp.id_material_permitido')
            ->where('emp.id_examen', $idExamen)
            ->selectRaw("'material' as tipo, m.descripcion as texto, null as extra, '1-' || m.id_material as orden"));

        $materialesPropiosExamen = $sinConfiguracion(DB::table('material_personalizado as mp')
            ->where('mp.id_examen', $idExamen)
            ->selectRaw("'material' as tipo, mp.descripcion_material as texto, null as extra, '2-' || mp.numero_material as orden"));

        return $cursos
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
