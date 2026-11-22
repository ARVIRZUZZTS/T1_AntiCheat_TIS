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
 */

declare(strict_types=1);

namespace App\Livewire\Cursos;

use App\Services\Curso\ListarCursosDocenteService;
use Illuminate\Contracts\View\View;
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
    }

    public function render(ListarCursosDocenteService $servicio): View
    {
        return view('livewire.cursos.restaurar-curso', [
            'cursos' => $servicio->archivados((int) auth()->id()),
        ])->extends('layouts.app');
    }
}
