<?php

namespace App\Policies;

use App\Models\InvitacionExamenCompartido;
use App\Models\Rol;
use App\Models\Usuario;

/**
 * Autoriza quién puede actuar sobre una invitación a compartir examen: solo el
 * docente invitado puede responderla (aceptar o rechazar) y solo el docente
 * que la envió puede cancelarla. La condición de estado ("Pendiente") la
 * verifica el servicio dentro de la transacción, no la policy.
 */
class InvitacionExamenCompartidoPolicy
{
    public function responder(Usuario $usuario, InvitacionExamenCompartido $invitacion): bool
    {
        return (int) $invitacion->id_docente_invitado === (int) $usuario->id_usuario
            && $this->esDocente($usuario);
    }

    public function cancelar(Usuario $usuario, InvitacionExamenCompartido $invitacion): bool
    {
        return (int) $invitacion->id_docente_invitador === (int) $usuario->id_usuario
            && $this->esDocente($usuario);
    }

    private function esDocente(Usuario $usuario): bool
    {
        return $usuario->roles()->where('nombre_rol', Rol::NOMBRE_DOCENTE)->exists();
    }
}
