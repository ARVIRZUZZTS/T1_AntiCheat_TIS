<?php

/**
 * @file    AuxiliarCursoRepository.php
 *
 * @author  OchoaCesar <cesareduardonick@gmail.com>
 *
 * @created 2026-11-08
 *
 * @updated 2026-11-08
 *
 * @description
 * Repositorio de la capa de persistencia de auxiliares: expone las consultas
 * que unen `auxiliar_curso`, `usuario` y `curso` sin pasar por la capa de
 * servicio. Es la primera pieza de la capa repositorio del proyecto.
 *
 * La consulta principal trae los auxiliares de un docente en un solo viaje
 * (joins, sin N+1), no filtra por estado —los dados de baja siguen en la
 * lista— y ordena por curso y luego por apellido, con el nombre como
 * desempate determinista. La normalización del estado a ACTIVO/BAJA es
 * responsabilidad del servicio.
 *
 * @changelog
 * - 2026-11-08  [OchoaCesar]  feat: creación inicial con `delDocente()`.
 * - 2026-11-08  [OchoaCesar]  fix: la docblock ya no referencia
 *   FuenteAuxiliares (la pestaña "Ver auxiliares" se retiró).
 *
 * @see  App\Services\Auxiliar\ListarAuxiliaresDocenteService
 * @see  App\Http\Controllers\Api\AuxiliaresDocenteController
 */

namespace App\Repositories;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AuxiliarCursoRepository
{
    /**
     * Asignaciones de auxiliares (auxiliar + curso + estado) de un docente.
     *
     * Una sola consulta con joins: cada fila trae el auxiliar (`usuario`),
     * su curso y el estado de la asignación. No se excluye ningún estado
     * y no se resuelven colecciones por fila, así que no hay N+1.
     *
     * @param  int  $idDocente  ID del usuario docente (`curso.sis_doc`).
     * @return Collection<int, \stdClass> Filas con id_usuario, cod_sis,
     *                                    nombre_usuario, apellido,
     *                                    nombre_curso y estado, ordenadas por
     *                                    curso y apellido.
     */
    public function delDocente(int $idDocente): Collection
    {
        return $this->consultaBase()
            ->where('curso.sis_doc', $idDocente)
            ->orderBy('curso.nombre_curso')
            ->orderBy('usuario.apellido')
            ->orderBy('usuario.nombre_usuario')
            ->get([
                'usuario.id_usuario',
                'usuario.cod_sis',
                'usuario.nombre_usuario',
                'usuario.apellido',
                'curso.nombre_curso',
                'auxiliar_curso.estado',
            ]);
    }

    /**
     * Base compartida de la consulta: unión de auxiliar, curso y estado.
     */
    private function consultaBase(): Builder
    {
        return DB::table('auxiliar_curso')
            ->join('usuario', 'usuario.id_usuario', '=', 'auxiliar_curso.id_auxiliar')
            ->join('curso', 'curso.id_curso', '=', 'auxiliar_curso.id_curso');
    }
}
