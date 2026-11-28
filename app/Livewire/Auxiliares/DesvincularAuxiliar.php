<?php

/**
 * @file    DesvincularAuxiliar.php
 * @author  Alisson D. Alvarado <alvaradoalissondalet@gmail.com>
 * @created 2026-11-27
 * @description Busca auxiliares y confirma la desvinculación de cursos o exámenes.
 * @changelog
 * - 2026-11-27  [Alisson D. Alvarado]  feat: buscar auxiliares y retirar sus asignaciones.
 */

namespace App\Livewire\Auxiliares;

use App\Models\Usuario;
use App\Services\Auxiliar\AsignarAuxiliarService;
use App\Services\Auxiliar\DesvincularAuxiliarService;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

/** Mantiene la selección y verifica cada asignación antes de modificarla. */
class DesvincularAuxiliar extends Component
{
    public string $busqueda = '';

    public ?int $auxiliarSeleccionado = null;

    public ?int $idPendiente = null;

    public string $tipoPendiente = '';

    public string $mensaje = '';

    /** Una búsqueda nueva cierra la selección y cualquier confirmación anterior. */
    public function updatedBusqueda(): void
    {
        $this->auxiliarSeleccionado = null;
        $this->cancelar();
        $this->mensaje = '';
    }

    /** Selecciona una cuenta de auxiliar existente. */
    public function seleccionar(int $idAuxiliar): void
    {
        $auxiliar = collect($this->datos())->firstWhere('id', $idAuxiliar);
        if ($auxiliar === null) {
            return;
        }

        $this->auxiliarSeleccionado = $idAuxiliar;
        $this->busqueda = $auxiliar['nombre'];
        $this->cancelar();
        $this->mensaje = '';
    }

    /** Abre la confirmación solamente si la asignación pertenece al docente. */
    public function confirmar(string $tipo, int $id): void
    {
        $this->cancelar();
        $this->mensaje = '';
        if ($this->asignacion($tipo, $id) !== null) {
            $this->tipoPendiente = $tipo;
            $this->idPendiente = $id;
        }
    }

    /** Cierra la confirmación sin modificar asignaciones. */
    public function cancelar(): void
    {
        $this->tipoPendiente = '';
        $this->idPendiente = null;
    }

    /** Retira la asignación confirmada, tras verificar nuevamente su pertenencia. */
    public function desvincular(): void
    {
        $idDocente = $this->idDocente();
        $auxiliar = $this->auxiliarActual();
        $asignacion = $this->asignacion($this->tipoPendiente, $this->idPendiente);

        if ($auxiliar === null || $asignacion === null) {
            $this->cancelar();
            $this->mensaje = 'La asignación ya no está disponible.';

            return;
        }

        try {
            if ($this->tipoPendiente === 'curso') {
                app(DesvincularAuxiliarService::class)->quitarCurso($idDocente, $auxiliar['id'], $asignacion['id']);
            } else {
                app(AsignarAuxiliarService::class)->quitarExamen($idDocente, $auxiliar['id'], $asignacion['id']);
            }
            $this->mensaje = 'Se desvinculó a '.$auxiliar['nombre'].' de '.$asignacion['nombre'].'.';
        } catch (ValidationException $error) {
            $this->mensaje = (string) collect($error->errors())->flatten()->first();
        }

        $this->cancelar();
    }

    /** Presenta coincidencias y las asignaciones del auxiliar elegido. */
    public function render(): View
    {
        $datos = collect($this->datos());
        $termino = mb_strtolower(trim($this->busqueda));
        $coincidencias = $termino === '' ? collect() : $datos->filter(fn (array $fila): bool =>
            str_contains(mb_strtolower($fila['nombre'].' '.$fila['sis']), $termino))->take(10);
        $auxiliar = $datos->firstWhere('id', $this->auxiliarSeleccionado);
        $clave = $this->tipoPendiente === 'curso' ? 'cursos' : 'examenes';
        $pendiente = $auxiliar && $this->idPendiente !== null
            ? collect($auxiliar[$clave])->firstWhere('id', $this->idPendiente) : null;

        return view('livewire.auxiliares.desvincular-auxiliar', compact('coincidencias', 'auxiliar', 'pendiente'))
            ->extends('layouts.app');
    }

    /** Exige el rol docente en todas las peticiones de Livewire. */
    private function idDocente(): int
    {
        $usuario = auth()->user();
        abort_unless($usuario instanceof Usuario && $usuario->esDocente(), 403);

        return (int) $usuario->id_usuario;
    }

    /** Consulta las asignaciones disponibles para el docente autenticado. */
    private function datos(): array
    {
        return app(DesvincularAuxiliarService::class)->listar($this->idDocente());
    }

    /** Recupera el auxiliar seleccionado desde los datos vigentes. */
    private function auxiliarActual(): ?array
    {
        return collect($this->datos())->firstWhere('id', $this->auxiliarSeleccionado);
    }

    /** Resuelve el tipo y el identificador contra las asignaciones vigentes. */
    private function asignacion(string $tipo, ?int $id): ?array
    {
        if (! in_array($tipo, ['curso', 'examen'], true) || $id === null) {
            return null;
        }

        $auxiliar = $this->auxiliarActual();

        return $auxiliar === null ? null : collect($auxiliar[$tipo === 'curso' ? 'cursos' : 'examenes'])
            ->firstWhere('id', $id);
    }
}
