<?php

/**
 * @file    ListarAuxiliaresDocenteService.php
 *
 * @author  OchoaCesar <cesareduardonick@gmail.com>
 *
 * @created 2026-11-08
 *
 * @updated 2026-11-08
 *
 * @description
 * Servicio de dominio que lista todas las cuentas de auxiliar con sus cursos
 * activos del docente y su estado derivado de esas asignaciones.
 *
 * El repositorio entrega las cuentas y las asignaciones activas en dos consultas
 * (sin N+1); este servicio las proyecta al contrato de la lista —`id`, `nombre`,
 * `apellido`, `codigoSis`, `curso`, `estado`— con dos valores posibles:
 *
 *  - `ACTIVO`    si el auxiliar está asignado a al menos un curso del docente.
 *  - `INACTIVO`  si solo existe su cuenta, sin asignaciones activas.
 *
 * El estado grabado en `auxiliar_curso` ya no se muestra: una baja deja de ser
 * un estado de la lista y simplemente deja de contar como asignación.
 *
 * @changelog
 * - 2026-11-08  [OchoaCesar]  feat: creación inicial del servicio con
 *   normalización de estado a ACTIVO/BAJA.
 * - 2026-11-24  [Equipo T1]  refactor: el estado sale de las asignaciones
 *   activas (ACTIVO/INACTIVO) y se listan también las cuentas sin curso.
 * - 2026-11-08  [OchoaCesar]  fix: la docblock ya no referencia
 *   FuenteAuxiliares (la pestaña "Ver auxiliares" se retiró).
 *
 * @see  App\Repositories\AuxiliarCursoRepository
 * @see  App\Http\Controllers\Api\AuxiliaresDocenteController
 */

namespace App\Services\Auxiliar;

use App\Repositories\AuxiliarCursoRepository;

class ListarAuxiliaresDocenteService
{
    public function __construct(
        private readonly AuxiliarCursoRepository $repositorio,
    ) {}

    /**
     * Auxiliares del docente con el contrato de la fuente de la lista.
     *
     * @param  int  $idDocente  ID del usuario docente (`curso.sis_doc`).
     * @return list<array{id: int, nombre: string, apellido: string, codigoSis: string, curso: string, estado: string}>
     */
    public function ejecutar(int $idDocente): array
    {
        $asignaciones = $this->repositorio->asignacionesActivas($idDocente)
            ->groupBy('id_auxiliar');

        return $this->repositorio->auxiliares()
            ->map(function (object $auxiliar) use ($asignaciones): array {
                $cursos = $asignaciones->get($auxiliar->id_usuario, collect())
                    ->pluck('nombre_curso')
                    ->unique()
                    ->values()
                    ->all();

                return [
                    'id' => (int) $auxiliar->id_usuario,
                    'nombre' => (string) $auxiliar->nombre_usuario,
                    'apellido' => (string) $auxiliar->apellido,
                    'codigoSis' => (string) $auxiliar->cod_sis,
                    'curso' => implode(', ', $cursos),
                    'estado' => $cursos === [] ? 'INACTIVO' : 'ACTIVO',
                ];
            })
            ->sortBy(fn (array $fila): string => ($fila['estado'] === 'INACTIVO' ? '1 ' : '0 ')
                .mb_strtolower($fila['apellido']).' '.mb_strtolower($fila['nombre']))
            ->values()
            ->all();
    }
}
