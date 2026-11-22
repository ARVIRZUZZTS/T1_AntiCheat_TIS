<?php

/**
 * @file    2026_11_22_000001_crear_auxiliar_examen.php
 *
 * @author  Valery D. Ortuno P <valerydariana98@gmail.com>
 *
 * @created 2026-11-22
 *
 * @description
 * Asignacion de auxiliares a un examen suelto (HU 24).
 *
 * La asignacion a un curso ya existe (`auxiliar_curso`) y da acceso a las dos
 * paginas de Monitor en vivo en todos los examenes del curso. Esta tabla
 * guarda la asignacion a un solo examen y que paginas puede ver el auxiliar
 * en el: `ver_ingreso`, `ver_monitor` o las dos (al menos una). Tambien
 * registra que docente hizo la asignacion, que es cuya configuracion del
 * examen sigue el auxiliar en el monitor.
 *
 * Es idempotente. Al revertir se pierden las asignaciones a examenes; las de
 * curso no se tocan.
 *
 * @changelog
 * - 2026-11-22  [Valery D. Ortuno P]  feat: creacion inicial (HU 24).
 *
 * @see  AsignarAuxiliarService
 * @see  AccesoAuxiliarService
 */

use App\Services\Auxiliar\AccesoAuxiliarService;
use App\Services\Auxiliar\AsignarAuxiliarService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('auxiliar_examen')) {
            return;
        }

        Schema::create('auxiliar_examen', function (Blueprint $tabla): void {
            $tabla->integer('id_auxiliar');
            $tabla->integer('id_examen');
            $tabla->boolean('ver_ingreso')->default(true);
            $tabla->boolean('ver_monitor')->default(true);
            $tabla->integer('asignado_por')->nullable();
            $tabla->timestamp('fecha_asignacion')->useCurrent();
            $tabla->primary(['id_auxiliar', 'id_examen']);
            $tabla->index('id_examen');
            $tabla->foreign('id_auxiliar')->references('id_usuario')->on('usuario')->cascadeOnDelete();
            $tabla->foreign('id_examen')->references('id_examen')->on('examen')->cascadeOnDelete();
            $tabla->foreign('asignado_por')->references('id_usuario')->on('usuario')->nullOnDelete();
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE auxiliar_examen ADD CONSTRAINT auxiliar_examen_alguna_pagina_check
                CHECK (ver_ingreso OR ver_monitor)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('auxiliar_examen');
    }
};
