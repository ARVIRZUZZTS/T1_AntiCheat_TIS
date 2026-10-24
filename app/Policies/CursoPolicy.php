<?php

/**
 * @file    CursoPolicy.php
 *
 * @author  Valery D. Ortuno P <valerydariana98@gmail.com>
 *
 * @created 2026-10-24
 *
 * @description
 * Autoriza la gestión de un curso (ver su detalle, sus exámenes y crear
 * exámenes en él) solo al docente responsable del curso (`curso.sis_doc`).
 *
 * @changelog
 * - 2026-10-24  [Valery D. Ortuno P]  feat: creación inicial de la policy.
 *
 * @see  App\Http\Controllers\CursoController
 * @see  App\Http\Controllers\ExamenController
 */

namespace App\Policies;

use App\Models\Curso;
use App\Models\Usuario;

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
        return (int) $curso->sis_doc === (int) $usuario->id_usuario;
    }
}
