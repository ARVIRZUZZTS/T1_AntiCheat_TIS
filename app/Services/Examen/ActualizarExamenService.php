<?php

/**
 * @file    ActualizarExamenService.php
 *
 * @author  Diego Tejerina <josediegotejerinamolina@gmail.com>
 *
 * @created 2026-11-07
 *
 * @description
 * Servicio de la feature Examen que guarda los cambios de un examen que ya
 * existe (Task #261, HU-22 "Editar informacion de un examen"). Es la contraparte
 * de RegistrarExamenService: comparte con el alta la normalizacion de datos
 * (trait NormalizaDatosExamen) y el modelo de las relaciones, pero actualiza en
 * vez de crear.
 *
 * Que SI toca, todo dentro de una transaccion para que un error no deje el
 * examen a medias:
 *
 *  - Los datos base del examen: tipo, fecha, hora de inicio y duracion.
 *  - Las relaciones de catalogo (ambientes, normas, materiales permitidos) con
 *    sync(): agrega las nuevas, saca las que se quitaron y deja las que siguen,
 *    sin duplicar filas en las tablas puente ni borrar los catalogos.
 *  - El material y las normas escritas a mano: se reemplazan por lo que llega
 *    (una fila por renglon), que es lo que el formulario reenvia entero.
 *
 * Que NO toca, a proposito:
 *
 *  - El curso asociado (examen_curso): es de solo lectura en la edicion.
 *  - El creador del examen.
 *  - La inscripcion de estudiantes (estudiante_examen) ni su division por
 *    ambientes (estudiante_examen_ambiente): esa es la operacion aparte "Dividir
 *    examen" y reasignarla aqui alteraria datos ligados a la asistencia. Cambiar
 *    los ambientes del examen no redistribuye estudiantes por si solo.
 *
 * @see  App\Http\Requests\UpdateExamenRequest
 * @see  App\Http\Controllers\Api\ExamenEdicionController::actualizar()
 * @see  App\Services\Examen\RegistrarExamenService
 * @see  App\Services\Examen\NormalizaDatosExamen
 *
 * @changelog
 * - 2026-11-07  [Diego Tejerina]  feat: creacion inicial del servicio.
 */

namespace App\Services\Examen;

use App\Models\Examen;
use Illuminate\Support\Facades\DB;

class ActualizarExamenService
{
    use NormalizaDatosExamen;

    /**
     * Guarda los cambios del examen y de sus relaciones.
     *
     * @param  Examen  $examen  Examen que se edita (ya resuelto por la ruta).
     * @param  array<string, mixed>  $datos  Datos ya validados (validated() del request).
     * @return Examen El examen actualizado, con las relaciones ya escritas.
     *
     * @throws \InvalidArgumentException Si el tipo no existe en el catalogo o la
     *                                   fecha/hora no se puede normalizar.
     */
    public function ejecutar(Examen $examen, array $datos): Examen
    {
        return DB::transaction(function () use ($examen, $datos): Examen {
            $examen->fecha = $this->fechaIso($datos['fecha']);
            $examen->hora_inicio = $this->horaIso($datos['hora_inicio']);
            $examen->duracion = (int) $datos['duracion'];
            $examen->tipo_examen = $this->idTipoExamen((string) $datos['tipo_examen']);
            // El curso (examen_curso) y el creador no se tocan: se conservan.
            $examen->save();

            // sync() y no attach(): el examen ya tenia relaciones, hay que sumar
            // las nuevas y quitar las que se desmarcaron sin duplicar las que
            // siguen. array_unique porque sync no deduplica los ids de entrada.
            $examen->ambientes()->sync(array_unique($datos['ambientes']));
            $examen->normas()->sync(array_unique($datos['normas'] ?? []));
            $examen->materialesPermitidos()->sync(array_unique($datos['materiales'] ?? []));

            $this->reemplazarMaterialesPersonalizados($examen, $datos['materiales_personalizados'] ?? null);
            $this->reemplazarNormasPersonalizadas($examen, $datos['normas_personalizadas'] ?? null);

            return $examen;
        });
    }

    /**
     * Material escrito a mano: se borra lo anterior y se reescribe con lo que
     * llega, una fila por renglon. El formulario reenvia el textarea entero, asi
     * que reemplazar es mas simple y seguro que diferenciar renglon por renglon,
     * y no deja numeros de linea con huecos.
     */
    private function reemplazarMaterialesPersonalizados(Examen $examen, ?string $texto): void
    {
        $examen->materialesPersonalizados()->delete();

        foreach ($this->lineas($texto) as $linea) {
            $examen->materialesPersonalizados()->create([
                'numero_material' => $linea['numero'],
                'descripcion_material' => $linea['descripcion'],
            ]);
        }
    }

    /**
     * Normas escritas a mano: mismo criterio que el material personalizado.
     */
    private function reemplazarNormasPersonalizadas(Examen $examen, ?string $texto): void
    {
        $examen->normasPersonalizadas()->delete();

        foreach ($this->lineas($texto) as $linea) {
            $examen->normasPersonalizadas()->create([
                'numero_norma' => $linea['numero'],
                'descripcion_norma' => $linea['descripcion'],
            ]);
        }
    }
}
