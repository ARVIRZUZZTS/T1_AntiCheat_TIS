<?php

/**
 * @file    UpdateExamenRequest.php
 *
 * @author  Diego Tejerina <josediegotejerinamolina@gmail.com>
 *
 * @created 2026-11-07
 *
 * @description
 * Validacion del guardado de un examen editado (Task #261, HU-22). Reutiliza
 * tal cual las reglas, mensajes y atributos del alta (StoreExamenRequest): los
 * campos que se editan y sus limites son los mismos, asi que la fuente de
 * verdad de la validacion no se duplica. Solo cambian tres cosas propias de una
 * edicion:
 *
 *  1. La autorizacion: no es "crear examen en el curso" sino "editar este
 *     examen", que ExamenPolicy::editar() concede solo a su creador.
 *  2. La regla de "un solo examen final / una sola segunda instancia por curso"
 *     se evalua sobre los cursos del examen y excluye al propio examen, para
 *     que guardar un final sin cambiarle el tipo no choque consigo mismo.
 *  3. El curso no viaja en la URL (la ruta es /api/examenes/{examen}); se toma
 *     del examen, que conserva su curso original (es de solo lectura).
 *
 * @see  App\Http\Requests\StoreExamenRequest
 * @see  App\Http\Controllers\Api\ExamenEdicionController::actualizar()
 * @see  App\Services\Examen\ActualizarExamenService
 * @see  App\Policies\ExamenPolicy::editar()
 *
 * @changelog
 * - 2026-11-07  [Diego Tejerina]  feat: creacion inicial del request de edicion.
 */

namespace App\Http\Requests;

use App\Models\Examen;

class UpdateExamenRequest extends StoreExamenRequest
{
    /**
     * Editar un examen es solo para su creador (ExamenPolicy::editar). El rol
     * docente ya lo exige el middleware `docente` de la ruta.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('editar', $this->route('examen')) ?? false;
    }

    /**
     * La regla de unicidad del final / segunda instancia se evalua sobre los
     * cursos a los que ya pertenece el examen (su curso es de solo lectura), no
     * sobre un curso de la URL que esta ruta no tiene.
     *
     * @return list<int>
     */
    protected function cursosParaUnicoPorTipo(): array
    {
        $examen = $this->route('examen');

        if (! $examen instanceof Examen) {
            return [];
        }

        return $examen->cursos()
            ->pluck('curso.id_curso')
            ->map(fn (mixed $id): int => (int) $id)
            ->all();
    }

    /**
     * El examen que se edita no cuenta como "ya existe": si no se excluye, un
     * final que se guarda sin cambiar el tipo chocaria consigo mismo.
     */
    protected function examenExcluidoDeUnicoPorTipo(): ?int
    {
        $examen = $this->route('examen');

        return $examen instanceof Examen ? (int) $examen->id_examen : null;
    }
}
