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
 * - 2026-11-21  [Equipo T1]  feat: listado, modal, confirmación y aviso de éxito.
 */

declare(strict_types=1);

namespace App\Livewire\Cursos;

use App\Enums\CursoEstado;
use App\Models\Curso;
use App\Models\Usuario;
use App\Services\Curso\ArchivarCursoService;
use App\Services\Curso\ListarCursosDocenteService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Throwable;

class ArchivarCurso extends Component
{
    public bool $modalAbierto = false;

    public ?int $cursoId = null;

    public string $cursoNombre = '';

    public bool $archivando = false;

    public string $errorArchivar = '';

    public bool $mostrarExito = false;

    public string $mensajeExito = '';

    public function cerrarModal(): void
    {
        if ($this->archivando) {
            return;
        }

        $this->reset(['modalAbierto', 'cursoId', 'cursoNombre', 'archivando', 'errorArchivar']);
    }

    public function abrirModal(int $cursoId): void
    {
        $curso = Curso::query()
            ->whereKey($cursoId)
            ->where('sis_doc', auth()->id())
            ->where('estado', CursoEstado::EnCurso->value)
            ->firstOrFail();

        $this->cursoId = (int) $curso->getKey();
        $this->cursoNombre = (string) $curso->nombre_curso;
        $this->errorArchivar = '';
        $this->archivando = false;
        $this->modalAbierto = true;
    }

    public function archivar(): void
    {
        if ($this->archivando || $this->cursoId === null) {
            return;
        }

        $this->archivando = true;
        $this->errorArchivar = '';

        $docente = auth()->user();
        abort_unless($docente instanceof Usuario, 401);

        try {
            $curso = Curso::query()->findOrFail($this->cursoId);
            app(ArchivarCursoService::class)->ejecutar($docente, $curso);

            $this->modalAbierto = false;
            $this->cursoId = null;
            $this->cursoNombre = '';
            $this->mensajeExito = ArchivarCursoService::MENSAJE_EXITO;
            $this->mostrarExito = true;
        } catch (ValidationException $e) {
            $this->errorArchivar = $e->errors()['curso'][0] ?? 'No se pudo archivar el curso.';
        } catch (AuthorizationException) {
            $this->errorArchivar = 'No tienes permiso para archivar este curso.';
        } catch (Throwable $e) {
            report($e);
            $this->errorArchivar = 'No se pudo archivar el curso.';
        } finally {
            $this->archivando = false;
        }
    }

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
