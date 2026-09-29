<?php

/**
 * @file    2026_09_28_000001_ampliar_central_riesgo_para_el_reporte_de_incidencias.php
 * @author  Candy
 * @created 2026-09-28
 * @updated 2026-09-28
 *
 * @description
 * Deja la tabla `central_riesgo` en condiciones de guardar el reporte de una
 * incidencia tal como lo pide el flujo del monitor en vivo: estudiante, usuario
 * registrador, materia, estado, motivo, descripción, fecha y hora.
 *
 * El esquema original (docker/postgres/init/001_create_schema.sql) solo tenía
 * `detalle_motivo`, `fecha_registro` de tipo `date` y `tipo_infraccion`, y
 * ataba el registro a un ingreso previo con `id_ingreso` NOT NULL. Eso impedía
 * registrar desde la central de riesgos, donde el estudiante puede no tener
 * ingreso, y no guardaba la materia ni la descripción.
 *
 * Cambios:
 * - `id_estudiante`, `materia` y `descripcion`: columnas nuevas.
 * - `fecha_registro`: pasa de `date` a `timestamp`, para conservar la hora.
 * - `id_ingreso`: pasa a nullable, porque el reporte ya no exige un ingreso.
 * - `id_registro`: recibe una secuencia, para no calcular el siguiente id a mano
 *   desde la aplicación y evitar colisiones entre dos registros simultáneos.
 *
 * @changelog
 * - 2026-09-28  [Candy]  feat: migración inicial.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Aplica los cambios de la tabla `central_riesgo`.
     */
    public function up(): void
    {
        Schema::table('central_riesgo', function (Blueprint $table): void {
            $table->string('id_estudiante', 20)->nullable()->after('id_registro');
            $table->string('materia', 100)->nullable()->after('detalle_motivo');
            $table->text('descripcion')->nullable()->after('materia');
            $table->timestamp('fecha_registro')->nullable()->change();
            $table->integer('id_ingreso')->nullable()->change();
        });

        // La clave primaria venía como `integer` a secas, sin secuencia, así que
        // cada alta tenía que inventar el id desde el código. Se crea la
        // secuencia y se deja como valor por defecto de la columna.
        //
        // La tabla ya trae filas sembradas con los ids 1 a 5, así que la
        // secuencia tiene que arrancar en el mayor id existente y no en 1: si no,
        // el primer reporte choca contra la clave primaria.
        DB::statement('CREATE SEQUENCE IF NOT EXISTS central_riesgo_id_registro_seq');
        DB::statement(
            "ALTER TABLE central_riesgo ALTER COLUMN id_registro "
            .'SET DEFAULT nextval(\'central_riesgo_id_registro_seq\')'
        );
        DB::statement(
            "SELECT setval('central_riesgo_id_registro_seq', "
            .'COALESCE((SELECT MAX(id_registro) FROM central_riesgo), 0) + 1, false)'
        );

        DB::statement(
            'ALTER TABLE central_riesgo ADD CONSTRAINT fk_cr_estudiante '
            .'FOREIGN KEY (id_estudiante) REFERENCES estudiante(sis_estudiante)'
        );
    }

    /**
     * Revierte los cambios, dejando la tabla como estaba en el esquema original.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE central_riesgo DROP CONSTRAINT IF EXISTS fk_cr_estudiante');

        DB::statement('ALTER TABLE central_riesgo ALTER COLUMN id_registro DROP DEFAULT');
        DB::statement('DROP SEQUENCE IF EXISTS central_riesgo_id_registro_seq');

        Schema::table('central_riesgo', function (Blueprint $table): void {
            $table->dropColumn(['id_estudiante', 'materia', 'descripcion']);
            $table->date('fecha_registro')->nullable()->change();
            $table->integer('id_ingreso')->nullable(false)->change();
        });
    }
};
