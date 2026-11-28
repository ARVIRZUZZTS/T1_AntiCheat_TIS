<?php

/**
 * @file    2026_11_28_000001_crear_materia.php
 *
 * @author  Diego Tejerina <josediegotejerinamolina@gmail.com>
 *
 * @created 2026-11-28
 *
 * @description
 * Catálogo de materias y sus prerrequisitos. Hasta ahora la "materia" era solo
 * texto dentro de `curso.nombre_curso`; estas tablas la vuelven un dato.
 *
 *  - `materia`: código SIS único opcional (7 dígitos), nombre, un
 *    `nombre_normalizado` (sin tildes ni mayúsculas) con índice único para no
 *    duplicar la misma materia escrita distinto, semestre opcional, `origen`
 *    ('catalogo' las del catálogo oficial, 'agregada por usuario' las que crea
 *    un docente al vuelo), fecha de creación y quién la creó.
 *  - `materia_prerrequisito`: aristas (materia → prerrequisito), ambas FK a
 *    `materia`.
 *
 * Idempotente (todo bajo `hasTable`/`hasColumn`) y reversible (down borra las
 * tablas). pgsql; en otros motores crea lo portable y omite el CHECK y el
 * índice parcial.
 *
 * @changelog
 * - 2026-11-28  [Diego Tejerina]  feat: creación del catálogo de materias.
 *
 * @see  App\Models\Materia
 * @see  database/seeders/MateriaCatalogoSeeder
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $esPgsql = DB::getDriverName() === 'pgsql';

        if (! Schema::hasTable('materia')) {
            Schema::create('materia', function (Blueprint $tabla) use ($esPgsql): void {
                $tabla->bigIncrements('id_materia');
                $tabla->string('codigo', 7)->nullable();
                $tabla->string('nombre', 120);
                $tabla->string('nombre_normalizado', 120);
                $tabla->smallInteger('semestre')->nullable();
                $tabla->string('origen', 20)->default('catalogo');
                $tabla->timestamp('fecha_creacion')->nullable()->useCurrent();
                $tabla->unsignedBigInteger('creado_por')->nullable();

                $tabla->unique('nombre_normalizado', 'materia_nombre_normalizado_unico');
                $tabla->foreign('creado_por', 'materia_creado_por_fk')
                    ->references('id_usuario')->on('usuario')->nullOnDelete();

                if (! $esPgsql) {
                    // En pgsql el único del código es parcial (solo cuando no es
                    // null); en otros motores, único normal (acepta varios null).
                    $tabla->unique('codigo', 'materia_codigo_unico');
                }
            });

            if ($esPgsql) {
                // Único solo para códigos presentes: varias materias pueden no
                // tener código sin chocar entre sí.
                DB::statement('CREATE UNIQUE INDEX materia_codigo_unico ON materia (codigo) WHERE codigo IS NOT NULL');
                DB::statement("ALTER TABLE materia ADD CONSTRAINT materia_origen_check
                    CHECK (origen IN ('catalogo', 'agregada por usuario'))");
            }
        }

        if (! Schema::hasTable('materia_prerrequisito')) {
            Schema::create('materia_prerrequisito', function (Blueprint $tabla): void {
                $tabla->unsignedBigInteger('id_materia');
                $tabla->unsignedBigInteger('id_prerrequisito');

                $tabla->primary(['id_materia', 'id_prerrequisito'], 'materia_prerrequisito_pk');
                $tabla->foreign('id_materia', 'materia_prerreq_materia_fk')
                    ->references('id_materia')->on('materia')->cascadeOnDelete();
                $tabla->foreign('id_prerrequisito', 'materia_prerreq_prerreq_fk')
                    ->references('id_materia')->on('materia')->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('materia_prerrequisito');
        Schema::dropIfExists('materia');
    }
};
