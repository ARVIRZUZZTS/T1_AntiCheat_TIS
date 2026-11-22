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
    public function render(ListarCursosDocenteService $servicio): View
    {
        return view('livewire.cursos.restaurar-curso', [
            'cursos' => $servicio->archivados((int) auth()->id()),
        ])->extends('layouts.app');
    }
}
