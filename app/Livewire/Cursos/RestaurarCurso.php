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
 */

declare(strict_types=1);

namespace App\Livewire\Cursos;

use App\Enums\CursoEstado;
use App\Models\Curso;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class RestaurarCurso extends Component
{
    public function render(): View
    {
        return view('livewire.cursos.restaurar-curso', [
            'cursos' => Curso::query()
                ->where('sis_doc', auth()->id())
                ->where('estado', CursoEstado::Archivado->value)
                ->orderBy('nombre_curso')
                ->get(['id_curso', 'nombre_curso']),
        ])->extends('layouts.app');
    }
}
