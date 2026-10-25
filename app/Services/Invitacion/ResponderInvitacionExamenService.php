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
 * Respuesta del docente invitado a una invitación de examen compartido. Aceptar
 * la deja en "Aceptada" (el examen pasa a estar compartido con ese docente) y
 * rechazar en "Rechazada"; en ambos casos se avisa al docente que invitó.
 *
 * La respuesta solo procede si la invitación sigue "Pendiente": la fila se toma
 * con lockForUpdate y se vuelve a verificar el estado dentro de la transacción,
 * de modo que un doble envío o dos respuestas simultáneas no la cambien dos
 * veces.
 */
class ResponderInvitacionExamenService
{
    public const MENSAJE_NO_PENDIENTE = 'Esta invitación ya no se puede responder.';

    public function __construct(
        private readonly NotificacionService $notificaciones,
    ) {}

    /**
     * @return array<string, mixed>
     *
     * @throws ValidationException
     */
    public function aceptar(Usuario $invitado, InvitacionExamenCompartido $invitacion): array
    {
        return $this->responder($invitado, $invitacion, InvitacionEstado::Aceptada);
    }

    /**
     * @return array<string, mixed>
     *
     * @throws ValidationException
     */
    public function rechazar(Usuario $invitado, InvitacionExamenCompartido $invitacion): array
    {
        return $this->responder($invitado, $invitacion, InvitacionEstado::Rechazada);
    }

    /**
     * @return array<string, mixed>
     *
     * @throws ValidationException
     */
    private function responder(
        Usuario $invitado,
        InvitacionExamenCompartido $invitacion,
        InvitacionEstado $nuevoEstado,
    ): array {
        Gate::forUser($invitado)->authorize('responder', $invitacion);

        $actualizada = DB::transaction(function () use ($invitacion, $nuevoEstado): InvitacionExamenCompartido {
            $bloqueada = InvitacionExamenCompartido::query()
                ->whereKey($invitacion->id_invitacion)
                ->lockForUpdate()
                ->firstOrFail();

            if ($bloqueada->estado !== InvitacionEstado::Pendiente) {
                throw ValidationException::withMessages([
                    'estado' => self::MENSAJE_NO_PENDIENTE,
                ]);
            }

            $bloqueada->estado = $nuevoEstado;
            $bloqueada->save();

            $this->notificaciones->notificarRespuestaInvitacion($bloqueada);

            return $bloqueada;
        });

        $actualizada->load([
            'invitador:id_usuario,nombre_usuario,apellido',
            'invitado:id_usuario,nombre_usuario,apellido',
            'examen.tipoExamen',
            'examen.cursos:id_curso,nombre_curso',
        ]);

        return $this->resumen($actualizada);
    }

    /** @return array<string, mixed> */
    private function resumen(InvitacionExamenCompartido $invitacion): array
    {
        $examen = $invitacion->examen;

        return [
            'id' => (int) $invitacion->id_invitacion,
            'estado' => $invitacion->estado->value,
            'examen' => [
                'id' => (int) $examen->id_examen,
                'tipo' => $examen->tipoExamen?->nombre_tipo_examen,
                'materias' => $examen->cursos->pluck('nombre_curso')->values()->all(),
            ],
            'invitador' => [
                'id' => (int) $invitacion->invitador->id_usuario,
                'nombre' => trim($invitacion->invitador->nombre_usuario.' '.$invitacion->invitador->apellido),
            ],
            'invitado' => [
                'id' => (int) $invitacion->invitado->id_usuario,
                'nombre' => trim($invitacion->invitado->nombre_usuario.' '.$invitacion->invitado->apellido),
            ],
        ];
    }
}
