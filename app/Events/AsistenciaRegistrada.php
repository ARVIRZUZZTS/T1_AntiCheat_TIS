<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AsistenciaRegistrada implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $idExamen,
        public string $sisEstudiante,
        public string $nombreCompleto,
        public string $horaIngreso,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel("examen.{$this->idExamen}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'AsistenciaRegistrada';
    }

    public function broadcastWith(): array
    {
        return [
            'sis'      => $this->sisEstudiante,
            'nombre'   => $this->nombreCompleto,
            'hora'     => $this->horaIngreso,
        ];
    }
}