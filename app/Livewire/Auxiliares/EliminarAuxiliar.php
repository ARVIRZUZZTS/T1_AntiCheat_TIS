<?php

/**
 * @file    EliminarAuxiliar.php
 *
 * @author  Diego Tejerina <josediegotejerinamolina@gmail.com>
 *
 * @created 2026-11-27
 *
 * @updated 2026-11-28
 *
 * @description
 * Componente Livewire de la subsección "Eliminar auxiliar", con el formato de
 * "Eliminar examen": tabla paginada (8 por página) de los auxiliares del
 * usuario con lo que tienen asociado y la acción "Eliminar", más una búsqueda
 * dinámica (nombre, correo, SIS o carnet, o curso). Al presionar "Eliminar":
 * si el auxiliar tiene algo asociado (cursos activos, cursos o exámenes de
 * antes) se muestra el aviso dentro de su fila y no se abre el modal; si no,
 * se abre el modal de confirmación y la eliminación lógica la hace
 * EliminarAuxiliarService (policy + guardas + transacción + lockForUpdate). Al
 * terminar se muestra el modal "Auxiliar eliminado con exito".
 *
 * @changelog
 * - 2026-11-27  [Diego Tejerina]  feat: creación inicial.
 * - 2026-11-28  [Valery D. Ortuno P]  refactor: formato de Eliminar examen; solo
 *   se elimina al auxiliar sin nada asociado y, si no, el aviso sale en su fila;
 *   se quitan los filtros de materia y estado (la búsqueda incluye los cursos).
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

    /** Fila con el aviso de que no se puede eliminar, y su mensaje. */
    public ?int $avisoId = null;

    public string $avisoMensaje = '';

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
     * @var array{auxiliares: list<array<string, mixed>>}|null
     */
    private ?array $datosCache = null;

    /** Cualquier cambio de filtro vuelve a la primera página. */
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

    public function abrirModal(int $idAuxiliar): void
    {
        $fila = $this->filtradas()->firstWhere('id', $idAuxiliar);

        if ($fila === null) {
            return;
        }

        // Con algo asociado no se abre el modal: el aviso sale en su fila.
        if (! $fila['eliminable']) {
            $this->avisoId = $idAuxiliar;
            $this->avisoMensaje = (string) $fila['motivo'];

            return;
        }

        $this->avisoId = null;
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

        return $filas->values();
    }

    /**
     * Resultado del servicio para el viewer, calculado una sola vez por request.
     *
     * @return array{auxiliares: list<array<string, mixed>>}
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
        $texto = mb_strtolower(implode(' ', [
            $fila['nombreCompleto'], $fila['correo'], $fila['documento'],
            ...$fila['cursosActivos'], ...$fila['cursosPasados'],
        ]));

        return mb_strpos($texto, $termino) !== false;
    }

    public function render(): View
    {
        $filtradas = $this->filtradas();
        $total = $filtradas->count();
        $totalPaginas = max(1, (int) ceil($total / self::POR_PAGINA));
        $this->pagina = min(max(1, $this->pagina), $totalPaginas);

        $auxiliares = $filtradas
            ->slice(($this->pagina - 1) * self::POR_PAGINA, self::POR_PAGINA)
            ->values();

        return view('livewire.auxiliares.eliminar-auxiliar', [
            'auxiliares' => $auxiliares,
            'hayAuxiliares' => $this->datos()['auxiliares'] !== [],
            'total' => $total,
            'totalPaginas' => $totalPaginas,
        ])->extends('layouts.app');
    }
}
