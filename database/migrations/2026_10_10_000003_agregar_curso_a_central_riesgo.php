<?php

/**
 * @file    2026_10_10_000003_agregar_curso_a_central_riesgo.php
 *
 * @author  T1
 *
 * @created 2026-10-10
 *
 * @updated 2026-10-10
 *
 * @description
 * Agrega `central_riesgo.id_curso`: el curso al que pertenece la incidencia
 * cuando el examen se comparte entre varios cursos. Hasta ahora la materia se
 * derivaba del primer curso del examen (`id_examen -> examen_curso -> curso`), y
 * con un examen compartido no había forma de saber cuál eligió la persona que
 * registró la incidencia.
 *
 * Es nullable: los exámenes de un solo curso no lo necesitan (la materia sigue
 * saliendo del examen) y las incidencias que ya existen no lo tienen, así que no
 * se rompe ningún registro viejo.
 *
 * Es idempotente: si la columna ya está, no hace nada.
 *
 * @see  App\Models\CentralRiesgo
 * @see  App\Livewire\Monitoreo\RegistrarIncidencia
 *
 * @changelog
 * - 2026-10-10  [T1]  feat: columna `id_curso` nullable con llave foránea.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Agrega la columna y su llave foránea contra `curso`.
     */
    public function up(): void
    {
        if (! Schema::hasTable('central_riesgo') || Schema::hasColumn('central_riesgo', 'id_curso')) {
            return;
        }

        Schema::table('central_riesgo', function (Blueprint $tabla): void {
            $tabla->integer('id_curso')->nullable();
            $tabla->index('id_curso');
        });

        // La llave foránea se agrega aparte para que, si algún motor no la
        // soporta, la columna ya esté creada igual.
        Schema::table('central_riesgo', function (Blueprint $tabla): void {
            $tabla->foreign('id_curso', 'central_riesgo_id_curso_foreign')
                ->references('id_curso')
                ->on('curso');
        });
    }

    /**
     * Quita la llave foránea y la columna.
     */
    public function down(): void
    {
        if (! Schema::hasColumn('central_riesgo', 'id_curso')) {
            return;
        }

        Schema::table('central_riesgo', function (Blueprint $tabla): void {
            $tabla->dropForeign('central_riesgo_id_curso_foreign');
        });

        Schema::table('central_riesgo', function (Blueprint $tabla): void {
            $tabla->dropColumn('id_curso');
        });
    }
};
