<?php

/**
 * @file    ValidarRegistros.php
 *
 * @author  Valery D. Ortuno P <valerydariana98@gmail.com>
 *
 * @created 2026-11-08
 *
 * @description
 * Pantalla "Docentes" (HU 26): una card por cada docente que se registró y
 * espera validación, con su nombre, correo, código SIS, fecha y hora de la
 * cuenta, "X de 2 verificaciones" y los docentes que ya aprobaron.
 *
 * Aprobar suma una verificación; con la segunda la cuenta queda habilitada y
 * la card desaparece. Desaprobar pide un motivo y deja la cuenta rechazada
 * (también desaparece). Los modales de confirmación viven en Alpine y llaman
 * a aprobar()/desaprobar(); el servidor vuelve a validar todo.
 *
 * @changelog
 * - 2026-11-08  [Valery D. Ortuno P]  feat: creación inicial (HU 26).
 *
 * @see  App\Services\Docente\ListarSolicitudesRegistroService
 * @see  App\Services\Docente\ValidarRegistroDocenteService
 * @see  resources/views/livewire/docentes/validar-registros.blade.php
 */

namespace App\Livewire\Docentes;

use App\Services\Docente\ListarSolicitudesRegistroService;
use App\Services\Docente\ValidarRegistroDocenteService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use Livewire\Attributes\Computed;
use Livewire\Component;

class ValidarRegistros extends Component
{
    /** Aviso del resultado de la última acción (vacío si no hay). */
    public string $mensaje = '';

    /** 'success' o 'danger', el tipo del aviso. */
    public string $tipoMensaje = 'success';

    /**
     * Solicitudes pendientes, de la más antigua a la más reciente.
     *
     * @return Collection<int, array<string, mixed>>
     */
    #[Computed]
    public function solicitudes(): Collection
    {
        return app(ListarSolicitudesRegistroService::class)->ejecutar((int) auth()->id());
    }

    /** Aprueba la solicitud: suma una verificación. */
    public function aprobar(int $idSolicitante): void
    {
        try {
            $resultado = app(ValidarRegistroDocenteService::class)->aprobar($idSolicitante, (int) auth()->id());
        } catch (InvalidArgumentException $error) {
            $this->avisar($error->getMessage(), 'danger');

            return;
        }

        $this->avisar($resultado['habilitada']
            ? 'Registro exitoso: la cuenta de '.$resultado['nombre'].' quedo habilitada.'
            : 'Aprobaste el registro de '.$resultado['nombre'].' ('.$resultado['aprobaciones'].' de '
                .ValidarRegistroDocenteService::APROBACIONES_REQUERIDAS.' verificaciones).');
    }

    /**
     * Desaprueba la solicitud: la cuenta queda rechazada.
     *
     * @return string Error para mostrar en el modal, vacío si se registró.
     */
    public function desaprobar(int $idSolicitante, ?string $motivo = null): string
    {
        try {
            $resultado = app(ValidarRegistroDocenteService::class)->desaprobar($idSolicitante, (int) auth()->id(), $motivo);
        } catch (InvalidArgumentException $error) {
            // Un motivo inválido se corrige en el modal; el resto va al aviso.
            if (in_array($error->getMessage(), [
                ValidarRegistroDocenteService::MENSAJE_MOTIVO_REQUERIDO,
                ValidarRegistroDocenteService::MENSAJE_MOTIVO_LARGO,
            ], true)) {
                $this->skipRender();

                return $error->getMessage();
            }

            $this->avisar($error->getMessage(), 'danger');

            return '';
        }

        $this->avisar('Rechazaste el registro de '.$resultado['nombre'].'.');

        return '';
    }

    public function cerrarMensaje(): void
    {
        $this->mensaje = '';
    }

    public function render(): View
    {
        return view('livewire.docentes.validar-registros');
    }

    private function avisar(string $mensaje, string $tipo = 'success'): void
    {
        $this->mensaje = $mensaje;
        $this->tipoMensaje = $tipo;
        unset($this->solicitudes);

        // El contador del sidebar está fuera del componente: se le avisa.
        $this->dispatch('solicitudes-registro', pendientes: $this->solicitudes->count());
    }
}
