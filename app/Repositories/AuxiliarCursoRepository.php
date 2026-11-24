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
 * Las consultas separan las cuentas de auxiliar de sus asignaciones activas
 * del docente (estado 'Activo'); el servicio proyecta ambas en dos viajes, sin
 * N+1. El estado de la lista ya no sale de `auxiliar_curso.estado`: una cuenta
 * sin asignaciones activas es INACTIVO, con una o más es ACTIVO.
 *
 * @changelog
 * - 2026-11-08  [OchoaCesar]  feat: creación inicial con `delDocente()`.
 * - 2026-11-24  [Equipo T1]  refactor: el estado deja de tomarse de la fila
 *   de asignación y pasa a derivarse de la existencia de asignaciones activas.
 * - 2026-11-08  [OchoaCesar]  fix: la docblock ya no referencia
 *   FuenteAuxiliares (la pestaña "Ver auxiliares" se retiró).
 *
 * @see  App\Services\Auxiliar\ListarAuxiliaresDocenteService
 * @see  App\Http\Controllers\Api\AuxiliaresDocenteController
 */

namespace App\Repositories;

use App\Models\Rol;
use App\Services\Auxiliar\AccesoAuxiliarService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AuxiliarCursoRepository
{
    /**
     * Todas las cuentas con rol auxiliar, estén o no asignadas a un curso.
     *
     * @return Collection<int, \stdClass>
     */
    public function auxiliares(): Collection
    {
        return DB::table('usuario as u')
            ->join('rol_usuario as ru', 'ru.id_usuario', '=', 'u.id_usuario')
            ->join('rol as r', 'r.id_rol', '=', 'ru.id_rol')
            ->where('r.nombre_rol', Rol::NOMBRE_AUXILIAR)
            ->orderBy('u.apellido')
            ->orderBy('u.nombre_usuario')
            ->get([
                'u.id_usuario',
                'u.cod_sis',
                'u.nombre_usuario',
                'u.apellido',
            ]);
    }

    /**
     * Asignaciones activas dentro de los cursos del docente, una fila por curso.
     *
     * @param  int  $idDocente  ID del usuario docente (`curso.sis_doc`).
     * @return Collection<int, \stdClass>
     */
    public function asignacionesActivas(int $idDocente): Collection
    {
        return DB::table('auxiliar_curso as ac')
            ->join('curso as c', 'c.id_curso', '=', 'ac.id_curso')
            ->where('c.sis_doc', $idDocente)
            ->where('ac.estado', AccesoAuxiliarService::ESTADO_ACTIVO)
            ->orderBy('c.nombre_curso')
            ->get([
                'ac.id_auxiliar',
                'c.id_curso',
                'c.nombre_curso',
            ]);
    }
}
