<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AsistenciaRegistrada implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $idExamen,
        public string $sisEstudiante,
        public string $nombreCompleto,
        public string $horaIngreso,
        public string $registrador
    ) {
        \Log::info('Evento construido', ['idExamen' => $idExamen]);
    }

    public function broadcastOn(): array
    {
        \Log::info('broadcastOn llamado', ['idExamen' => $this->idExamen]);
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
            'registrador' => $this->registrador,
        ];
    }
}