<?php

/**
 * @file    UsuarioPolicy.php
 *
 * @author  Diego Tejerina <josediegotejerinamolina@gmail.com>
 *
 * @created 2026-11-27
 *
 * @description
 * Autoriza acciones sobre una cuenta de usuario. Por ahora solo la eliminación
 * lógica de un auxiliar (subsección "Eliminar auxiliar"):
 *
 *  - El objetivo debe ser un auxiliar.
 *  - Nadie puede eliminarse a sí mismo.
 *  - El **docente responsable** (`id_docente_responsable`) puede eliminar a sus
 *    auxiliares.
 *  - El **coordinador** (auxiliar con `puede_crear_auxiliares`) solo puede
 *    eliminar a los auxiliares que él creó (`id_creador`).
 *
 * Laravel descubre la policy por convención de nombre (modelo `Usuario` →
 * `UsuarioPolicy`), igual que `CursoPolicy` y `ExamenPolicy`.
 *
 * @changelog
 * - 2026-11-27  [Diego Tejerina]  feat: creación inicial con eliminar().
 *
 * @see  App\Services\Auxiliar\EliminarAuxiliarService
 */

namespace App\Policies;

use App\Models\Usuario;

class UsuarioPolicy
{
    /**
     * Indica si $actor puede eliminar (lógicamente) la cuenta $auxiliar.
     *
     * @param  Usuario  $actor     Usuario autenticado que intenta eliminar.
     * @param  Usuario  $auxiliar  Cuenta de auxiliar que se quiere eliminar.
     * @return bool  Si la eliminación está permitida.
     *
     * @author Diego Tejerina <josediegotejerinamolina@gmail.com>
     *
     * @since  2026-11-27
     */
    public function eliminar(Usuario $actor, Usuario $auxiliar): bool
    {
        if ((int) $actor->id_usuario === (int) $auxiliar->id_usuario) {
            return false;
        }

        if (! $auxiliar->esAuxiliar()) {
            return false;
        }

        if ($actor->esDocente()) {
            return $auxiliar->id_docente_responsable !== null
                && (int) $auxiliar->id_docente_responsable === (int) $actor->id_usuario;
        }

        if ($actor->esAuxiliar() && (bool) $actor->puede_crear_auxiliares) {
            return $auxiliar->id_creador !== null
                && (int) $auxiliar->id_creador === (int) $actor->id_usuario;
        }

        return false;
    }
}
