<?php

/**
 * @file    CursoPolicy.php
 *
 * @author  Valery D. Ortuno P <valerydariana98@gmail.com>
 *
 * @created 2026-10-24
 *
 * @description
 * Autoriza la gestión de un curso (ver su detalle y sus exámenes) solo al
 * docente responsable del curso (`curso.sis_doc`), y la creación de exámenes
 * solo si además el curso sigue en curso.
 *
 * @changelog
 * - 2026-10-24  [Valery D. Ortuno P]  feat: creación inicial de la policy.
 * - 2026-10-24  [Valery D. Ortuno P]  feat: crearExamen() impide crear
 *   exámenes en un curso finalizado (HU 11).
 *
 * @see  App\Http\Controllers\CursoController
 * @see  App\Http\Controllers\ExamenController
 */

namespace App\Policies;

use App\Enums\CursoEstado;
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
     *
     * @return bool Si el curso pertenece al usuario.
     *
     * @author Valery D. Ortuno P <valerydariana98@gmail.com>
     * @since  2026-10-24
     */
    public function gestionar(Usuario $usuario, Curso $curso): bool
    {
        if ((int) $curso->sis_doc === (int) $usuario->id_usuario) {
            return true;
        }

        return DB::table('configuracion_examen_docente as ced')
            ->join('examen_curso as ec', 'ec.id_examen', '=', 'ced.id_examen')
            ->where('ced.id_docente', $usuario->id_usuario)
            ->where('ec.id_curso', $curso->id_curso)
            ->exists();
    }

    /**
     * Indica si el usuario puede crear exámenes en el curso: tiene que ser su
     * docente y el curso tiene que seguir en curso. La sección Exámenes solo
     * lista cursos activos, pero esto cierra también el acceso por URL a un
     * curso finalizado.
     *
     * @param  Usuario  $usuario  Usuario autenticado.
     * @param  Curso  $curso  Curso en el que se quiere crear el examen.
     *
     * @return bool Si puede crear exámenes en el curso.
     *
     * @author Valery D. Ortuno P <valerydariana98@gmail.com>
     * @since  2026-10-24
     */
    public function crearExamen(Usuario $usuario, Curso $curso): bool
    {
        return $this->gestionar($usuario, $curso)
            && $curso->estado === CursoEstado::EnCurso->value;
    }
}
