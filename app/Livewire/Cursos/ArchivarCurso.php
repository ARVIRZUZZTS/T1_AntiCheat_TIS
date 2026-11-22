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

use App\Models\Curso;
use App\Services\Curso\ListarCursosDocenteService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Livewire\Component;

class ArchivarCurso extends Component
{
    public bool $modalAbierto = false;

    public ?int $cursoId = null;

    public string $cursoNombre = '';

    public bool $archivando = false;

    public string $errorArchivar = '';

    /**
     * Cursos propios en curso que todavía se pueden archivar.
     *
     * @return EloquentCollection<int, Curso>
     */
    public function cursos(): EloquentCollection
    {
        return app(ListarCursosDocenteService::class)->archivables((int) auth()->id());
    }

    public function render(): View
    {
        return view('livewire.cursos.archivar-curso', [
            'cursos' => $this->cursos(),
        ])->extends('layouts.app');
    }
}
