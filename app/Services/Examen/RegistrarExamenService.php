<?php

/**
 * @file    RegistrarExamenService.php
 *
 * @author  Alex Candia <alex.leonar.candia@gmail.com>
 *
 * @created 2026-10-05
 *
 * @updated 2026-10-10
 *
 * @description
 * Servicio de la feature Examen que da de alta un examen con todo lo que se
 * elige en el modal: tipo, fecha, hora de inicio, duración, ambientes, material y
 * normas del catálogo, y el material y las normas escritas a mano.
 *
 * Tres cosas del esquema obligan a resolverlas acá:
 *
 *  1. `examen.id_examen` es un entero sin secuencia ni default, así que el id
 *     se calcula como max(id)+1 dentro de la transacción, con la tabla bloqueada
 *     para que dos altas simultáneas no se queden con el mismo.
 *  2. La hora de fin no se guarda: es un dato derivado (hora de inicio más
 *     duración) que calcula el propio modelo (`Examen::getHoraFinAttribute()`),
 *     para que no pueda quedar desincronizada con la duración.
 *  3. `material_personalizado` y `norma_personalizada` guardan una fila por
 *     renglón escrito, numerada desde 1.
 *
 * @see  App\Http\Requests\StoreExamenRequest
 * @see  App\Http\Controllers\ExamenController
 * @see  resources/views/components/ui/modal-crear-examen.blade.php
 *
 * @changelog
 * - 2026-10-05  [Alex Candia]  feat: creación inicial del servicio.
 * - 2026-10-10  [Alex Candia]  refactor: deja de calcular y escribir `hora_fin`;
 *   la columna se eliminó y la hora de fin pasa a ser un accesor del modelo.
 */

namespace App\Services\Examen;

