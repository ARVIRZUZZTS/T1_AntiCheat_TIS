<?php

namespace App\Enums;

enum InvitacionEstado: string
{
    case Pendiente = 'pendiente';
    case Aceptada = 'aceptado';
    case Rechazada = 'rechazado';
    case Cancelada = 'cancelado';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Pendiente => 'Pendiente',
            self::Aceptada => 'Aceptada',
            self::Rechazada => 'Rechazada',
            self::Cancelada => 'Cancelada',
        };
    }

    public function bloqueaNuevaInvitacion(): bool
    {
        return in_array($this, [self::Pendiente, self::Aceptada], true);
    }
}
