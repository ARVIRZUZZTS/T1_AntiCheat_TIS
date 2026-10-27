<?php

namespace App\Services\Invitacion;

use App\Models\Examen;
use App\Models\InvitacionExamenCompartido;
use App\Models\Usuario;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;

class ListarInvitacionesExamenService
{
    /**
     * @return Collection<int, array{id: int, estado: 'aceptado'|'cancelado'|'pendiente'|'rechazado', enviada_en: string|null, invitado: array{id: int, nombre: string, correo: string|null}}>
     */
    public function ejecutar(Usuario $docente, Examen $examen): Collection
    {
        Gate::forUser($docente)->authorize('invitarDocente', $examen);

        return InvitacionExamenCompartido::query()
            ->where('id_examen', $examen->id_examen)
            ->where('id_docente_invitador', $docente->id_usuario)
            ->with(['invitado:id_usuario,nombre_usuario,apellido,correo'])
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (InvitacionExamenCompartido $invitacion): array => [
                'id' => (int) $invitacion->id_invitacion,
                'estado' => $invitacion->estado->value,
                'enviada_en' => $invitacion->created_at->toISOString(),
                'invitado' => [
                    'id' => (int) $invitacion->invitado->id_usuario,
                    'nombre' => trim($invitacion->invitado->nombre_usuario.' '.$invitacion->invitado->apellido),
                    'correo' => $invitacion->invitado->correo,
                ],
            ]);
    }
}
