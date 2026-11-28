<?php

/**
 * @file    2026_11_27_000002_permitir_estado_cuenta_eliminada.php
 *
 * @author  Diego Tejerina <josediegotejerinamolina@gmail.com>
 *
 * @created 2026-11-27
 *
 * @description
 * Agrega el estado `eliminada` a `usuario.estado_cuenta`: una cuenta de auxiliar
 * eliminada de forma lógica no puede iniciar sesión y sale de todas las listas,
 * conservando su historial. Reutiliza el patrón de columna de estado de
 * "Archivar curso" (no hay SoftDeletes ni deleted_at en el repo).
 *
 * El CHECK de `estado_cuenta` se reescribe con los cinco estados. La columna ya
 * es varchar(20) desde HU 27, así que no se cambia el tipo. Es idempotente
 * (DROP IF EXISTS + ADD). Al revertir, las cuentas `eliminada` se dejan en su
 * estado (no se puede adivinar el anterior) pero se reescribe el CHECK sin el
 * valor nuevo; por eso down() solo vuelve a los cuatro estados si no queda
 * ninguna fila `eliminada`.
 *
 * @changelog
 * - 2026-11-27  [Diego Tejerina]  feat: estado `eliminada` para la eliminación
 *   lógica de auxiliares.
 *
 * @see  App\Enums\EstadoCuenta
 * @see  App\Services\Auxiliar\EliminarAuxiliarService
 * @see  database/migrations/2026_11_23_000001_agregar_pendiente_correo_a_estado_cuenta.php
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
        DB::statement("ALTER TABLE usuario ADD CONSTRAINT usuario_estado_cuenta_check
            CHECK (estado_cuenta IN ('pendiente', 'pendiente_correo', 'habilitada', 'rechazada', 'eliminada'))");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql' || ! Schema::hasColumn('usuario', 'estado_cuenta')) {
            return;
        }

        // Sin filas eliminadas, el CHECK vuelve a los cuatro estados de HU 27.
        if (DB::table('usuario')->where('estado_cuenta', 'eliminada')->exists()) {
            return;
        }

        DB::statement('ALTER TABLE usuario DROP CONSTRAINT IF EXISTS usuario_estado_cuenta_check');
        DB::statement("ALTER TABLE usuario ADD CONSTRAINT usuario_estado_cuenta_check
            CHECK (estado_cuenta IN ('pendiente', 'pendiente_correo', 'habilitada', 'rechazada'))");
    }
};
