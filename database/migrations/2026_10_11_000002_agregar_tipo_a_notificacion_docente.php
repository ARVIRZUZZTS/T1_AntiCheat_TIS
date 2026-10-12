<?php

/**
 * @file    2026_10_11_000002_agregar_tipo_a_notificacion_docente.php
 *
 * @author  David E. Chavez T. <virzuzz12345@gmail.com>
 *
 * @created 2026-10-11
 *
 * @description Agrega el tipo de aviso a `notificacion_docente` (HU 6 · T1/T2).
 * La pantalla de notificaciones pinta dos tarjetas distintas y necesita saber
 * cuál toca:
 *
 *   - `posible_tramposo` (HU 12): el auxiliar reportó a un estudiante como
 *     sospechoso; tarjeta ámbar con acción "Ver detalle".
 *   - `central_riesgo` (HU 6): el estudiante fue registrado o confirmado como
 *     tramposo en la central; tarjeta roja sin acción.
 *
 * El tipo se guarda en la notificación (no se deriva del registro) porque un
 * mismo caso de la central puede generar avisos de distinto tipo: el reporte
 * del auxiliar (sospechoso) y el ingreso efectivo al confirmarlo. Derivarlo
 * repintaría como HU 6 avisos que nacieron como HU 12.
 *
 * El default `posible_tramposo` cubre las filas ya existentes sin cambiarlas.
 * Igual que la de `oculta`, esta migración queda PENDIENTE de ejecutar sobre
 * el esquema de Supabase.
 *
 * @changelog
 * - 2026-10-11  [David E. Chavez T.]  feat: columna `tipo` en `notificacion_docente` (#HU6).
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('notificacion_docente', 'tipo')) {
            Schema::table('notificacion_docente', function (Blueprint $tabla): void {
                $tabla->string('tipo')->notNullable()->default('posible_tramposo');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('notificacion_docente', 'tipo')) {
            Schema::table('notificacion_docente', function (Blueprint $tabla): void {
                $tabla->dropColumn('tipo');
            });
        }
    }
};
