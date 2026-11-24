<?php

/**
 * @file    ExamenPolicy.php
 *
 * @author  Alex Candia <alex.leonar.candia@gmail.com>
 *
 * @created 2026-10-23
 *
 * @updated 2026-11-20
 * @updated 2026-11-23
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
 * - 2026-11-22  [Valery D. Ortuno P]  feat: `monitorear` deja pasar al auxiliar
 *   con el curso activo o con el examen asignado con Monitor (HU 24).
 * - 2026-11-23  [Valery D. Ortuno P]  fix: `invitarDocente` es solo para el
 *   anfitrion (creador); un invitado que acepto ya no puede invitar a otros.
 * - 2026-11-23  [Valery D. Ortuno P]  perf: `invitarDocente` revisa primero el
 *   creador (sin consultar) y luego rol y curso archivado en una sola consulta.
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
use App\Services\Auxiliar\AccesoAuxiliarService;
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

        // Curso activo asignado o el examen asignado con Monitor (HU 24).
        return app(AccesoAuxiliarService::class)
            ->puede((int) $usuario->id_usuario, (int) $examen->id_examen, AccesoAuxiliarService::MONITOR);
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
        // Solo el anfitrion (quien creo el examen) invita: los invitados que
        // aceptaron comparten el examen pero no pueden sumar a otros. Se sabe
        // sin consultar.
        if ((int) $examen->creador !== (int) $usuario->id_usuario) {
            return false;
        }

        // Docente y sin cursos archivados en el examen, en una sola consulta
        // (la busqueda de docentes lo revisa en cada tecla).
        $fila = DB::selectOne(
            'select exists (select 1 from rol_usuario ru join rol r on r.id_rol = ru.id_rol where ru.id_usuario = ? and r.nombre_rol = ?) as docente, '
            .'exists (select 1 from examen_curso ec join curso c on c.id_curso = ec.id_curso where ec.id_examen = ? and c.estado = ?) as archivado',
            [$usuario->id_usuario, Rol::NOMBRE_DOCENTE, $examen->id_examen, \App\Enums\CursoEstado::Archivado->value],
        );

        return (bool) $fila->docente && ! (bool) $fila->archivado;
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
