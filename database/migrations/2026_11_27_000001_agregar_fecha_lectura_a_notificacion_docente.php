<?php

/**
 * @file    2026_11_27_000001_agregar_fecha_lectura_a_notificacion_docente.php
 * @author  Alisson D. Alvarado <alvaradoalissondalet@gmail.com>
 * @created 2026-11-27
 * @description Conserva el momento de lectura para calcular la caducidad de cada aviso.
 * @changelog
 * - 2026-11-27  [Alisson D. Alvarado]  feat: registrar la fecha de lectura sin borrar avisos.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Agrega la fecha sin modificar los avisos existentes. */
    public function up(): void
    {
        if (! Schema::hasColumn('notificacion_docente', 'fecha_lectura')) {
            Schema::table('notificacion_docente', function (Blueprint $tabla): void {
                $tabla->timestamp('fecha_lectura')->nullable();
            });
        }
    }

    /** Conserva las fechas registradas incluso si se revierte la migración. */
    public function down(): void {}
};
