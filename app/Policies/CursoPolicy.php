<?php

/**
 * @file    CursoPolicy.php
 *
 * @author  Valery D. Ortuno P <valerydariana98@gmail.com>
 *
 * @created 2026-10-24
 *
 * @updated 2026-11-20
 *
 * @description
 * Autoriza la gestión de un curso (ver su detalle y sus exámenes) solo al
 * docente responsable del curso (`curso.sis_doc`), y la creación de exámenes
 * solo si además el curso sigue en curso. La eliminación queda reservada al
 * docente que creó el curso. Los cursos archivados son estrictamente de solo
 * lectura: ninguna capacidad de escritura aplica sobre ellos.
 *
 * @changelog
 * - 2026-10-24  [Valery D. Ortuno P]  feat: creación inicial de la policy.
 * - 2026-10-24  [Valery D. Ortuno P]  feat: crearExamen() impide crear
 *   exámenes en un curso finalizado (HU 11).
 * - 2026-11-16  [Alex Candia]  feat: eliminar() reserva el borrado al docente
 *   dueño del curso, sin incluir a los invitados.
 * - 2026-11-20  [Equipo T1]  feat: archivar() y bloqueo de escrituras para
 *   cursos archivados (HU #321 · Task #323).
 * - 2026-11-20  [Equipo T1]  feat: restaurar() permite volver a `EnCurso` un
 *   curso archivado del docente propietario (HU #322 · Task #325).
 *
 * @see  App\Http\Controllers\CursoController
 * @see  App\Http\Controllers\ExamenController
 */

namespace App\Policies;

use App\Enums\CursoEstado;
use App\Enums\InvitacionEstado;
use App\Models\Curso;
use App\Models\Usuario;
use Illuminate\Support\Facades\DB;

class CursoPolicy
{
    /**
     * Indica si el usuario es el docente responsable del curso.
     *
     * El rol docente ya lo exige el middleware `docente` de las rutas; acá
     * solo se decide la pertenencia, para que un docente no abra por URL el
     * curso de otro.
     *
     * @param  Usuario  $usuario  Usuario autenticado.
     * @param  Curso  $curso  Curso que se quiere gestionar.
     * @return bool Si el curso pertenece al usuario.
     *
     * @author Valery D. Ortuno P <valerydariana98@gmail.com>
     *
     * @since  2026-10-24
     */
    public function gestionar(Usuario $usuario, Curso $curso): bool
    {
        if ((int) $curso->sis_doc === (int) $usuario->id_usuario) {
            return true;
        }

        return DB::table('invitacion_examen_compartido as iec')
            ->join('examen_curso as ec', 'ec.id_examen', '=', 'iec.id_examen')
            ->where('iec.id_docente_invitado', $usuario->id_usuario)
            ->where('iec.estado', InvitacionEstado::Aceptada->value)
            ->where('ec.id_curso', $curso->id_curso)
            ->exists();
    }

    /**
     * Indica si el usuario puede eliminar el curso. Solo el docente que lo
     * creó (`curso.sis_doc`); un docente invitado puede gestionar exámenes,
     * pero no borrar el curso de otro. Los cursos archivados son de solo
     * lectura y no pueden eliminarse.
     *
     * @param  Usuario  $usuario  Usuario autenticado.
     * @param  Curso  $curso  Curso que se quiere eliminar.
     * @return bool Si el curso pertenece al usuario.
     *
     * @author Alex Candia
     *
     * @since  2026-11-16
     */
    public function eliminar(Usuario $usuario, Curso $curso): bool
    {
        return $this->esPropietario($usuario, $curso) && ! $curso->estaArchivado();
    }

    /**
     * Indica si el usuario puede archivar el curso. Solo el docente propietario
     * (`curso.sis_doc`) y únicamente cuando el curso está en estado `EnCurso`.
     *
     * @param  Usuario  $usuario  Usuario autenticado.
     * @param  Curso  $curso  Curso que se quiere archivar.
     * @return bool Si puede archivar el curso.
     *
     * @author Equipo T1
     *
     * @since  2026-11-20
     */
    public function archivar(Usuario $usuario, Curso $curso): bool
    {
        return $this->esPropietario($usuario, $curso)
            && $curso->estado === CursoEstado::EnCurso->value;
    }

    /**
     * Indica si el usuario puede restaurar el curso. Solo el docente propietario
     * (`curso.sis_doc`) y únicamente cuando el curso está en estado `Archivado`.
     *
     * @param  Usuario  $usuario  Usuario autenticado.
     * @param  Curso  $curso  Curso que se quiere restaurar.
     * @return bool Si puede restaurar el curso.
     *
     * @author Equipo T1
     *
     * @since  2026-11-20
     */
    public function restaurar(Usuario $usuario, Curso $curso): bool
    {
        return $this->esPropietario($usuario, $curso)
            && $curso->estado === CursoEstado::Archivado->value;
    }

    /**
     * Indica si el usuario puede crear exámenes en el curso: tiene que ser su
     * docente y el curso tiene que seguir en curso. La sección Exámenes solo
     * lista cursos activos, pero esto cierra también el acceso por URL a un
     * curso finalizado o archivado.
     *
     * @param  Usuario  $usuario  Usuario autenticado.
     * @param  Curso  $curso  Curso en el que se quiere crear el examen.
     * @return bool Si puede crear exámenes en el curso.
     *
     * @author Valery D. Ortuno P <valerydariana98@gmail.com>
     *
     * @since  2026-10-24
     */
    public function crearExamen(Usuario $usuario, Curso $curso): bool
    {
        return $this->gestionar($usuario, $curso)
            && $curso->estado === CursoEstado::EnCurso->value;
    }

    /**
     * Indica si el usuario puede realizar operaciones de escritura sobre el
     * curso (habilitar/deshabilitar estudiantes, cargar CSV, etc.). Requiere
     * poder gestionarlo y que el curso no esté archivado.
     */
    public function modificar(Usuario $usuario, Curso $curso): bool
    {
        return $this->gestionar($usuario, $curso) && ! $curso->estaArchivado();
    }

    private function esPropietario(Usuario $usuario, Curso $curso): bool
    {
        return (int) $curso->sis_doc === (int) $usuario->id_usuario;
    }
}
