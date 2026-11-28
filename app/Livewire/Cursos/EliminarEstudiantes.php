<?php

/**
 * @file    EliminarEstudiantes.php
 *
 * @author  Valery D. Ortuno P <valerydariana98@gmail.com>
 *
 * @created 2026-11-28
 *
 * @description
 * Componente Livewire de "Cursos > Eliminar estudiantes" (HU 34), con el
 * formato de "Eliminar curso": tabla paginada (8 por página) de los cursos
 * activos del docente que tienen estudiantes cargados, con un buscador y la
 * acción "Eliminar estudiantes". Si el curso ya tomó algún examen o tiene
 * ingresos o incidencias, el aviso sale dentro de su fila y no se abre el
 * modal; si no, el modal de confirmación quita la lista con
 * EliminarEstudiantesCursoService y muestra el modal de éxito.
 *
 * @see  App\Services\Curso\ListarCursosEliminarEstudiantesService
 * @see  App\Services\Curso\EliminarEstudiantesCursoService
 */

namespace App\Livewire\Cursos;

use App\Models\Curso;
use App\Models\Usuario;
use App\Services\Curso\EliminarEstudiantesCursoService;
use App\Services\Curso\ListarCursosEliminarEstudiantesService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Throwable;

class EliminarEstudiantes extends Component
{
    /** Cursos por página. */
    public const POR_PAGINA = 8;

    public string $busqueda = '';

    public int $pagina = 1;

    /** Fila con el aviso de que no se puede eliminar, y su mensaje. */
    public ?int $avisoId = null;

    public string $avisoMensaje = '';

    public bool $modalAbierto = false;

    public ?int $cursoId = null;

    public string $cursoNombre = '';

    public int $cursoEstudiantes = 0;

    public bool $eliminando = false;

    public string $errorEliminar = '';

    public bool $mostrarExito = false;

    public string $mensajeExito = '';

    /** @var list<array<string, mixed>>|null Cursos del docente, una vez por request. */
    private ?array $cursosCache = null;

    public function updated(string $propiedad): void
    {
        if ($propiedad === 'busqueda') {
            $this->pagina = 1;
            $this->avisoId = null;
        }
    }

    public function irAPagina(int $pagina): void
    {
        $this->pagina = max(1, $pagina);
        $this->avisoId = null;
    }

    public function abrirModal(int $idCurso): void
    {
        $fila = $this->filtrados()->firstWhere('id', $idCurso);

        if ($fila === null) {
            return;
        }

        // Con examenes tomados o ingresos no se abre el modal: aviso en la fila.
        if (! $fila['eliminable']) {
            $this->avisoId = $idCurso;
            $this->avisoMensaje = (string) $fila['motivo'];

            return;
        }

        $this->avisoId = null;
        $this->cursoId = $idCurso;
        $this->cursoNombre = (string) $fila['nombre'];
        $this->cursoEstudiantes = (int) $fila['estudiantes'];
        $this->errorEliminar = '';
        $this->modalAbierto = true;
    }

    public function cerrarModal(): void
    {
        if ($this->eliminando) {
            return;
        }

        $this->modalAbierto = false;
        $this->cursoId = null;
        $this->cursoNombre = '';
        $this->cursoEstudiantes = 0;
        $this->errorEliminar = '';
    }

    public function eliminar(): void
    {
        if ($this->eliminando || $this->cursoId === null) {
            return;
        }

        $this->eliminando = true;
        $this->errorEliminar = '';

        $docente = auth()->user();
        abort_unless($docente instanceof Usuario, 401);

        try {
            $curso = Curso::query()->findOrFail($this->cursoId);
            app(EliminarEstudiantesCursoService::class)->ejecutar($docente, $curso);

            $this->modalAbierto = false;
            $this->cursoId = null;
            $this->cursoNombre = '';
            $this->cursoEstudiantes = 0;
            $this->cursosCache = null;
            $this->mensajeExito = EliminarEstudiantesCursoService::MENSAJE_EXITO;
            $this->mostrarExito = true;
        } catch (ValidationException $e) {
            $this->errorEliminar = $e->errors()['curso'][0] ?? 'No se pudo eliminar la lista de estudiantes.';
        } catch (AuthorizationException) {
            $this->errorEliminar = 'No tienes permiso para eliminar la lista de este curso.';
        } catch (Throwable $e) {
            report($e);
            $this->errorEliminar = 'No se pudo eliminar la lista de estudiantes.';
        } finally {
            $this->eliminando = false;
        }
    }

    public function cerrarAviso(): void
    {
        $this->mostrarExito = false;
        $this->mensajeExito = '';
    }

    /**
     * Cursos del docente que coinciden con la búsqueda (curso o gestión).
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function filtrados(): Collection
    {
        $filas = collect($this->cursos());
        $termino = mb_strtolower(trim($this->busqueda));

        if ($termino !== '') {
            $filas = $filas->filter(fn (array $fila): bool => mb_strpos(
                mb_strtolower($fila['nombre'].' '.($fila['gestion'] ?? '')),
                $termino,
            ) !== false);
        }

        return $filas->values();
    }

    /** @return list<array<string, mixed>> */
    private function cursos(): array
    {
        if ($this->cursosCache === null) {
            $docente = auth()->user();
            abort_unless($docente instanceof Usuario, 401);

            $this->cursosCache = app(ListarCursosEliminarEstudiantesService::class)->ejecutar((int) $docente->id_usuario);
        }

        return $this->cursosCache;
    }

    public function render(): View
    {
        $filtrados = $this->filtrados();
        $total = $filtrados->count();
        $totalPaginas = max(1, (int) ceil($total / self::POR_PAGINA));
        $this->pagina = min(max(1, $this->pagina), $totalPaginas);

        return view('livewire.cursos.eliminar-estudiantes', [
            'cursos' => $filtrados->slice(($this->pagina - 1) * self::POR_PAGINA, self::POR_PAGINA)->values(),
            'hayCursos' => $this->cursos() !== [],
            'total' => $total,
            'totalPaginas' => $totalPaginas,
        ])->extends('layouts.app');
    }
}
