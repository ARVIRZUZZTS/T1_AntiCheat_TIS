<?php

/**
 * @file    ArchivarRestaurarCurso.php
 *
 * @author  OchoaCesar <cesareduardonick@gmail.com>
 *
 * @created 2026-11-22
 *
 * @updated 2026-11-23
 *
 * @description
 * Componente de la subopción «Archivar/Restaurar cursos»: una sola lista con
 * los cursos propios en curso y los archivados al final. Cada tarjeta ofrece
 * su acción como texto: «Archivar» en rojo para los activos y «Restaurar» en
 * gris para los archivados. Cada acción se confirma en su modal y delega en su
 * servicio (Gate + transacción + lockForUpdate); el resultado sale en el mismo
 * aviso inline de éxito. Bajo el nombre de cada curso se muestra su gestión con
 * año, igual que en «Ver cursos».
 *
 * @changelog
 * - 2026-11-22  [OchoaCesar]  feat: fusiona las subsecciones "Archivar curso"
 *   y "Restaurar curso" en una página única con pestañas; se retiran los dos
 *   componentes y vistas separados.
 * - 2026-11-23  [OchoaCesar]  refactor: se eliminan las pestañas; la lista es
 *   única con los archivados al final y la apertura de un modal cierra el otro.
 * - 2026-11-23  [OchoaCesar]  feat: cada tarjeta muestra la gestión del curso
 *   bajo el título, con el mismo formato que «Ver cursos».
 */

declare(strict_types=1);

namespace App\Livewire\Cursos;

use App\Enums\CursoEstado;
use App\Models\Curso;
use App\Models\Usuario;
use App\Services\Curso\ArchivarCursoService;
use App\Services\Curso\ListarCursosDocenteService;
use App\Services\Curso\RestaurarCursoService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Throwable;

class ArchivarRestaurarCurso extends Component
{
    public bool $modalAbierto = false;

    public bool $modalArchivarAbierto = false;

    public ?int $cursoId = null;

    public string $cursoNombre = '';

    public bool $archivando = false;

    public string $errorArchivar = '';

    public bool $mostrarExito = false;

    public string $mensajeExito = '';

    public function mount(): void
    {
        // El POST de los endpoints *.store llega por redirect con el mensaje
        // en sesión; se pasa al aviso unificado de la página.
        if (session()->has('mensaje')) {
            $this->mensajeExito = (string) session('mensaje');
            $this->mostrarExito = true;
        }
    }

    public function cerrarModal(): void
    {
        if ($this->archivando) {
            return;
        }

        $this->modalArchivarAbierto = false;
        $this->cursoId = null;
        $this->cursoNombre = '';
        $this->errorArchivar = '';
    }

    public function abrirModalArchivar(int $cursoId): void
    {
        $curso = Curso::query()
            ->whereKey($cursoId)
            ->where('sis_doc', auth()->id())
            ->where('estado', CursoEstado::EnCurso->value)
            ->firstOrFail();

        $this->cerrarModales();
        $this->cursoId = (int) $curso->getKey();
        $this->cursoNombre = (string) $curso->nombre_curso;
        $this->modalArchivarAbierto = true;
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

            $this->modalArchivarAbierto = false;
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

    public function abrirModalRestaurar(int $cursoId, string $cursoNombre): void
    {
        $this->cerrarModales();
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
            $this->cerrarConError('No se pudo restaurar el curso.');

            return;
        }

        if ((int) $curso->sis_doc !== (int) $docente->id_usuario) {
            $this->cerrarConError('No tienes permiso para restaurar este curso.');

            return;
        }

        if ($curso->estado !== CursoEstado::Archivado->value) {
            $this->cerrarConError('Solo se pueden restaurar cursos en estado Archivado.');

            return;
        }

        try {
            app(RestaurarCursoService::class)->ejecutar($docente, $curso);
        } catch (ValidationException $e) {
            $this->cerrarConError($e->errors()['curso'][0] ?? 'No se pudo restaurar el curso.');

            return;
        } catch (AuthorizationException) {
            $this->cerrarConError('No tienes permiso para restaurar este curso.');

            return;
        }

        $this->modalAbierto = false;
        $this->cursoId = null;
        $this->cursoNombre = '';
        $this->mensajeExito = RestaurarCursoService::MENSAJE_EXITO;
        $this->mostrarExito = true;
    }

    public function cerrarAviso(): void
    {
        $this->mostrarExito = false;
        $this->mensajeExito = '';
    }

    public function render(ListarCursosDocenteService $servicio): View
    {
        $idDocente = (int) auth()->id();

        $cursos = $servicio->archivables($idDocente)
            ->concat($servicio->archivados($idDocente));

        $idsCursos = $cursos->pluck('id_curso')->map(fn ($id): int => (int) $id)->all();

        return view('livewire.cursos.archivar-restaurar-curso', [
            'cursos' => $cursos,
            'gestiones' => $servicio->gestionesPorCurso($idsCursos),
        ])->extends('layouts.app');
    }

    private function cerrarConError(string $mensaje): void
    {
        session()->flash('error', $mensaje);
        $this->modalAbierto = false;
        $this->cursoId = null;
        $this->cursoNombre = '';
    }

    private function cerrarModales(): void
    {
        $this->modalAbierto = false;
        $this->modalArchivarAbierto = false;
        $this->cursoId = null;
        $this->cursoNombre = '';
        $this->archivando = false;
        $this->errorArchivar = '';
    }
}
