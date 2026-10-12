<?php

/**
 * @file    2026_10_11_000002_agregar_tipo_a_notificacion_docente.php
 *
 * @author  T1 - Tech One SRL
 *
 * @created 2026-10-11
 *
 * @description Agrega la columna `tipo` a `notificacion_docente` (HU 12): el
 * tipo de aviso. Nace en `posible_tramposo`, que es el único tipo que hoy
 * genera `NotificacionService`, de modo que el alta no depende de que quien
 * inserta conozca el valor.
 *
 * Esta migración ya se aplicó a Supabase fuera del repositorio (batch 7) y el
 * archivo se había perdido; se repone acá para que las migraciones reflejen el
 * esquema real de la base. Es idempotente: si la columna ya existe no la repite.
 *
 * @changelog
 * - 2026-10-11  [T1]  feat: columna `tipo` en `notificacion_docente` (#HU12).
 * - 2026-10-11  [T1]  fix: se repone el archivo, perdido tras aplicarse en
 *   Supabase, para que el esquema quede en sincronía con la base.
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
