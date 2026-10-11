<?php

/**
 * @file    2026_10_10_000002_quitar_hora_fin_de_examen.php
 *
 * @author  Alex Candia <alex.leonar.candia@gmail.com>
 *
 * @created 2026-10-10
 *
 * @updated 2026-10-10
 *
 * @description
 * Saca `examen.hora_fin`: es un dato derivado (hora de inicio + duración) y
 * guardarlo aparte permitía que quedara desincronizado con la duración. La hora
 * de fin se calcula al vuelo en el modelo (`Examen::getHoraFinAttribute()`) y en
 * los servicios que resuelven el estado del examen, así que la columna deja de
 * hacer falta.
 *
 * Es idempotente: si la columna ya no está, no hace nada. No se toca ninguna
 * otra tabla: la ventana horaria del examen se sigue leyendo igual, porque el
 * acceso `$examen->hora_fin` ahora lo resuelve el modelo.
 *
 * @see  App\Models\Examen
 * @see  App\Services\Examen\ListarExamenesCursoService
 * @see  App\Services\Monitoreo\GenerarReporteAsistenciaService
 *
 * @changelog
 * - 2026-10-10  [Alex Candia]  feat: quitar la columna derivada `hora_fin`.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Quita la columna si existe.
     */
    public function up(): void
    {
        if (! Schema::hasTable('examen') || ! Schema::hasColumn('examen', 'hora_fin')) {
            return;
        }

        Schema::table('examen', function (Blueprint $tabla): void {
            $tabla->dropColumn('hora_fin');
        });
    }

    /**
     * Vuelve a crear la columna vacía.
     *
     * No se puede recuperar lo que había: la hora de fin se calcula desde la
     * hora de inicio y la duración, así que el valor se puede reconstruir.
     */
    public function down(): void
    {
        if (! Schema::hasTable('examen') || Schema::hasColumn('examen', 'hora_fin')) {
            return;
        }

        Schema::table('examen', function (Blueprint $tabla): void {
            $tabla->time('hora_fin')->nullable();
        });
    }
};
