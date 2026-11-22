<?php

/**
 * @file    ArchivarCurso.php
 *
 * @author  Equipo T1
 *
 * @created 2026-11-21
 *
 * @description
 * Componente de la subsección «Archivar curso»: lista los cursos activos del
 * docente y confirma el archivado en un modal antes de llamar al servicio.
 *
 * @changelog
 * - 2026-11-21  [Equipo T1]  feat: creación del componente y su ruta.
 */

declare(strict_types=1);

namespace App\Livewire\Cursos;

use Illuminate\Contracts\View\View;
use Livewire\Component;

class ArchivarCurso extends Component
{
    public function render(): View
    {
        return view('livewire.cursos.archivar-curso')->extends('layouts.app');
    }
}
