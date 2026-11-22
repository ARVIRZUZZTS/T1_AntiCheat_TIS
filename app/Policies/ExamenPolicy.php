<?php

/**
 * @file    ExamenPolicy.php
 *
 * @author  Alex Candia <alex.leonar.candia@gmail.com>
 *
 * @created 2026-10-23
 *
 * @updated 2026-11-20
 *
 * @description Autoriza el reporte de horarios de ingreso de un examen a su
 * docente responsable o a un docente invitado que acepto la invitacion, y la
 * edicion de los datos base del examen solo a su creador.
 *
 * Un examen vinculado a algún curso archivado es estrictamente de solo lectura:
 * las capacidades de escritura/configuración/monitoreo (acciones que modifican
 * estado) quedan bloqueadas, conservando el acceso de lectura según aplique.
 *
 * @changelog
 * - 2026-11-19  [Valery D. Ortuno P]  refactor: se quita `ver`; el permiso del
 *   detalle se revisa dentro de la consulta de ObtenerDetalleExamenService
 *   (ahorra tres consultas a la base remota).
 * - 2026-11-20  [Equipo T1]  feat: bloqueo de escrituras si algún curso
 *   vinculado al examen está archivado (HU #321 · Task #323).
 *
 * @see  App\Services\Reporte\ConsultarReporteHorarioIngresoService
 * @see  App\Http\Controllers\Api\ExamenEdicionController
 */

namespace App\Policies;

use App\Models\Curso;
use App\Models\Examen;
use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Support\Facades\DB;

class ExamenPolicy
{
    public function configurar(Usuario $usuario, Examen $examen): bool
    {
        if ($this->tieneCursoArchivado($examen)) {
            return false;
        }

        if (! $usuario->roles()->where('nombre_rol', Rol::NOMBRE_DOCENTE)->exists()) {
            return false;
        }

        return $examen->configuracionesDocentes()
            ->where('id_docente', $usuario->id_usuario)
            ->exists();
    }

    public function monitorear(Usuario $usuario, Examen $examen): bool
    {
        if ($this->tieneCursoArchivado($examen)) {
            return false;
        }

        if ($usuario->roles()->where('nombre_rol', Rol::NOMBRE_DOCENTE)->exists()) {
            return $this->configurar($usuario, $examen)
                && $examen->cursos()->where('curso.sis_doc', $usuario->id_usuario)->exists();
        }

        if (! $usuario->roles()->where('nombre_rol', Rol::NOMBRE_AUXILIAR)->exists()) {
            return false;
        }

        return DB::table('auxiliar_curso')
            ->join('examen_curso', 'examen_curso.id_curso', '=', 'auxiliar_curso.id_curso')
            ->where('auxiliar_curso.id_auxiliar', $usuario->id_usuario)
            ->where('examen_curso.id_examen', $examen->id_examen)
            ->exists();
    }

    /**
     * Editar los datos base del examen (tipo, fecha, hora, duracion,
     * ambientes, materiales y normas) es solo para quien lo creo.
     */
    public function editar(Usuario $usuario, Examen $examen): bool
    {
        if ($this->tieneCursoArchivado($examen)) {
            return false;
        }

        if (! $usuario->roles()->where('nombre_rol', Rol::NOMBRE_DOCENTE)->exists()) {
            return false;
        }

        return (int) $examen->creador === (int) $usuario->id_usuario;
    }

    public function invitarDocente(Usuario $usuario, Examen $examen): bool
    {
        if ($this->tieneCursoArchivado($examen)) {
            return false;
        }

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

    /**
     * Indica si el examen tiene al menos un curso vinculado en estado archivado.
     * En exámenes compartidos, basta con que uno de los cursos esté archivado
     * para bloquear las operaciones de escritura.
     */
    private function tieneCursoArchivado(Examen $examen): bool
    {
        $examen->loadMissing('cursos');

        return $examen->cursos->contains(fn (Curso $curso): bool => $curso->estaArchivado());
    }
}
