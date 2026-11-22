<?php

/**
 * @file    RestaurarCurso.php
 *
 * @author  Equipo T1
 *
 * @created 2026-11-21
 *
 * @description
 * Componente de la subsección «Restaurar curso»: lista los cursos archivados
 * del docente y restaura uno desde un modal de confirmación.
 *
 * @changelog
 * - 2026-11-21  [Equipo T1]  feat: creación del componente y su ruta (Refs #326).
 * - 2026-11-21  [Equipo T1]  feat: lista los archivados desde el servicio (Refs #326).
 * - 2026-11-21  [Equipo T1]  feat: confirma la restauración con el servicio (Refs #326).
 */

declare(strict_types=1);

namespace App\Livewire\Cursos;

use App\Models\Curso;
use App\Models\Usuario;
use App\Services\Curso\ListarCursosDocenteService;
use App\Services\Curso\RestaurarCursoService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class RestaurarCurso extends Component
{
    public bool $modalAbierto = false;

    public ?int $cursoId = null;

    public string $cursoNombre = '';

    public function abrirModal(int $cursoId, string $cursoNombre): void
    {
        $this->cursoId = $cursoId;
        $this->cursoNombre = $cursoNombre;
        $this->modalAbierto = true;
    }

    public function cancelar(): void
    {
        $this->modalAbierto = false;
        $this->cursoId = null;
        $this->cursoNombre = '';
    }

    public function confirmarRestauracion(): void
    {
        if (! $this->modalAbierto || $this->cursoId === null) {
            return;
        }

        $docente = auth()->user();
        $curso = Curso::find($this->cursoId);

        if (! $docente instanceof Usuario || $curso === null) {
            session()->flash('error', 'No se pudo restaurar el curso.');
            $this->modalAbierto = false;

            return;
        }

        try {
            app(RestaurarCursoService::class)->ejecutar($docente, $curso);
        } catch (ValidationException $e) {
            session()->flash('error', $e->errors()['curso'][0] ?? 'No se pudo restaurar el curso.');
            $this->modalAbierto = false;

            return;
        } catch (AuthorizationException) {
            session()->flash('error', 'No tienes permiso para restaurar este curso.');
            $this->modalAbierto = false;

            return;
        }

        $this->modalAbierto = false;
        $this->cursoId = null;
        $this->cursoNombre = '';
        $this->dispatch('$refresh');
        $this->dispatch('restauracion-curso-exitosa', mensaje: RestaurarCursoService::MENSAJE_EXITO);
    }

    public function render(ListarCursosDocenteService $servicio): View
    {
        return view('livewire.cursos.restaurar-curso', [
            'cursos' => $servicio->archivados((int) auth()->id()),
        ])->extends('layouts.app');
    }
}
