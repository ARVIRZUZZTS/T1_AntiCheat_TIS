<?php

/**
 * @file    2026_10_24_000001_agregar_anio_a_curso_tipo_gestion.php
 *
 * @author  Valery D. Ortuno P <valerydariana98@gmail.com>
 *
 * @created 2026-10-24
 *
 * @description
 * Agrega el año a la gestión de cada curso (`curso_tipo_gestion.anio`), para
 * que la gestión quede completa ("Segundo semestre 2026") y el docente pueda
 * crear cursos de un año futuro. `fecha_creacion` no sirve para esto: es el
 * día en que se registró el curso, no el año en que se dicta.
 *
 * Las filas que ya existen toman el año de la `fecha_creacion` de su curso (o
 * el año actual si no tiene fecha), y recién entonces la columna pasa a NOT
 * NULL, así ninguna gestión queda sin año. Es idempotente: si la columna ya
 * existe no la repite.
 *
 * @changelog
 * - 2026-10-24  [Valery D. Ortuno P]  feat: columna `anio` en `curso_tipo_gestion`.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('curso_tipo_gestion', 'anio')) {
            return;
        }

        Schema::table('curso_tipo_gestion', function (Blueprint $tabla): void {
            $tabla->smallInteger('anio')->nullable();
        });

        DB::statement(
            'update curso_tipo_gestion ctg'
            .' set anio = coalesce(extract(year from c.fecha_creacion)::smallint, extract(year from current_date)::smallint)'
            .' from curso c where c.id_curso = ctg.id_curso'
        );

        // Una gestión sin curso no debería existir (hay FK), pero si quedara
        // alguna, también recibe año para poder poner la columna en NOT NULL.
        DB::statement('update curso_tipo_gestion set anio = extract(year from current_date)::smallint where anio is null');

        DB::statement('alter table curso_tipo_gestion alter column anio set not null');
    }

    public function down(): void
    {
        if (Schema::hasColumn('curso_tipo_gestion', 'anio')) {
            Schema::table('curso_tipo_gestion', function (Blueprint $tabla): void {
                $tabla->dropColumn('anio');
            });
        }
    }
};
