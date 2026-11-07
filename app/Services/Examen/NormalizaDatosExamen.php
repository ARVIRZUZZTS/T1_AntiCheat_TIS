<?php

/**
 * @file    NormalizaDatosExamen.php
 *
 * @author  Diego Tejerina <josediegotejerinamolina@gmail.com>
 *
 * @created 2026-11-07
 *
 * @description
 * Comportamiento compartido por el alta y la edicion de un examen: convierte
 * los datos crudos del formulario (fecha dd/mm/aaaa, hora hh:mm, el nombre del
 * tipo y los textos de material/normas a mano) al formato que guarda la base.
 *
 * Vive en un trait, y no duplicado en cada servicio, porque las dos operaciones
 * tienen que normalizar igual: si cambia la regla (por ejemplo el rechazo de
 * fechas imposibles como 31/02) debe cambiar en un solo lugar. Son metodos
 * puros, sin estado, extraidos tal cual de RegistrarExamenService.
 *
 * @see  App\Services\Examen\RegistrarExamenService
 * @see  App\Services\Examen\ActualizarExamenService
 * @see  App\Services\Examen\CatalogosExamenService
 *
 * @changelog
 * - 2026-11-07  [Diego Tejerina]  refactor: se extrae de RegistrarExamenService
 *   la normalizacion de fecha, hora, tipo y lineas personalizadas, para que la
 *   edicion de examen (Task #261) la reutilice sin copiarla.
 */

namespace App\Services\Examen;

use App\Models\TipoExamen as TipoExamenCatalogo;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;

trait NormalizaDatosExamen
{
    /**
     * Fecha en ISO a partir del dd/mm/aaaa del formulario.
     *
     * La conversion va aca y no en el request a proposito: si se hiciera en el
     * request, el input viejo que vuelve al formulario seria el ISO y el campo
     * (que es texto con el separador puesto a mano) lo mostraria revuelto.
     *
     * Y es obligatoria: Postgres NO rechaza '05/12/2026', lo lee como mes/dia y
     * guarda el 12 de mayo. Sin esto la fecha se corrompe en silencio.
     *
     * @throws InvalidArgumentException Si la fecha no viene en dd/mm/aaaa.
     */
    private function fechaIso(string $fecha): string
    {
        $momento = Carbon::hasFormat($fecha, 'd/m/Y')
            ? Carbon::createFromFormat('d/m/Y', $fecha)
            : null;

        // El round-trip es lo que descarta las fechas que estan bien formadas pero
        // no existen: hasFormat acepta 31/02/2026 y createFromFormat la correria a
        // marzo. El request ya rechaza esas, pero aca no se da por hecho que
        // alguien mas vaya a pasar por esta capa.
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
     * Id del tipo de examen en el catalogo.
     *
     * El formulario manda el nombre del tipo ('examen parcial', 'examen final'...)
     * porque es lo que se muestra, y la columna guarda el id. Que el nombre sea
     * valido lo controla el request; esto cubre el otro caso: que este en el enum
     * pero no tenga fila en `tipo_examen`.
     *
     * El id se cachea como los demas catalogos del alta
     * (CatalogosExamenService::CACHE_MINUTOS): la aplicacion no modifica
     * `tipo_examen`. Un tipo que no existe no se cachea.
     *
     * @throws InvalidArgumentException Si el catalogo no tiene ese tipo.
     */
    private function idTipoExamen(string $codigo): int
    {
        $id = Cache::remember(
            CatalogosExamenService::CACHE_PREFIJO.'tipo.'.$codigo,
            now()->addMinutes(CatalogosExamenService::CACHE_MINUTOS),
            fn (): mixed => TipoExamenCatalogo::query()
                ->where('nombre_tipo_examen', $codigo)
                ->value('id_tipo_examen'),
        );

        if ($id === null) {
            throw new InvalidArgumentException("El tipo de examen {$codigo} no está en el catálogo.");
        }

        return (int) $id;
    }

    /**
     * Convierte el texto del formulario en las filas de las tablas de lo
     * personalizado: una por renglon, numeradas desde 1, que es como ya vienen
     * los datos que hay cargados.
     *
     * @param  ?string  $texto  Texto del textarea (puede ser null o estar vacio).
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
