<?php

/**
 * @file    Index.php
 * @author  David E. Chavez T. <virzuzz12345@gmail.com>
 * @created 2026-11-08
 * @updated 2026-11-08
 *
 * @description
 * Componente Livewire de la página de invitaciones: pestañas para recibir y
 * enviar invitaciones a exámenes compartidos.
 *
 * @changelog
 * - 2026-11-08  [David E. Chavez T.]  feat: creación inicial.
 *
 * @see  App\Services\Invitacion\ListarInvitacionesRecibidasService
 */

namespace App\Livewire\Invitaciones;

use App\Models\Curso;
use App\Models\Examen;
use App\Models\Usuario;
use App\Services\Curso\ListarCursosDocenteService;
use App\Services\Examen\ListarExamenesCursoService;
use App\Services\Invitacion\BuscarDocentesInvitablesService;
use App\Services\Invitacion\EnviarInvitacionExamenService;
use App\Services\Invitacion\ListarInvitacionesRecibidasService;
use App\Services\Invitacion\ResponderInvitacionExamenService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class Index extends Component
{
    public string $tab = 'recibir';

    public int $idCurso = 0;

    public int $idExamen = 0;

    public string $busquedaDocente = '';

    public ?int $docenteSeleccionado = null;

    public string $mensajeExito = '';

    public string $mensajeError = '';

    public array $invitacionesRecibidas = [];

    public function mount(
        ListarCursosDocenteService $cursos,
        ListarInvitacionesRecibidasService $recibidas,
    ): void {
        $this->cursos = $cursos->activos((int) auth()->id())->map(fn (Curso $curso): array => [
            'id' => (int) $curso->id_curso,
            'nombre' => (string) $curso->nombre_curso,
        ])->all();

        $this->invitacionesRecibidas = $recibidas->ejecutar(auth()->user())->all();
    }

    public array $cursos = [];

    public array $examenes = [];

    public array $docentes = [];

    public function updatedIdCurso(ListarExamenesCursoService $servicio): void
    {
        $this->idExamen = 0;
        $this->examenes = [];

        if ($this->idCurso <= 0) {
            return;
        }

        $this->examenes = $servicio->ejecutar($this->idCurso)
            ->map(fn (Examen $examen): array => [
                'id' => (int) $examen->id_examen,
                'nombre' => (string) $examen->nombre_examen,
            ])
            ->all();
    }

    public function cambiarTab(string $tab): void
    {
        $this->tab = $tab === 'enviar' ? 'enviar' : 'recibir';
        $this->mensajeExito = '';
        $this->mensajeError = '';
    }

    public function cargarRecibidas(ListarInvitacionesRecibidasService $servicio): void
    {
        $this->invitacionesRecibidas = $servicio->ejecutar(auth()->user())->all();
    }

    public function buscarDocentes(BuscarDocentesInvitablesService $servicio): void
    {
        $this->mensajeError = '';

        if ($this->idExamen <= 0) {
            $this->mensajeError = 'Debe seleccionar un examen.';
            $this->docentes = [];

            return;
        }

        if (mb_strlen(trim($this->busquedaDocente)) < 2) {
            $this->docentes = [];

            return;
        }

        $examen = Examen::query()->findOrFail($this->idExamen);
        $this->docentes = $servicio->ejecutar(auth()->user(), $examen, trim($this->busquedaDocente))->values()->all();
    }

    public function enviar(EnviarInvitacionExamenService $servicio): void
    {
        $this->mensajeExito = '';
        $this->mensajeError = '';

        if ($this->idExamen <= 0 || $this->docenteSeleccionado === null) {
            $this->mensajeError = 'Debe seleccionar un examen y un docente.';
            return;
        }

        $examen = Examen::query()->findOrFail($this->idExamen);

        try {
            $servicio->ejecutar(auth()->user(), $examen, $this->docenteSeleccionado);
        } catch (\Throwable $error) {
            $this->mensajeError = $error->getMessage() ?: 'No se pudo enviar la invitación.';

            return;
        }

        $this->mensajeExito = 'Invitación enviada correctamente.';
        $this->busquedaDocente = '';
        $this->docentes = [];
        $this->docenteSeleccionado = null;
        $this->dispatch('invitaciones-pendientes', pendientes: $this->contarPendientes());
    }

    public function aceptar(int $idInvitacion, ResponderInvitacionExamenService $servicio): void
    {
        $this->mensajeExito = '';
        $this->mensajeError = '';

        $invitacion = \App\Models\InvitacionExamenCompartido::query()->findOrFail($idInvitacion);

        try {
            $servicio->aceptar(auth()->user(), $invitacion);
        } catch (\Throwable $error) {
            $this->mensajeError = $error->getMessage() ?: 'No se pudo aceptar la invitación.';

            return;
        }

        $this->mensajeExito = 'Invitación aceptada.';
        $this->cargarRecibidas(app(ListarInvitacionesRecibidasService::class));
        $this->dispatch('invitaciones-pendientes', pendientes: $this->contarPendientes());
    }

    public function rechazar(int $idInvitacion, ResponderInvitacionExamenService $servicio): void
    {
        $this->mensajeExito = '';
        $this->mensajeError = '';

        $invitacion = \App\Models\InvitacionExamenCompartido::query()->findOrFail($idInvitacion);

        try {
            $servicio->rechazar(auth()->user(), $invitacion);
        } catch (\Throwable $error) {
            $this->mensajeError = $error->getMessage() ?: 'No se pudo rechazar la invitación.';

            return;
        }

        $this->mensajeExito = 'Invitación rechazada.';
        $this->cargarRecibidas(app(ListarInvitacionesRecibidasService::class));
        $this->dispatch('invitaciones-pendientes', pendientes: $this->contarPendientes());
    }

    private function contarPendientes(): int
    {
        return app(ListarInvitacionesRecibidasService::class)->contarPendientes(auth()->user());
    }

    public function render(): View
    {
        return view('livewire.invitaciones.index')->extends('layouts.app');
    }
}
