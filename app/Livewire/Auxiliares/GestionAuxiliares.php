<?php

/**
 * @file    GestionAuxiliares.php
 *
 * @author  David E. Chavez T. <virzuzz12345@gmail.com>
 *
 * @created 2026-10-19
 *
 * @updated 2026-11-08
 * @updated 2026-11-09
 * @updated 2026-11-15
 *
 * @description
 * Componente Livewire del listado de auxiliares del docente: tabla de todas
 * las cuentas de auxiliar con filtros por estado (Todos/Activo/Inactivo) y por
 * curso. El alta dejó de ser un modal: ahora es su propia página (CrearAuxiliar),
 * y el botón "Crear Auxiliar" enlaza a ella. El aviso de éxito del alta llega
 * por la sesión al volver a la lista.
 *
 * @changelog
 * - 2026-10-19  [ARVIRZUZZTS]  feat: agregar autenticación y gestión de
 *   docentes/auxiliares.
 * - 2026-11-08  [David E. Chavez T.]  refactor: mover la creación de auxiliar
 *   a un modal con inputs verticales.
 * - 2026-11-09  [OchoaCesar]  feat: filtros de estado (Todos/Activo/Baja) y
 *   curso (Todos + cursos del docente) sobre el listado.
 * - 2026-11-15  [Diego Tejerina]  refactor: el alta pasa de modal a página
 *   propia (CrearAuxiliar); este componente queda solo con el listado y sus
 *   filtros.
 * - 2026-11-24  [Equipo T1]  refactor: se quita el estado Baja; un auxiliar es
 *   Activo si tiene asignación activa a algún curso e Inactivo si solo existe
 *   su cuenta.
 *
 * @see  App\Livewire\Auxiliares\CrearAuxiliar
 * @see  App\Models\Curso
 */

namespace App\Livewire\Auxiliares;

use App\Models\Curso;
use App\Services\Auxiliar\AccesoAuxiliarService;
use App\Services\Auxiliar\ListarAuxiliaresDocenteService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

/**
 * Lista todas las cuentas de auxiliar, con el estado según sus asignaciones
 * activas en los cursos del docente autenticado.
 *
 * @author   David E. Chavez T. <virzuzz12345@gmail.com>
 *
 * @since    2026-10-19
 */
class GestionAuxiliares extends Component
{
    /**
     * Filtro de estado del listado: todos, Activo o Inactivo.
     */
    public string $filtroEstado = 'todos';

    /**
     * Filtro de curso del listado: todos o el id de un curso del docente.
     */
    public string $filtroCurso = 'todos';

    /**
     * Búsqueda por nombre, apellido o código SIS del auxiliar.
     */
    public string $busqueda = '';

    /**
     * Fija el filtro de estado (todos / Activo / Inactivo) desde los botones.
     */
    public function filtrarEstado(string $estado): void
    {
        $this->filtroEstado = in_array($estado, ['Activo', 'Inactivo'], true) ? $estado : 'todos';
    }

    /**
     * Cursos del docente autenticado (para el filtro por curso).
     *
     * @return Collection<int, Curso>
     */
    public function cursos()
    {
        return Curso::query()
            ->where('sis_doc', auth()->id())
            ->orderBy('nombre_curso')
            ->get();
    }

    /**
     * Todas las cuentas de auxiliar con los filtros vigentes. El estado se
     * deriva de la existencia de asignaciones activas, no de `auxiliar_curso.estado`.
     *
     * @return Collection<int, \stdClass>
     */
    public function auxiliares(): Collection
    {
        $idDocente = (int) auth()->id();
        $filas = collect(app(ListarAuxiliaresDocenteService::class)->ejecutar($idDocente));

        if ($this->filtroCurso !== 'todos') {
            $asignados = $this->idsAsignadosAlCurso($idDocente, (int) $this->filtroCurso);
            $filas = $filas->filter(fn (array $fila): bool => in_array($fila['id'], $asignados, true));
        }

        $estado = $this->filtroEstado === 'todos' ? null : strtoupper($this->filtroEstado);

        if ($estado !== null) {
            $filas = $filas->filter(fn (array $fila): bool => $fila['estado'] === $estado);
        }

        $termino = mb_strtolower(trim($this->busqueda));

        if ($termino !== '') {
            $filas = $filas->filter(fn (array $fila): bool => $this->coincide($fila, $termino));
        }

        return $filas
            ->map(function (array $fila): \stdClass {
                $auxiliar = new \stdClass;
                $auxiliar->id_usuario = $fila['id'];
                $auxiliar->cod_sis = $fila['codigoSis'];
                $auxiliar->nombre_usuario = $fila['nombre'];
                $auxiliar->apellido = $fila['apellido'];
                $auxiliar->nombre_curso = $fila['curso'];
                $auxiliar->estado = $fila['estado'] === 'ACTIVO' ? 'Activo' : 'Inactivo';

                return $auxiliar;
            })
            ->values();
    }

    /** @return list<int> */
    private function idsAsignadosAlCurso(int $idDocente, int $idCurso): array
    {
        return DB::table('auxiliar_curso as ac')
            ->join('curso as c', 'c.id_curso', '=', 'ac.id_curso')
            ->where('c.sis_doc', $idDocente)
            ->where('c.id_curso', $idCurso)
            ->where('ac.estado', AccesoAuxiliarService::ESTADO_ACTIVO)
            ->pluck('ac.id_auxiliar')
            ->map(fn (mixed $id): int => (int) $id)
            ->all();
    }

    /** @param array<string, mixed> $fila */
    private function coincide(array $fila, string $termino): bool
    {
        foreach (['nombre', 'apellido', 'codigoSis'] as $campo) {
            if (mb_strpos(mb_strtolower((string) $fila[$campo]), $termino) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Renderiza el listado de auxiliares.
     *
     * @return View
     */
    public function render()
    {
        return view('livewire.auxiliares.gestion-auxiliares', [
            'cursos' => $this->cursos(),
            'auxiliares' => $this->auxiliares(),
        ])->extends('layouts.app');
    }
}
