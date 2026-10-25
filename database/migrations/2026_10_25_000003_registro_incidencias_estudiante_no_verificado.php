<?php

/**
 * @file    2026_10_25_000003_registro_incidencias_estudiante_no_verificado.php
 *
 * @author  Valery D. Ortuno P <valerydariana98@gmail.com>
 *
 * @created 2026-10-25
 *
 * @description
 * Dos cambios para el registro de incidencias del monitor en vivo (HU 5):
 *
 *  1. `estudiante.verificado`: los estudiantes que cargan los docentes son
 *     verificados; los que se dan de alta desde una incidencia (no estaban en
 *     la base) quedan como no verificados, porque sus datos los dio el propio
 *     estudiante. Viven en la misma tabla para no romper la FK de
 *     `central_riesgo`, pero no se mezclan: no están inscritos en ningún curso.
 *     Todos los estudiantes que ya existen quedan como verificados.
 *  2. Motivo nuevo `en examen sin registro de ingreso`, para el monitor:
 *     reemplaza en esa lista a "intento de ingreso no autorizado", que solo
 *     tiene sentido en el ingreso.
 *
 * Es idempotente: no repite la columna ni el valor si ya existen.
 *
 * @changelog
 * - 2026-10-25  [Valery D. Ortuno P]  feat: `estudiante.verificado` y motivo `en examen sin registro de ingreso`.
 *
 * @see  App\Services\CentralRiesgo\RegistrarIncidenciaService
 * @see  App\Enums\Motivo
 */

use App\Enums\Motivo;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('estudiante', 'verificado')) {
            Schema::table('estudiante', function (Blueprint $tabla): void {
                $tabla->boolean('verificado')->default(true);
            });
        }

        if (DB::getDriverName() === 'pgsql'
            && DB::selectOne("SELECT 1 FROM pg_type WHERE typname = 'motivo' LIMIT 1")) {
            DB::statement('ALTER TYPE motivo ADD VALUE IF NOT EXISTS '
                .DB::getPdo()->quote(Motivo::EnExamenSinRegistroDeIngreso->value));
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('estudiante', 'verificado')) {
            Schema::table('estudiante', function (Blueprint $tabla): void {
                $tabla->dropColumn('verificado');
            });
        }

        // PostgreSQL no permite quitar valores de un ENUM sin recrearlo.
    }
};
