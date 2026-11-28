<?php

/**
 * @file    2026_11_28_000002_agregar_materia_y_grupo_a_curso.php
 *
 * @author  Diego Tejerina <josediegotejerinamolina@gmail.com>
 *
 * @created 2026-11-28
 *
 * @description
 * Agrega a `curso` la referencia a la materia (`id_materia`, FK a `materia`) y
 * el número de grupo (`numero_grupo`, entero 1-10). Se conserva `nombre_curso`,
 * que las pantallas actuales siguen usando: Crear/Editar curso lo autollenan
 * como "Materia - Grupo N".
 *
 * `id_materia` queda nullable en el esquema: lo llena la migración de datos
 * (comando `cursos:asignar-materia-grupo`), que interpreta `nombre_curso`. El
 * `numero_grupo` también es nullable (se reporta cuando no se puede deducir).
 *
 * La unicidad materia+grupo+gestión+año se valida en los Services (gestión y año
 * viven en `curso_tipo_gestion`, otra tabla). Aquí solo va el CHECK del rango de
 * grupo.
 *
 * Idempotente (hasColumn) y reversible. No borra ni modifica ningún curso.
 *
 * @changelog
 * - 2026-11-28  [Diego Tejerina]  feat: id_materia y numero_grupo en curso.
 *
 * @see  database/migrations/2026_11_28_000001_crear_materia.php
 * @see  App\Console\Commands\AsignarMateriaGrupoCursos
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

        if (! Schema::hasColumn('curso', 'id_materia')) {
            Schema::table('curso', function (Blueprint $tabla): void {
                $tabla->unsignedBigInteger('id_materia')->nullable();
                $tabla->foreign('id_materia', 'curso_id_materia_fk')
                    ->references('id_materia')->on('materia')->restrictOnDelete();
            });
        }

        if (! Schema::hasColumn('curso', 'numero_grupo')) {
            Schema::table('curso', function (Blueprint $tabla): void {
                $tabla->smallInteger('numero_grupo')->nullable();
            });

            if ($esPgsql) {
                DB::statement('ALTER TABLE curso ADD CONSTRAINT curso_numero_grupo_check
                    CHECK (numero_grupo IS NULL OR (numero_grupo >= 1 AND numero_grupo <= 10))');
            }
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE curso DROP CONSTRAINT IF EXISTS curso_numero_grupo_check');
        }

        if (Schema::hasColumn('curso', 'id_materia')) {
            Schema::table('curso', function (Blueprint $tabla): void {
                $tabla->dropForeign('curso_id_materia_fk');
                $tabla->dropColumn('id_materia');
            });
        }

        if (Schema::hasColumn('curso', 'numero_grupo')) {
            Schema::table('curso', function (Blueprint $tabla): void {
                $tabla->dropColumn('numero_grupo');
            });
        }
    }
};
