<?php

/**
 * @file    CentralRiesgoPolicy.php
 *
 * @author  Diego Tejerina <josediegotejerinamolina@gmail.com>
 *
 * @created 2026-10-09
 *
 * @updated 2026-10-09
 *
 * @description Autoriza la revisión únicamente al docente del curso del examen.
 *
 * @changelog
 * - 2026-10-09  [Diego Tejerina]  feat: autorizar resolución de incidencias (#142).
 */

namespace App\Policies;

use App\Models\CentralRiesgo;
use App\Models\Curso;
use App\Models\Rol;
use App\Models\Usuario;

class CentralRiesgoPolicy
{
    /**
     * @param  Usuario  $usuario  Docente autenticado, nunca tomado del cuerpo HTTP.
     * @param  CentralRiesgo  $incidencia  Incidencia del examen que se desea revisar.
     * @return bool Si tiene rol docente y es responsable de un curso del examen.
     */
    public function resolver(Usuario $usuario, CentralRiesgo $incidencia): bool
    {
        if (! $usuario->roles()->where('nombre_rol', Rol::NOMBRE_DOCENTE)->exists()) {
            return false;
        }

        // Supabase enlaza el examen directamente; el esquema original usa el ingreso.
        $idExamen = $incidencia->getAttribute('id_examen')
            ?? $incidencia->registroAsistencia?->id_examen;

        if ($idExamen === null) {
            return false;
        }

        return Curso::query()
            ->where('sis_doc', $usuario->id_usuario)
            ->whereHas('examenes', fn ($consulta) => $consulta->where('examen.id_examen', $idExamen))
            ->exists();
    }
}
