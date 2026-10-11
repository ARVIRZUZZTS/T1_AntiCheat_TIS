<?php

/**
 * @file    NotificacionDocentePolicy.php
 *
 * @author  T1 - Tech One SRL
 *
 * @created 2026-10-11
 *
 * @description
 * Autoriza ocultar una notificación (HU 12 · T2) únicamente al docente dueño
 * del curso al que apunta (`curso.sis_doc`). El docente autenticado nunca se
 * toma del cuerpo HTTP: lo resuelve el controlador desde la sesión.
 *
 * @see  App\Models\NotificacionDocente
 * @see  App\Services\Notificacion\NotificacionService
 *
 * @changelog
 * - 2026-10-11  [T1]  feat: autorizar ocultado por dueño (#HU12).
 */

namespace App\Policies;

use App\Models\NotificacionDocente;
use App\Models\Rol;
use App\Models\Usuario;

class NotificacionDocentePolicy
{
    public function ocultar(Usuario $usuario, NotificacionDocente $notificacion): bool
    {
        if (! $usuario->roles()->where('nombre_rol', Rol::NOMBRE_DOCENTE)->exists()) {
            return false;
        }

        return (int) $notificacion->curso?->sis_doc === (int) $usuario->id_usuario;
    }
}
