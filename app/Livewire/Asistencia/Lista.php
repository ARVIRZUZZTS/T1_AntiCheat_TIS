<?php

namespace App\Livewire\Asistencia;

use App\Services\Asistencia\ListarAsistenciaDeExamenService;
use Livewire\Attributes\On;
use Livewire\Component;

class Lista extends Component
{
    public int $idExamen;

    public int $ultimoIngreso = 0;

    public function mount(int $idExamen): void
    {
        $this->idExamen = $idExamen;
    }

    #[On('asistencia-registrada')]
    public function estudianteIngreso(array $payload): void
    {
        // Cambiar una propiedad fuerza re-render en Livewire
        $this->ultimoIngreso = time();
    }

    public function render(ListarAsistenciaDeExamenService $servicio)
    {
        return view('livewire.asistencia.lista', [
            'asistencias' => $servicio->ejecutar($this->idExamen),
        ])->layout('layouts.app');
    }
}