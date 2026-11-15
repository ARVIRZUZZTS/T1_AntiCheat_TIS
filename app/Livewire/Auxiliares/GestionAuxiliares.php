<?php

/**
 * @file    GestionAuxiliares.php
 * @author  David E. Chavez T. <virzuzz12345@gmail.com>
 * @created 2026-10-19
 * @updated 2026-11-08
 * @updated 2026-11-09
 * @updated 2026-11-15
 *
 * @description
 * Componente Livewire del listado de auxiliares del docente: tabla de
 * auxiliares asignados con filtros por estado (Todos/Activo/Baja) y por curso.
 * El alta dejó de ser un modal: ahora es su propia página (CrearAuxiliar), y
 * el botón "Crear Auxiliar" enlaza a ella. El aviso de éxito del alta llega por
 * la sesión al volver a la lista.
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
 *
 * @see  App\Livewire\Auxiliares\CrearAuxiliar
 * @see  App\Models\Curso
 */

namespace App\Livewire\Auxiliares;

use App\Models\Curso;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

/**
 * Lista los auxiliares asignados a los cursos del docente autenticado.
 *
 * @package  App\Livewire\Auxiliares
 * @author   David E. Chavez T. <virzuzz12345@gmail.com>
 * @since    2026-10-19
 */
class GestionAuxiliares extends Component
{
    /**
     * Filtro de estado del listado: todos, Activo o Baja.
     *
     * @var string
     */
    public string $filtroEstado = 'todos';

    /**
     * Filtro de curso del listado: todos o el id de un curso del docente.
     *
     * @var string
     */
    public string $filtroCurso = 'todos';

    /**
     * Búsqueda por nombre, apellido o código SIS del auxiliar.
     *
     * @var string
     */
    public string $busqueda = '';

    /**
     * Fija el filtro de estado (todos / Activo / Baja) desde los botones.
     */
    public function filtrarEstado(string $estado): void
    {
        $this->filtroEstado = in_array($estado, ['Activo', 'Baja'], true) ? $estado : 'todos';
    }

    /**
     * Cursos del docente autenticado (para el filtro por curso).
     *
     * @return \Illuminate\Support\Collection<int, \App\Models\Curso>
     */
    public function cursos()
    {
        return Curso::query()
            ->where('sis_doc', auth()->id())
            ->orderBy('nombre_curso')
            ->get();
    }

    /**
     * Auxiliares asignados a los cursos del docente, con los filtros vigentes.
     *
     * @return \Illuminate\Support\Collection<int, \stdClass>
     */
    public function auxiliares()
    {
        $consulta = DB::table('auxiliar_curso')
            ->join('curso', 'curso.id_curso', '=', 'auxiliar_curso.id_curso')
            ->join('usuario', 'usuario.id_usuario', '=', 'auxiliar_curso.id_auxiliar')
            ->where('curso.sis_doc', auth()->id())
            ->select('usuario.id_usuario', 'usuario.cod_sis', 'usuario.nombre_usuario', 'usuario.apellido', 'curso.nombre_curso', 'auxiliar_curso.estado');

        if (in_array($this->filtroEstado, ['Activo', 'Baja'], true)) {
            $consulta->where('auxiliar_curso.estado', $this->filtroEstado);
        }

        if ($this->filtroCurso !== 'todos') {
            $consulta->where('auxiliar_curso.id_curso', (int) $this->filtroCurso);
        }

        $termino = trim($this->busqueda);
        if ($termino !== '') {
            // Búsqueda por nombre, apellido o código SIS (ilike = sin distinguir
            // mayúsculas en Postgres).
            $consulta->where(function ($q) use ($termino): void {
                $q->where('usuario.nombre_usuario', 'ilike', '%'.$termino.'%')
                    ->orWhere('usuario.apellido', 'ilike', '%'.$termino.'%')
                    ->orWhere('usuario.cod_sis', 'ilike', '%'.$termino.'%');
            });
        }

        return $consulta
            ->orderBy('usuario.nombre_usuario')
            ->get();
    }

    /**
     * Renderiza el listado de auxiliares.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function render()
    {
        return view('livewire.auxiliares.gestion-auxiliares', [
            'cursos' => $this->cursos(),
            'auxiliares' => $this->auxiliares(),
        ])->extends('layouts.app');
    }
}
