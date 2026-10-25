<?php

/**
 * @file    ExamenPolicy.php
 *
 * @author  Alex Candia <alex.leonar.candia@gmail.com>
 *
 * @created 2026-10-23
 *
 * @description Autoriza el reporte de horarios de ingreso de un examen a su
 * docente responsable o a un docente invitado que acepto la invitacion.
 *
 * @see  App\Services\Reporte\ConsultarReporteHorarioIngresoService
 */

namespace App\Policies;

use App\Models\Curso;
use App\Models\Examen;
use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Support\Facades\DB;

class ExamenPolicy
{
    public function invitarDocente(Usuario $usuario, Examen $examen): bool
    {
        $esDocente = $usuario->roles()
            ->where('nombre_rol', Rol::NOMBRE_DOCENTE)
            ->exists();

        if (! $esDocente) {
            return false;
        }

        if ((int) $examen->creador === (int) $usuario->id_usuario) {
            return true;
        }

        // Un docente que aceptó la invitación al examen también puede invitar a
        // otros docentes a compartirlo.
        return DB::table('invitacion_examen_compartido')
            ->where('id_examen', $examen->id_examen)
            ->where('id_docente_invitado', $usuario->id_usuario)
            ->where('estado', 'aceptado')
            ->exists();
    }

    public function reporteHorarioIngreso(Usuario $usuario, Examen $examen, int $idCurso): bool
    {
        $esDocente = $usuario->roles()
            ->where('nombre_rol', Rol::NOMBRE_DOCENTE)
            ->exists();

        if (! $esDocente) {
            return false;
        }

        $cursoPropio = Curso::query()
            ->where('id_curso', $idCurso)
            ->where('sis_doc', $usuario->id_usuario)
            ->whereHas('examenes', fn ($consulta) => $consulta->where('examen.id_examen', $examen->id_examen))
            ->exists();

        if (! $cursoPropio) {
            return false;
        }

        if ((int) $examen->creador === (int) $usuario->id_usuario) {
            return true;
        }

        return DB::table('invitacion_examen_compartido')
            ->where('id_examen', $examen->id_examen)
            ->where('id_docente_invitado', $usuario->id_usuario)
            ->where('estado', 'aceptado')
            ->exists();
    }
}
