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
 * Servicio de dominio que lista los auxiliares de un docente con su curso
 * y su estado normalizado.
 *
 * La consulta (union auxiliar + curso + estado, orden por curso y luego por
 * apellido, sin N+1) vive en `AuxiliarCursoRepository`; este servicio la
 * proyecta al contrato de `FuenteAuxiliares` —`id`, `nombre`, `apellido`,
 * `codigoSis`, `curso`, `estado`— y normaliza el estado a dos valores:
 *
 *  - `ACTIVO`  cuando la asignación está activa (`auxiliar_curso.estado`
 *              dice 'Activo', sin importar mayúsculas/minúsculas).
 *  - `BAJA`    en cualquier otro caso ('Baja', null o valor desconocido):
 *              solo es activo si lo dice explícitamente.
 *
 * Los auxiliares dados de baja no se filtran: siguen apareciendo en la lista.
 *
 * @changelog
 * - 2026-11-08  [OchoaCesar]  feat: creación inicial del servicio con
 *   normalización de estado a ACTIVO/BAJA.
 *
 * @see  App\Repositories\AuxiliarCursoRepository
 * @see  App\Livewire\Auxiliares\FuenteAuxiliares
 */

namespace App\Services\Auxiliar;

use App\Repositories\AuxiliarCursoRepository;
use Illuminate\Support\Collection;

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
        return $this->proyectar($this->repositorio->delDocente($idDocente));
    }

    /**
     * Pasa las filas del repositorio al contrato de salida, normalizando
     * el estado.
     *
     * @param  Collection<int, \stdClass>  $filas  Filas del repositorio.
     * @return list<array{id: int, nombre: string, apellido: string, codigoSis: string, curso: string, estado: string}>
     */
    private function proyectar(Collection $filas): array
    {
        return $filas
            ->map(fn (object $fila): array => [
                'id' => (int) $fila->id_usuario,
                'nombre' => (string) $fila->nombre_usuario,
                'apellido' => (string) $fila->apellido,
                'codigoSis' => (string) $fila->cod_sis,
                'curso' => (string) $fila->nombre_curso,
                'estado' => $this->normalizarEstado($fila->estado),
            ])
            ->values()
            ->all();
    }

    /**
     * Normaliza `auxiliar_curso.estado` a los dos valores del contrato.
     *
     * @param  mixed  $estado  Valor crudo de la base.
     * @return string 'ACTIVO' o 'BAJA'.
     */
    private function normalizarEstado(mixed $estado): string
    {
        return strtoupper(trim((string) $estado)) === 'ACTIVO' ? 'ACTIVO' : 'BAJA';
    }
}
