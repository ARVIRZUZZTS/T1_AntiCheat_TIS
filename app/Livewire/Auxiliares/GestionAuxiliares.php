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
 * @updated 2026-11-24
 *
 * @description
 * Componente Livewire del listado de auxiliares del docente: tabla de todas
 * las cuentas de auxiliar con filtro por estado (Todos/Activo/Inactivo) y
 * búsqueda por nombre o código SIS. El alta dejó de ser un modal: ahora es su
 * propia página (CrearAuxiliar), y el botón "Crear Auxiliar" enlaza a ella. El
 * aviso de éxito del alta llega por la sesión al volver a la lista. La acción
 * «Ver detalle» abre un modal con todos los cursos activos del auxiliar.
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
 * - 2026-11-24  [OchoaCesar]  refactor: se retira el filtro por curso (y su
 *   código asociado); quedan el filtro de estado y la búsqueda.
 * - 2026-11-24  [OchoaCesar]  feat: «Ver detalle» abre un modal con todos los
 *   cursos activos del auxiliar elegido.
 *
 * @see  App\Livewire\Auxiliares\CrearAuxiliar
 * @see  resources\views\components\ui\modal-detalle-auxiliar.blade.php
 * @see  App\Models\Curso
 */

namespace App\Livewire\Auxiliares;

use App\Models\Usuario;
use App\Services\Auxiliar\AccesoAuxiliarService;
use App\Services\Auxiliar\ListarAuxiliaresDocenteService;
use App\Services\Auxiliar\ReenviarCredencialesAuxiliarService;
use App\Support\NombreCurso;
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
     * Búsqueda por nombre, apellido o código SIS del auxiliar.
     */
    public string $busqueda = '';

    /**
     * Modal de detalle: true mientras muestre los cursos de un auxiliar.
     */
    public bool $detalleAbierto = false;

    /**
     * Cursos activos del auxiliar elegido en «Ver detalle».
     *
     * @var list<string>
     */
    public array $detalleCursos = [];

    /** Aviso tras reenviar credenciales (vacío si no hay nada que mostrar). */
    public string $mensajeReenvio = '';

    /**
     * Fija el filtro de estado (todos / Activo / Inactivo) desde los botones.
     */
    public function filtrarEstado(string $estado): void
    {
        $this->filtroEstado = in_array($estado, ['Activo', 'Inactivo'], true) ? $estado : 'todos';
    }

    /**
     * Abre el modal «Ver detalle» con los cursos activos del auxiliar
     * indicado (solo si sigue en el listado vigente).
     */
    public function verDetalle(int $idAuxiliar): void
    {
        $auxiliar = $this->auxiliares()->firstWhere('id', $idAuxiliar);

        if ($auxiliar === null) {
            return;
        }

        $this->detalleCursos = $this->cursosDelAuxiliar($idAuxiliar);
        $this->detalleAbierto = true;
    }

    /**
     * Reenvía (reseteando) las credenciales del auxiliar y muestra el aviso.
     * Solo opera sobre auxiliares que están en la lista vigente.
     */
    public function reenviarCredenciales(int $idAuxiliar): void
    {
        $fila = $this->auxiliares()->firstWhere('id', $idAuxiliar);

        if ($fila === null) {
            return;
        }

        $auxiliar = Usuario::query()->find($idAuxiliar);

        if ($auxiliar === null || ! $auxiliar->esAuxiliar()) {
            return;
        }

        $enviado = app(ReenviarCredencialesAuxiliarService::class)->reenviar($auxiliar);

        $this->mensajeReenvio = $enviado
            ? 'Credenciales reenviadas a '.$fila['correo']
            : 'No se pudo enviar el correo a '.$fila['correo'].'. Inténtalo de nuevo.';
    }

    /**
     * Cierra el modal «Ver detalle» y limpia sus cursos.
     */
    public function cerrarDetalle(): void
    {
        $this->detalleAbierto = false;
        $this->detalleCursos = [];
    }

    /**
     * Cursos activos del auxiliar en los cursos del docente autenticado
     * (mismo criterio con el que se deriva el estado de la lista).
     *
     * @return list<string>
     */
    private function cursosDelAuxiliar(int $idAuxiliar): array
    {
        return DB::table('auxiliar_curso as ac')
            ->join('curso as c', 'c.id_curso', '=', 'ac.id_curso')
            ->leftJoin('curso_tipo_gestion as ctg', 'ctg.id_curso', '=', 'c.id_curso')
            ->leftJoin('tipo_gestion as tg', 'tg.id_tipo_gestion', '=', 'ctg.id_tg')
            ->where('c.sis_doc', (int) auth()->id())
            ->where('ac.id_auxiliar', $idAuxiliar)
            ->where('ac.estado', AccesoAuxiliarService::ESTADO_ACTIVO)
            ->orderBy('c.nombre_curso')
            ->get(['c.nombre_curso', 'tg.nombre_tipo_gestion', 'ctg.anio'])
            ->map(fn (object $curso): string => NombreCurso::conGestion(
                (string) $curso->nombre_curso,
                $curso->nombre_tipo_gestion !== null ? (string) $curso->nombre_tipo_gestion : null,
                $curso->anio !== null ? (int) $curso->anio : null,
            ))
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Todas las cuentas de auxiliar con los filtros vigentes. El estado se
     * deriva de la existencia de asignaciones activas (cursos o examenes), no de
     * `auxiliar_curso.estado`. Cada fila lleva su etiqueta de estado lista.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function auxiliares(): Collection
    {
        $idDocente = (int) auth()->id();
        $filas = collect(app(ListarAuxiliaresDocenteService::class)->ejecutar($idDocente, true));

        $estado = $this->filtroEstado === 'todos' ? null : strtoupper($this->filtroEstado);

        if ($estado !== null) {
            $filas = $filas->filter(fn (array $fila): bool => $fila['estado'] === $estado);
        }

        $termino = mb_strtolower(trim($this->busqueda));

        if ($termino !== '') {
            $filas = $filas->filter(fn (array $fila): bool => $this->coincide($fila, $termino));
        }

        return $filas
            ->map(function (array $fila): array {
                $fila['estadoEtiqueta'] = $fila['estado'] === 'ACTIVO' ? 'Activo' : 'Inactivo';

                return $fila;
            })
            ->values();
    }

    /** @param array<string, mixed> $fila */
    private function coincide(array $fila, string $termino): bool
    {
        foreach (['nombreCompleto', 'usuario', 'correo', 'carnet', 'codigoSis', 'tipo', 'curso'] as $campo) {
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
            'auxiliares' => $this->auxiliares(),
        ])->extends('layouts.app');
    }
}