use App\Enums\TipoExamen;
use App\Models\Curso;
use App\Models\Examen;
use App\Models\TipoExamen as TipoExamenCatalogo;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class RegistrarExamenService
{
    /**
     * Techo de minutos de la duración. No es una regla del dominio sino un
     * tope contra un número sin sentido: nadie examina 300 minutos (5 horas).
     */
    public const DURACION_MAXIMA = 300;

    /**
     * Crea el examen y todo lo que se le eligió.
     *
     * @param  Curso  $curso  Curso al que queda asociado el examen.
     * @param  array<string, mixed>  $datos  Datos ya validados (validated() del request).
     * @return Examen Examen creado, con las relaciones ya escritas.
     *
     * @throws InvalidArgumentException Si el tipo no existe en el catálogo.
     * @throws RuntimeException Si no se puede determinar el usuario creador.
     */
    public function ejecutar(Curso $curso, array $datos): Examen
    {
        $creador = $this->idCreador($curso);

        return DB::transaction(function () use ($curso, $datos, $creador): Examen {
            $examen = new Examen;
            $examen->id_examen = $this->siguienteIdExamen();
            $examen->fecha = $this->fechaIso($datos['fecha']);
            $examen->hora_inicio = $this->horaIso($datos['hora_inicio']);
            $examen->duracion = (int) $datos['duracion'];
            $examen->creador = $creador;
            $examen->tipo_examen = $this->idTipoExamen((string) $datos['tipo_examen']);
            $examen->save();

            $examen->cursos()->attach($curso->id_curso);
            $examen->ambientes()->sync($datos['ambientes']);
            $examen->normas()->sync($datos['normas'] ?? []);
            $examen->materialesPermitidos()->sync($datos['materiales'] ?? []);

            $this->guardarMaterialesPersonalizados($examen, $datos['materiales_personalizados'] ?? null);
            $this->guardarNormasPersonalizadas($examen, $datos['normas_personalizadas'] ?? null);

            return $examen;
        });
    }

    /**
     * Próximo id de examen.
     *
     * `max()` por sí solo no bloquea: dos altas simultáneas leerían el mismo
     * máximo y el segundo insert pisaría al primero. El bloqueo de tabla en modo
     * SHARE ROW EXCLUSIVE serializa a los escritores y deja pasar a los lectores.
     */
    private function siguienteIdExamen(): int
    {
        DB::statement('LOCK TABLE examen IN SHARE ROW EXCLUSIVE MODE');

        return (int) Examen::query()->max('id_examen') + 1;
    }

    /**
     * Usuario que figura como creador del examen.
     *
     * Todavía no hay login (#29), así que `auth()->id()` viene nulo y se cae al
     * docente del curso: es quien lleva la materia y el que la va a monitorear.
     * Cuando exista la sesión, manda el usuario autenticado.
     */
    private function idCreador(Curso $curso): int
    {
        $id = auth()->id() ?? $curso->docente?->id_usuario;

        if ($id === null) {
            throw new RuntimeException(
                'No se pudo determinar el usuario creador: no hay sesión y el curso no tiene docente.'
            );
        }

        return (int) $id;
    }

    /**
     * Id del tipo de examen en el catálogo.
     *
     * El formulario manda el nombre del tipo ('examen parcial', 'examen final'...)
     * porque es lo que se muestra, y la columna guarda el id. Que el nombre sea
     * válido lo controla el request; esto cubre el otro caso: que esté en el enum
     * pero no tenga fila en `tipo_examen`.
     *
     * @throws InvalidArgumentException Si el catálogo no tiene ese tipo.
     */
    private function idTipoExamen(string $codigo): int
    {
        $id = TipoExamenCatalogo::query()
            ->where('nombre_tipo_examen', $codigo)
            ->value('id_tipo_examen');

        if ($id === null) {
            throw new InvalidArgumentException("El tipo de examen {$codigo} no está en el catálogo.");
        }

        return (int) $id;
    }

    /**
     * Fecha en ISO a partir del dd/mm/aaaa del formulario.
     *
     * La conversión va acá y no en el request a propósito: si se hiciera en el
     * request, el input viejo que vuelve al formulario sería el ISO y el campo
     * (que es texto con el separador puesto a mano) lo mostraría revuelto.
     *
     * Y es obligatoria: Postgres NO rechaza '05/12/2026', lo lee como mes/día y
     * guarda el 12 de mayo. Sin esto la fecha se corrompe en silencio.
     *
     * @throws InvalidArgumentException Si la fecha no viene en dd/mm/aaaa.
     */
    private function fechaIso(string $fecha): string
    {
        $momento = Carbon::hasFormat($fecha, 'd/m/Y')
            ? Carbon::createFromFormat('d/m/Y', $fecha)
            : null;

        // El round-trip es lo que descarta las fechas que están bien formadas pero
        // no existen: hasFormat acepta 31/02/2026 y createFromFormat la correría a
        // marzo. El request ya rechaza esas, pero acá no se da por hecho que
        // alguien más vaya a pasar por esta capa.
        if ($momento === null || $momento->format('d/m/Y') !== $fecha) {
            throw new InvalidArgumentException("La fecha {$fecha} no es una fecha válida en dd/mm/aaaa.");
        }

        return $momento->format('Y-m-d');
    }

    /**
     * Hora en H:i:s a partir del hh:mm del formulario. A diferencia de la fecha,
     * Postgres no cambia el significado de una hora de 24 horas, pero se
     * normaliza igual para que la columna quede siempre con segundos.
     *
     * @throws InvalidArgumentException Si la hora no viene en hh:mm.
     */
    private function horaIso(string $hora): string
    {
        $momento = Carbon::hasFormat($hora, 'H:i')
            ? Carbon::createFromFormat('H:i', $hora)
            : null;

        if ($momento === null || $momento->format('H:i') !== $hora) {
            throw new InvalidArgumentException("La hora {$hora} no está en formato hh:mm.");
        }

        return $momento->format('H:i:s');
    }

    /**
     * Material escrito a mano: una fila por renglón no vacío.
     */
    private function guardarMaterialesPersonalizados(Examen $examen, ?string $texto): void
    {
        foreach ($this->lineas($texto) as $linea) {
            $examen->materialesPersonalizados()->create([
                'numero_material' => $linea['numero'],
                'descripcion_material' => $linea['descripcion'],
            ]);
        }
    }

    /**
     * Normas escritas a mano: una fila por renglón no vacío.
     */
    private function guardarNormasPersonalizadas(Examen $examen, ?string $texto): void
    {
        foreach ($this->lineas($texto) as $linea) {
            $examen->normasPersonalizadas()->create([
                'numero_norma' => $linea['numero'],
                'descripcion_norma' => $linea['descripcion'],
            ]);
        }
    }

    /**
     * Convierte el texto del formulario en las filas de las tablas de lo
     * personalizado: una por renglón, numeradas desde 1, que es como ya vienen
     * los datos que hay cargados.
     *
     * @param  ?string  $texto  Texto del textarea (puede ser null o estar vacío).
     * @return list<array{numero: int, descripcion: string}>
     */
    private function lineas(?string $texto): array
    {
        return collect(preg_split('/\R/u', $texto ?? '') ?: [])
            ->map(fn (string $linea): string => trim($linea))
            ->filter()
            ->values()
            ->map(fn (string $descripcion, int $indice): array => [
                'numero' => $indice + 1,
                'descripcion' => $descripcion,
            ])
            ->all();
    }
}
