<?php

/**
 * @file    2026_11_22_000001_agregar_fecha_evento_a_notificacion_docente.php
 *
 * @author  T1 - Tech One SRL
 *
 * @created 2026-11-22
 *
 * @description Agrega la columna `fecha_evento` a `notificacion_docente` (HU 33).
 * Es el momento exacto que da origen a un aviso autogenerado por el sistema y no
 * por otra entidad del dominio; hoy lo usa el aviso de "Respaldo automático de la
 * base de datos", que no se cuelga de `central_riesgo` ni de una invitación y por
 * eso no tiene de dónde derivar su fecha. Guardarla permite mostrar la fecha y
 * hora exactas del respaldo y ordenar el aviso junto a los demás.
 *
 * Es nullable: las filas existentes (posible tramposo, central de riesgo,
 * invitaciones) no la necesitan y quedan en NULL. La migración es idempotente
 * y segura para PostgreSQL/Supabase: si la columna ya existe, no la repite.
 *
 * @changelog
 * - 2026-11-22  [T1]  feat: columna `fecha_evento` en `notificacion_docente` (#HU33).
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('notificacion_docente', 'fecha_evento')) {
            Schema::table('notificacion_docente', function (Blueprint $tabla): void {
                $tabla->timestamp('fecha_evento')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('notificacion_docente', 'fecha_evento')) {
            Schema::table('notificacion_docente', function (Blueprint $tabla): void {
                $tabla->dropColumn('fecha_evento');
            });
        }
    }
};
