<?php

/**
 * @file    2026_11_23_000001_agregar_pendiente_correo_a_estado_cuenta.php
 *
 * @author  Diego Tejerina <josediegotejerinamolina@gmail.com>
 *
 * @created 2026-11-23
 *
 * @description
 * Agrega el estado `pendiente_correo` a `usuario.estado_cuenta` (HU 27): una
 * cuenta con correo institucional queda pendiente de confirmación por correo
 * electrónico y no entra a la bandeja de validación manual de dos docentes
 * (HU 26), que sigue filtrando solo `pendiente`.
 *
 * El valor es más largo que `habilitada`, así que la columna se amplía a
 * varchar(20) y se reescribe el CHECK con los cuatro estados.
 *
 * Es idempotente (DROP IF EXISTS + ADD, y el TYPE es no-op si ya está en 20).
 * Al revertir, las cuentas `pendiente_correo` pasan a `pendiente` (validación
 * manual) para que el CHECK de tres valores vuelva a aceptar la columna.
 *
 * @changelog
 * - 2026-11-23  [Diego Tejerina]  feat: creación inicial (HU 27).
 *
 * @see  App\Enums\EstadoCuenta
 * @see  database/migrations/2026_11_08_000001_validacion_registro_docente.php
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql' || ! Schema::hasColumn('usuario', 'estado_cuenta')) {
            return;
        }

        DB::statement('ALTER TABLE usuario DROP CONSTRAINT IF EXISTS usuario_estado_cuenta_check');
        DB::statement('ALTER TABLE usuario ALTER COLUMN estado_cuenta TYPE varchar(20)');
        DB::statement("ALTER TABLE usuario ADD CONSTRAINT usuario_estado_cuenta_check
            CHECK (estado_cuenta IN ('pendiente', 'pendiente_correo', 'habilitada', 'rechazada'))");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql' || ! Schema::hasColumn('usuario', 'estado_cuenta')) {
            return;
        }

        DB::statement('ALTER TABLE usuario DROP CONSTRAINT IF EXISTS usuario_estado_cuenta_check');
        // Sin el estado nuevo, las cuentas por confirmar pasan a validación manual.
        DB::table('usuario')->where('estado_cuenta', 'pendiente_correo')->update(['estado_cuenta' => 'pendiente']);
        DB::statement('ALTER TABLE usuario ALTER COLUMN estado_cuenta TYPE varchar(10)');
        DB::statement("ALTER TABLE usuario ADD CONSTRAINT usuario_estado_cuenta_check
            CHECK (estado_cuenta IN ('pendiente', 'habilitada', 'rechazada'))");
    }
};
