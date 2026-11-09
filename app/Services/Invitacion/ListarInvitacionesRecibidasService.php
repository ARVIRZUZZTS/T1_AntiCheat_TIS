<?php

/**
 * @file    ListarInvitacionesRecibidasService.php
 * @author  David E. Chavez T. <virzuzz12345@gmail.com>
 * @created 2026-11-08
 * @updated 2026-11-08
 *
 * @description
 * Servicio de dominio que lista las invitaciones a exámenes recibidas por un
 * docente, filtrando por estado pendiente para la vista de recepción.
 *
 * @changelog
 * - 2026-11-08  [David E. Chavez T.]  feat: creación inicial del servicio.
 *
 * @see  App\Models\InvitacionExamenCompartido
 */

namespace App\Services\Invitacion;

use App\Models\InvitacionExamenCompartido;
use App\Models\Usuario;
use Illuminate\Support\Collection;

class ListarInvitacionesRecibidasService
{
    /**
     * Invitaciones pendientes recibidas por el docente.
     *
     * @param  Usuario  $docente  Docente autenticado.
     * @return Collection<int, array{id: int, estado: string, enviada_en: string|null, examen: array{id: int, nombre: string}, invitador: array{id: int, nombre: string}}>
     *
     * @author David E. Chavez T. <virzuzz12345@gmail.com>
     * @since  2026-11-08
     */
    public function ejecutar(Usuario $docente): Collection
    {
        return InvitacionExamenCompartido::query()
            ->where('id_docente_invitado', $docente->id_usuario)
            ->where('estado', 'pendiente')
            ->with(['examen:id_examen,nombre_examen', 'invitador:id_usuario,nombre_usuario,apellido'])
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (InvitacionExamenCompartido $invitacion): array => [
                'id' => (int) $invitacion->id_invitacion,
                'estado' => $invitacion->estado->value,
                'enviada_en' => $invitacion->created_at?->toISOString(),
                'examen' => [
                    'id' => (int) $invitacion->examen->id_examen,
                    'nombre' => (string) $invitacion->examen->nombre_examen,
                ],
                'invitador' => [
                    'id' => (int) $invitacion->invitador->id_usuario,
                    'nombre' => trim($invitacion->invitador->nombre_usuario.' '.$invitacion->invitador->apellido),
                ],
            ]);
    }

    /**
     * Cantidad de invitaciones pendientes recibidas.
     *
     * @param  Usuario  $docente  Docente autenticado.
     * @return int
     *
     * @author David E. Chavez T. <virzuzz12345@gmail.com>
     * @since  2026-11-08
     */
    public function contarPendientes(Usuario $docente): int
    {
        return InvitacionExamenCompartido::query()
            ->where('id_docente_invitado', $docente->id_usuario)
            ->where('estado', 'pendiente')
            ->count();
    }
}
