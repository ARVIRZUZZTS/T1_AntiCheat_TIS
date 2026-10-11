<?php

/**
 * @file    2026_10_11_000001_agregar_oculta_a_notificacion_docente.php
 *
 * @author  T1 - Tech One SRL
 *
 * @created 2026-10-11
 *
 * @description Agrega la marca de ocultado a `notificacion_docente` (HU 12 ·
 * T2). Ocultar una notificación es un borrado lógico por docente: la fila sigue
 * existiendo y el caso de la central de riesgos no cambia, solo deja de
 * aparecer en el listado de ese docente.
 *
 * El esquema de Supabase todavía no tiene la columna, así que esta migración
 * queda PENDIENTE de ejecutar por la persona responsable de la base.
 *
 * @changelog
 * - 2026-10-11  [T1]  feat: columna `oculta` en `notificacion_docente` (#HU12).
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('notificacion_docente', 'oculta')) {
            Schema::table('notificacion_docente', function (Blueprint $tabla): void {
                $tabla->boolean('oculta')->notNullable()->default(false);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('notificacion_docente', 'oculta')) {
            Schema::table('notificacion_docente', function (Blueprint $tabla): void {
                $tabla->dropColumn('oculta');
            });
        }
    }
};
