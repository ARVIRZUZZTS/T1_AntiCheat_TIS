<?php

namespace App\Livewire\Asistencia;

use App\Services\Asistencia\ListarAsistenciaDeExamenService;
use Livewire\Attributes\On;
use Livewire\Component;

class Lista extends Component
{
    public int $idExamen;

    public function mount(int $idExamen): void
    {
        $this->idExamen = $idExamen;
    }

    #[On('echo:examen.{idExamen},AsistenciaRegistrada')]
    public function estudianteIngreso(array $payload): void
    {
        $this->dispatch('notificar-ingreso', nombre: $payload['nombre']);
    }

    public function render(ListarAsistenciaDeExamenService $servicio)
    {
        return view('livewire.asistencia.lista', [
            'asistencias' => $servicio->ejecutar($this->idExamen),
        ])->layout('layouts.app');
    }
}