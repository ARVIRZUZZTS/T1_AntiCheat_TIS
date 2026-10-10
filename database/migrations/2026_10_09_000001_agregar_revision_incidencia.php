<?php

/**
 * @file    2026_10_09_000001_agregar_revision_incidencia.php
 *
 * @author  Diego Tejerina <josediegotejerinamolina@gmail.com>
 *
 * @created 2026-10-09
 *
 * @updated 2026-10-09
 *
 * @description Campos de revisión que faltan en el esquema actual de Supabase.
 *
 * @changelog
 * - 2026-10-09  [Diego Tejerina]  feat: estado y docente confirmador (#142).
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * @throws QueryException Si el esquema no admite los campos.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('central_riesgo', 'estado_incidencia')) {
            if (DB::getDriverName() === 'pgsql') {
                $tipo = DB::selectOne(
                    "SELECT 1 FROM pg_type t JOIN pg_namespace n ON n.oid = t.typnamespace
                     WHERE t.typname = 'estado_incidencia' AND n.nspname = 'public'"
                );
                if (! $tipo) {
                    DB::statement("CREATE TYPE public.estado_incidencia AS ENUM ('Confirmado', 'Pendiente')");
                }
                DB::statement(
                    "ALTER TABLE central_riesgo ADD COLUMN estado_incidencia
                     public.estado_incidencia NOT NULL DEFAULT 'Pendiente'"
                );
            } else {
                Schema::table('central_riesgo', function (Blueprint $tabla): void {
                    $tabla->enum('estado_incidencia', ['Confirmado', 'Pendiente'])->default('Pendiente');
                });
            }

            // El formulario existente distingue docente/auxiliar mediante este tipo.
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

    /**
     * @throws QueryException Si no puede retirar la clave foránea.
     */
    public function down(): void
    {
        Schema::table('central_riesgo', function (Blueprint $tabla): void {
            $tabla->dropForeign('fk_cr_confirmador');
            $tabla->dropColumn('id_confirmador');
        });
        // El estado puede existir desde una migración anterior; se conserva al revertir.
    }
};
