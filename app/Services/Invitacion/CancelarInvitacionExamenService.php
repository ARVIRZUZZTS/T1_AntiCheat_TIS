<?php

namespace App\Services\Invitacion;

use App\Enums\InvitacionEstado;
use App\Models\InvitacionExamenCompartido;
use App\Models\Usuario;
use App\Services\Notificacion\NotificacionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/**
 * Cancelación de una invitación de examen compartido por parte del docente que
 * la envió. Solo procede mientras la invitación sigue "Pendiente": si el
 * invitado ya respondió, no hay nada que cancelar.
 *
 * La fila se toma con lockForUpdate y se revalida el estado dentro de la
 * transacción (protección contra doble envío y carrera). Al cancelar, el aviso
 * de la invitación se oculta de la bandeja del invitado para que no la responda.
 */
class CancelarInvitacionExamenService
{
    public const MENSAJE_NO_PENDIENTE = 'Solo se puede cancelar una invitación pendiente.';

    public function __construct(
        private readonly NotificacionService $notificaciones,
    ) {}

    /**
     * @return array<string, mixed>
     *
     * @throws ValidationException
     */
    public function cancelar(Usuario $invitador, InvitacionExamenCompartido $invitacion): array
    {
        Gate::forUser($invitador)->authorize('cancelar', $invitacion);

        $actualizada = DB::transaction(function () use ($invitacion): InvitacionExamenCompartido {
            $bloqueada = InvitacionExamenCompartido::query()
                ->whereKey($invitacion->id_invitacion)
                ->lockForUpdate()
                ->firstOrFail();

            if ($bloqueada->estado !== InvitacionEstado::Pendiente) {
                throw ValidationException::withMessages([
                    'estado' => self::MENSAJE_NO_PENDIENTE,
                ]);
            }

            $bloqueada->estado = InvitacionEstado::Cancelada;
            $bloqueada->save();

            $this->notificaciones->ocultarDeInvitacion($bloqueada->id_invitacion);

            return $bloqueada;
        });

        return [
            'id' => (int) $actualizada->id_invitacion,
            'estado' => $actualizada->estado->value,
        ];
    }
}
