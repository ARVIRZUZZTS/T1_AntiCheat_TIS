<?php

/**
 * @file    2026_10_26_000001_unificar_estado_incidencia_en_tipo_infraccion.php
 *
 * @author  Valery D. Ortuno P <valerydariana98@gmail.com>
 *
 * @created 2026-10-26
 *
 * @description
 * `tipo_infraccion` pasa a ser lo único que dice en qué estado está una
 * incidencia de la central de riesgos: tramposo = confirmado, sospechoso = en
 * revisión. La columna `estado_incidencia` repetía ese dato y se había
 * desalineado (filas tramposo + Pendiente y sospechoso + Confirmado), y
 * `id_confirmador` ya no se usa: confirma el docente del curso y el aviso le
 * llega a él.
 *
 *  1. Las incidencias sospechosas que un docente ya confirmó (estado
 *     `Confirmado`) pasan a tramposo, que es lo que significa confirmar.
 *     Las tramposas que quedaron en `Pendiente` no cambian: el tipo manda.
 *  2. Se eliminan la FK `fk_cr_confirmador`, las columnas `id_confirmador` y
 *     `estado_incidencia` y el tipo enum `estado_incidencia`.
 *
 * Es idempotente. Al revertir se recrean el tipo, las columnas y la FK, y el
 * estado se calcula a partir del tipo (el confirmador no se puede recuperar).
 *
 * @changelog
 * - 2026-10-26  [Valery D. Ortuno P]  refactor: se unifica el estado de la incidencia en `tipo_infraccion`.
 *
 * @see  App\Enums\TipoInfraccion
 * @see  App\Services\CentralRiesgo\ResolverIncidenciaService
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('central_riesgo', 'estado_incidencia')) {
            DB::table('central_riesgo')
                ->where('tipo_infraccion', 'sospechoso')
                ->where('estado_incidencia', 'Confirmado')
                ->update(['tipo_infraccion' => 'tramposo']);
        }

        if (Schema::hasColumn('central_riesgo', 'id_confirmador')) {
            Schema::table('central_riesgo', function (Blueprint $tabla): void {
                // SQLite (pruebas) solo quita la FK por columna, no por nombre.
                $tabla->dropForeign(DB::getDriverName() === 'sqlite' ? ['id_confirmador'] : 'fk_cr_confirmador');
                $tabla->dropColumn('id_confirmador');
            });
        }

        if (Schema::hasColumn('central_riesgo', 'estado_incidencia')) {
            Schema::table('central_riesgo', function (Blueprint $tabla): void {
                $tabla->dropColumn('estado_incidencia');
            });
        }

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('DROP TYPE IF EXISTS public.estado_incidencia');
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('central_riesgo', 'estado_incidencia')) {
            if (DB::getDriverName() === 'pgsql') {
                DB::statement("DO $$ BEGIN
                    CREATE TYPE public.estado_incidencia AS ENUM ('Confirmado', 'Pendiente');
                EXCEPTION WHEN duplicate_object THEN NULL; END $$");
                DB::statement("ALTER TABLE central_riesgo ADD COLUMN estado_incidencia
                    public.estado_incidencia NOT NULL DEFAULT 'Pendiente'");
            } else {
                Schema::table('central_riesgo', function (Blueprint $tabla): void {
                    $tabla->enum('estado_incidencia', ['Confirmado', 'Pendiente'])->default('Pendiente');
                });
            }

            DB::table('central_riesgo')->where('tipo_infraccion', 'tramposo')
                ->update(['estado_incidencia' => 'Confirmado']);
        }

        if (! Schema::hasColumn('central_riesgo', 'id_confirmador')) {
            Schema::table('central_riesgo', function (Blueprint $tabla): void {
                $tabla->integer('id_confirmador')->nullable();
                $tabla->foreign('id_confirmador', 'fk_cr_confirmador')
                    ->references('id_usuario')->on('usuario');
            });
        }
    }
};
