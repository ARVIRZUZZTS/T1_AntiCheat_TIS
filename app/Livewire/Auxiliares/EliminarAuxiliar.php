<?php

/**
 * @file    EliminarAuxiliar.php
 *
 * @author  Diego Tejerina <josediegotejerinamolina@gmail.com>
 *
 * @created 2026-11-27
 *
 * @description
 * Componente Livewire de la subsección "Eliminar auxiliar": lista paginada
 * (8 por página) de los auxiliares que el usuario puede eliminar, con búsqueda
 * dinámica (nombre, correo, SIS o carnet), filtro por curso, filtro por fecha de
 * creación (desde/hasta) y filtro de estado (Todos / Habilitados /
 * Deshabilitados). Cada fila solo tiene el botón "Eliminar", que abre un modal
 * de confirmación y delega la eliminación lógica en EliminarAuxiliarService
 * (policy + transacción + lockForUpdate). Al cambiar cualquier filtro se vuelve
 * a la página 1.
 *
 * @changelog
 * - 2026-11-27  [Diego Tejerina]  feat: creación inicial.
 *
 * @see  App\Services\Auxiliar\ListarAuxiliaresEliminablesService
 * @see  App\Services\Auxiliar\EliminarAuxiliarService
 * @see  App\Policies\UsuarioPolicy
 */

namespace App\Livewire\Auxiliares;

use App\Models\Usuario;
use App\Services\Auxiliar\EliminarAuxiliarService;
use App\Services\Auxiliar\ListarAuxiliaresEliminablesService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Throwable;

class EliminarAuxiliar extends Component
{
    /** Auxiliares por página. */
    public const POR_PAGINA = 8;

    public string $busqueda = '';

    public string $filtroCurso = '';

    /** todos / Habilitado / Deshabilitado. */
    public string $filtroEstado = 'todos';

    public int $pagina = 1;

    public bool $modalAbierto = false;

    public ?int $auxiliarId = null;

    public string $auxiliarNombre = '';

    public bool $eliminando = false;

    public string $errorEliminar = '';

    public bool $mostrarExito = false;

    public string $mensajeExito = '';

    /**
     * Resultado del servicio cacheado por request (auxiliares del viewer +
     * cursos para el filtro). No es público: se recalcula en cada request.
     *
     * @var array{auxiliares: list<array<string, mixed>>, cursos: list<string>}|null
     */
    private ?array $datosCache = null;

    /** Cualquier cambio de filtro vuelve a la primera página. */
    public function updated(string $propiedad): void
    {
        if (in_array($propiedad, ['busqueda', 'filtroCurso', 'filtroEstado'], true)) {
            $this->pagina = 1;
        }
    }

    public function filtrarEstado(string $estado): void
    {
        $this->filtroEstado = in_array($estado, ['Habilitado', 'Deshabilitado'], true) ? $estado : 'todos';
        $this->pagina = 1;
    }

    public function irAPagina(int $pagina): void
    {
        $this->pagina = max(1, $pagina);
    }

    public function abrirModal(int $idAuxiliar): void
    {
        $fila = $this->filtradas()->firstWhere('id', $idAuxiliar);

        if ($fila === null) {
            return;
        }

        $this->auxiliarId = $idAuxiliar;
        $this->auxiliarNombre = (string) $fila['nombreCompleto'];
        $this->errorEliminar = '';
        $this->modalAbierto = true;
    }

    public function cerrarModal(): void
    {
        if ($this->eliminando) {
            return;
        }

        $this->modalAbierto = false;
        $this->auxiliarId = null;
        $this->auxiliarNombre = '';
        $this->errorEliminar = '';
    }

    public function eliminar(): void
    {
        if ($this->eliminando || $this->auxiliarId === null) {
            return;
        }

        $this->eliminando = true;
        $this->errorEliminar = '';

        $actor = auth()->user();
        abort_unless($actor instanceof Usuario, 401);

        try {
            $auxiliar = Usuario::query()->findOrFail($this->auxiliarId);
            app(EliminarAuxiliarService::class)->ejecutar($actor, $auxiliar);

            $this->modalAbierto = false;
            $this->auxiliarId = null;
            $this->auxiliarNombre = '';
            $this->mensajeExito = EliminarAuxiliarService::MENSAJE_EXITO;
            $this->mostrarExito = true;
        } catch (ValidationException $e) {
            $this->errorEliminar = $e->errors()['auxiliar'][0] ?? 'No se pudo eliminar el auxiliar.';
        } catch (AuthorizationException) {
            $this->errorEliminar = 'No tienes permiso para eliminar a este auxiliar.';
        } catch (Throwable $e) {
            report($e);
            $this->errorEliminar = 'No se pudo eliminar el auxiliar.';
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
     * Auxiliares del viewer tras aplicar todos los filtros (combinados).
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function filtradas(): Collection
    {
        $filas = collect($this->datos()['auxiliares']);

        $termino = mb_strtolower(trim($this->busqueda));
        if ($termino !== '') {
            $filas = $filas->filter(fn (array $fila): bool => $this->coincide($fila, $termino));
        }

        if ($this->filtroCurso !== '') {
            $filas = $filas->filter(fn (array $fila): bool => in_array($this->filtroCurso, $fila['cursos'], true));
        }

        if ($this->filtroEstado !== 'todos') {
            $filas = $filas->filter(fn (array $fila): bool => $fila['estado'] === $this->filtroEstado);
        }

        return $filas->values();
    }

    /**
     * Resultado del servicio para el viewer, calculado una sola vez por request.
     *
     * @return array{auxiliares: list<array<string, mixed>>, cursos: list<string>}
     */
    private function datos(): array
    {
        if ($this->datosCache === null) {
            $actor = auth()->user();
            abort_unless($actor instanceof Usuario, 401);

            $this->datosCache = app(ListarAuxiliaresEliminablesService::class)->ejecutar($actor);
        }

        return $this->datosCache;
    }

    /** @param array<string, mixed> $fila */
    private function coincide(array $fila, string $termino): bool
    {
        foreach (['nombreCompleto', 'correo', 'documento'] as $campo) {
            if (mb_strpos(mb_strtolower((string) $fila[$campo]), $termino) !== false) {
                return true;
            }
        }

        return false;
    }

    public function render(): View
    {
        $cursos = $this->datos()['cursos'];

        $filtradas = $this->filtradas();
        $total = $filtradas->count();
        $totalPaginas = max(1, (int) ceil($total / self::POR_PAGINA));
        $this->pagina = min(max(1, $this->pagina), $totalPaginas);

        $auxiliares = $filtradas
            ->slice(($this->pagina - 1) * self::POR_PAGINA, self::POR_PAGINA)
            ->values();

        return view('livewire.auxiliares.eliminar-auxiliar', [
            'auxiliares' => $auxiliares,
            'cursos' => $cursos,
            'total' => $total,
            'totalPaginas' => $totalPaginas,
        ])->extends('layouts.app');
    }
}
