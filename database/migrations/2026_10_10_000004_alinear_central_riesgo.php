<?php

/**
 * @file    2026_10_10_000004_alinear_central_riesgo.php
 *
 * @author  Valery D. Ortuno P. <valerydariana98@gmail.com>
 *
 * @created 2026-10-10
 *
 * @updated 2026-10-10
 *
 * @description
 * Deja `central_riesgo` con el esquema #70 que documenta `bd.md` y que el
 * modelo `CentralRiesgo` ya da por hecho: `sis_estudiante`, `id_examen` y
 * `motivo` como columnas propias. Esas columnas se agregaron a mano en el
 * servidor (`database/db_server/sync_local_to_server.sql`) y ninguna migración
 * las creaba, así que una base levantada con `php artisan migrate` quedaba con
 * el esquema viejo y la central de riesgos fallaba con
 * `column central_riesgo.sis_estudiante does not exist`.
 *
 * Además de las tres columnas:
 *  - las filas que ya existen se rellenan desde `registro_asistencia`, que es
 *    de donde salían el estudiante y el examen en el esquema viejo; el motivo
 *    queda en `otro`, porque antes solo existía el texto de `detalle_motivo`;
 *  - `id_ingreso` pasa a admitir null: el registro de incidencias ya no lo
 *    escribe. No se elimina para no perder el enlace de las filas viejas;
 *  - `fecha_registro` pasa a `timestamp`, porque la incidencia se muestra con
 *    fecha y hora;
 *  - `estado_incidencia` recupera el valor por defecto `Pendiente`, que el
 *    registro de incidencias necesita porque no escribe la columna.
 *
 * Es idempotente y solo corre en PostgreSQL: en una base que ya tiene el
 * esquema #70, como la del servidor, no cambia nada.
 *
 * @see  CentralRiesgo
 * @see  Motivo
 * @see  bd.md
 *
 * @changelog
 * - 2026-10-10  [Valery D. Ortuno P]  feat: columnas del esquema #70 en
 *   `central_riesgo` para las bases creadas por migraciones (HU 10).
 */

use App\Enums\Motivo;
use App\Models\CentralRiesgo;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * @throws QueryException Si el esquema no admite las columnas.
     */
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql' || ! Schema::hasTable('central_riesgo')) {
            return;
        }

        $this->crearTipoMotivo();
        $this->agregarColumnas();
        $this->rellenarFilasExistentes();
        $this->exigirColumnas();
        $this->agregarLlavesForaneas();
        $this->ajustarColumnasViejas();
    }

    /**
     * No revierte nada: quitar las columnas dejaría a la aplicación sin el
     * estudiante, el examen y el motivo de cada incidencia.
     */
    public function down(): void
    {
        //
    }

    /** Crea el ENUM `motivo` con los valores de `App\Enums\Motivo` si falta. */
    private function crearTipoMotivo(): void
    {
        if (DB::selectOne("SELECT 1 FROM pg_type WHERE typname = 'motivo' LIMIT 1")) {
            return;
        }

        $valores = implode(', ', array_map(
            fn (Motivo $motivo): string => DB::getPdo()->quote($motivo->value),
            Motivo::cases()
        ));

        DB::statement("CREATE TYPE motivo AS ENUM ({$valores})");
    }

    /** Agrega las tres columnas admitiendo null, para poder rellenarlas después. */
    private function agregarColumnas(): void
    {
        DB::statement('ALTER TABLE central_riesgo ADD COLUMN IF NOT EXISTS sis_estudiante varchar(20)');
        DB::statement('ALTER TABLE central_riesgo ADD COLUMN IF NOT EXISTS id_examen integer');
        DB::statement('ALTER TABLE central_riesgo ADD COLUMN IF NOT EXISTS motivo motivo');
    }

    /**
     * Completa las incidencias del esquema viejo con el estudiante y el examen
     * de su registro de asistencia.
     */
    private function rellenarFilasExistentes(): void
    {
        if (Schema::hasColumn('central_riesgo', 'id_ingreso')) {
            DB::statement(
                'UPDATE central_riesgo AS incidencia
                 SET sis_estudiante = COALESCE(incidencia.sis_estudiante, ingreso.id_estudiante),
                     id_examen = COALESCE(incidencia.id_examen, ingreso.id_examen)
                 FROM registro_asistencia AS ingreso
                 WHERE ingreso.id_ingreso = incidencia.id_ingreso
                   AND (incidencia.sis_estudiante IS NULL OR incidencia.id_examen IS NULL)'
            );
        }

        DB::table('central_riesgo')->whereNull('motivo')->update(['motivo' => Motivo::Otro->value]);
    }

    /**
     * Marca las columnas como obligatorias, igual que en el servidor. Una
     * columna con filas sin dato se deja como está: es preferible una columna
     * que admite null a una migración que no puede terminar.
     */
    private function exigirColumnas(): void
    {
        foreach (['sis_estudiante', 'id_examen', 'motivo', 'fecha_registro'] as $columna) {
            if (DB::table('central_riesgo')->whereNull($columna)->exists()) {
                continue;
            }

            DB::statement("ALTER TABLE central_riesgo ALTER COLUMN {$columna} SET NOT NULL");
        }
    }

    private function agregarLlavesForaneas(): void
    {
        $llaves = [
            'fk_cr_estudiante' => 'FOREIGN KEY (sis_estudiante) REFERENCES estudiante(sis_estudiante)',
            'fk_cr_examen' => 'FOREIGN KEY (id_examen) REFERENCES examen(id_examen)',
        ];

        foreach ($llaves as $nombre => $definicion) {
            $existe = DB::selectOne(
                "SELECT 1 FROM pg_constraint WHERE conname = ? AND conrelid = 'central_riesgo'::regclass",
                [$nombre]
            );

            if (! $existe) {
                DB::statement("ALTER TABLE central_riesgo ADD CONSTRAINT {$nombre} {$definicion}");
            }
        }
    }

    /** Ajusta lo que el esquema viejo todavía exigía y la aplicación ya no escribe. */
    private function ajustarColumnasViejas(): void
    {
        if (Schema::hasColumn('central_riesgo', 'id_ingreso')) {
            DB::statement('ALTER TABLE central_riesgo ALTER COLUMN id_ingreso DROP NOT NULL');
        }

        if (Schema::getColumnType('central_riesgo', 'fecha_registro') === 'date') {
            DB::statement(
                'ALTER TABLE central_riesgo
                 ALTER COLUMN fecha_registro TYPE timestamp USING fecha_registro::timestamp'
            );
        }

        if (Schema::hasColumn('central_riesgo', 'estado_incidencia')) {
            DB::statement("ALTER TABLE central_riesgo ALTER COLUMN estado_incidencia SET DEFAULT 'Pendiente'");
        }
    }
};
