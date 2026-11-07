<?php

namespace App\Livewire\Auxiliares;

use Illuminate\Contracts\View\View;
use Livewire\Component;
use Throwable;

class ListaAuxiliares extends Component
{
    /**
     * @var list<array{id: int, nombre: string, apellido: string, codigoSis: string, curso: string, estado: string}>
     */
    public array $auxiliares = [];

    public bool $cargando = true;

    public string $mensajeError = '';

    public function cargar(): void
    {
        $this->cargando = true;
        $this->mensajeError = '';

        try {
            $this->auxiliares = app(FuenteAuxiliares::class)->obtener();
        } catch (Throwable $error) {
            if ($error->getCode() === 401) {
                $this->redirectRoute('login');

                return;
            }

            $this->auxiliares = [];
            $this->mensajeError = $error->getCode() === 403
                ? 'No tienes permiso para ver la lista de auxiliares.'
                : 'No se pudieron cargar los auxiliares.';
        } finally {
            $this->cargando = false;
        }
    }

    public function render(): View
    {
        return view('livewire.auxiliares.lista-auxiliares');
    }
}
