<?php

/**
 * @file    2026_11_24_000002_agregar_detalles_a_notificacion_docente.php
 *
 * @author  T1 - Tech One SRL
 *
 * @created 2026-11-24
 *
 * @description Agrega la columna `detalles` a `notificacion_docente` para los
 * avisos de eventos que no tienen una entidad propia a la que colgar: hoy, el
 * cambio de ambientes, fecha u hora de un examen ya programado. Guarda el
 * examen, las materias, el editor y el texto de cada cambio para que el aviso
 * muestre exactamente lo que se modificó aunque después vuelva a cambiar.
 *
 * Es nullable: las filas existentes (posible tramposo, central de riesgo,
 * invitaciones y respaldos) no la necesitan y quedan en NULL. La migración es
 * idempotente y segura para PostgreSQL/Supabase y SQLite (pruebas).
 *
 * @changelog
 * - 2026-11-24  [Equipo T1]  feat: columna `detalles` en `notificacion_docente`
 *   para los avisos de cambios de examen.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('notificacion_docente', 'detalles')) {
            Schema::table('notificacion_docente', function (Blueprint $tabla): void {
                $tabla->json('detalles')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('notificacion_docente', 'detalles')) {
            Schema::table('notificacion_docente', function (Blueprint $tabla): void {
                $tabla->dropColumn('detalles');
            });
        }
    }
};
