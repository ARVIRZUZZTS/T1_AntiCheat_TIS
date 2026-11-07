<?php

/**
 * @file    2026_11_07_000001_motivos_multiples_incidencia.php
 *
 * @author  Valery D. Ortuno P <valerydariana98@gmail.com>
 *
 * @created 2026-11-07
 *
 * @description
 * Una incidencia de la central de riesgos puede tener varios motivos (antes
 * uno solo, en `central_riesgo.motivo`).
 *
 *  1. Crea `central_riesgo_motivo` (id_registro, motivo): una fila por motivo,
 *     con el mismo enum `motivo`. La clave es el par, así que un motivo no se
 *     repite en la misma incidencia, y la FK borra los motivos junto con la
 *     incidencia (rechazar un caso la elimina).
 *  2. Copia el motivo que ya tenía cada incidencia: ninguna se queda sin él.
 *  3. Quita `central_riesgo.motivo`. `detalle_motivo` (la descripción) sigue
 *     en `central_riesgo`: es una sola por incidencia.
 *
 * Es idempotente. Al revertir vuelve la columna con el primer motivo de cada
 * incidencia (los demás se pierden, porque la columna admite uno).
 *
 * @changelog
 * - 2026-11-07  [Valery D. Ortuno P]  feat: motivos múltiples por incidencia.
 *
 * @see  App\Services\CentralRiesgo\RegistrarIncidenciaService
 * @see  App\Enums\Motivo
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('central_riesgo_motivo')) {
            if (DB::getDriverName() === 'pgsql') {
                // Con el tipo enum `motivo` de la base, igual que la columna
                // que reemplaza.
                DB::statement('CREATE TABLE central_riesgo_motivo (
                    id_registro integer NOT NULL
                        REFERENCES central_riesgo (id_registro) ON DELETE CASCADE,
                    motivo public.motivo NOT NULL,
                    PRIMARY KEY (id_registro, motivo)
                )');
            } else {
                Schema::create('central_riesgo_motivo', function (Blueprint $tabla): void {
                    $tabla->integer('id_registro');
                    $tabla->string('motivo');
                    $tabla->primary(['id_registro', 'motivo']);
                    $tabla->foreign('id_registro')->references('id_registro')->on('central_riesgo')->cascadeOnDelete();
                });
            }
        }

        if (Schema::hasColumn('central_riesgo', 'motivo')) {
            DB::statement('INSERT INTO central_riesgo_motivo (id_registro, motivo)
                SELECT id_registro, motivo FROM central_riesgo WHERE motivo IS NOT NULL');

            Schema::table('central_riesgo', function (Blueprint $tabla): void {
                $tabla->dropColumn('motivo');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('central_riesgo', 'motivo')) {
            if (DB::getDriverName() === 'pgsql') {
                DB::statement('ALTER TABLE central_riesgo ADD COLUMN motivo public.motivo');
                // El primero en el orden del enum, que es el de los formularios.
                DB::statement('UPDATE central_riesgo cr SET motivo = (
                    SELECT min(m.motivo) FROM central_riesgo_motivo m WHERE m.id_registro = cr.id_registro
                )');
                DB::statement("UPDATE central_riesgo SET motivo = 'otro' WHERE motivo IS NULL");
                DB::statement('ALTER TABLE central_riesgo ALTER COLUMN motivo SET NOT NULL');
            } else {
                Schema::table('central_riesgo', function (Blueprint $tabla): void {
                    $tabla->string('motivo')->default('otro');
                });
                DB::statement('UPDATE central_riesgo SET motivo = COALESCE((
                    SELECT min(m.motivo) FROM central_riesgo_motivo m WHERE m.id_registro = central_riesgo.id_registro
                ), \'otro\')');
            }
        }

        Schema::dropIfExists('central_riesgo_motivo');
    }
};
