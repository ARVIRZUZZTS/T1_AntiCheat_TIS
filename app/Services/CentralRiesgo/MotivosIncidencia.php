<?php

/**
 * @file    MotivosIncidencia.php
 *
 * @author  Valery D. Ortuno P <valerydariana98@gmail.com>
 *
 * @created 2026-11-07
 *
 * @description
 * Acceso a los motivos de las incidencias (`central_riesgo_motivo`): una
 * incidencia puede tener varios. Concentra en un lugar cómo se guardan y cómo
 * se leen, para que el registro, la central de riesgos y los reportes no
 * repitan la consulta.
 *
 *  - guardar(): una fila por motivo, dentro de la transacción del registro.
 *  - seleccionar(): suma a una consulta de `central_riesgo` sus motivos en un
 *    texto separado por comas (subconsulta), así una lista sigue siendo una
 *    sola consulta. Motivo::lista() y Motivo::etiquetas() lo leen.
 *
 * @changelog
 * - 2026-11-07  [Valery D. Ortuno P]  feat: creación inicial (motivos múltiples).
 *
 * @see  App\Enums\Motivo
 * @see  database/migrations/2026_11_07_000001_motivos_multiples_incidencia.php
 */

namespace App\Services\CentralRiesgo;

use App\Enums\Motivo;
use Illuminate\Contracts\Database\Query\Builder as ContratoBuilder;
use Illuminate\Support\Facades\DB;

class MotivosIncidencia
{
    /** Tabla de los motivos de cada incidencia. */
    public const TABLA = 'central_riesgo_motivo';

    /**
     * Guarda los motivos de una incidencia, sin repetir.
     *
     * @param  int  $idRegistro  Incidencia (`central_riesgo.id_registro`).
     * @param  list<Motivo>  $motivos  Motivos ya validados.
     */
    public static function guardar(int $idRegistro, array $motivos): void
    {
        DB::table(self::TABLA)->insert(array_map(
            fn (Motivo $motivo): array => ['id_registro' => $idRegistro, 'motivo' => $motivo->value],
            Motivo::lista($motivos),
        ));

        // La incidencia se guardó antes que sus motivos: si alguien cacheó la
        // central entre medio, la vería sin ellos.
        CacheCentralRiesgo::invalidar();
    }

    /**
     * Agrega a la consulta los motivos de cada incidencia, separados por
     * comas, en la columna `$alias`. La consulta tiene que tener
     * `central_riesgo` sin alias.
     *
     * @param  ContratoBuilder  $query  Consulta de `central_riesgo`.
     * @param  string  $alias  Columna en la que llegan los motivos.
     */
    public static function seleccionar(ContratoBuilder $query, string $alias = 'motivos'): ContratoBuilder
    {
        // string_agg en PostgreSQL, group_concat en SQLite (las pruebas).
        $agregado = DB::getDriverName() === 'pgsql'
            ? "string_agg(m.motivo::text, ',')"
            : "group_concat(m.motivo, ',')";

        return $query->selectRaw(
            "(select {$agregado} from ".self::TABLA.' as m'
            .' where m.id_registro = central_riesgo.id_registro) as '.$alias
        );
    }
}
