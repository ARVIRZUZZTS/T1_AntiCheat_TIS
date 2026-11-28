<?php

/**
 * @file    2026_11_28_000004_agregar_puede_asignar_auxiliares_a_usuario.php
 *
 * @author  Valery D. Ortuno P <valerydariana98@gmail.com>
 *
 * @created 2026-11-28
 *
 * @description
 * Separa el permiso de coordinador en dos: `puede_crear_auxiliares` (ya
 * existia) y `puede_asignar_auxiliares` (nuevo). El docente los da al asignar
 * un auxiliar a un curso. Los coordinadores que ya existian tenian los dos
 * permisos en uno solo, asi que el nuevo arranca con el mismo valor.
 *
 * @changelog
 * - 2026-11-28  [Valery D. Ortuno P]  feat: creacion inicial.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('usuario', 'puede_asignar_auxiliares')) {
            Schema::table('usuario', function (Blueprint $tabla): void {
                $tabla->boolean('puede_asignar_auxiliares')->default(false);
            });
        }

        // Los coordinadores de antes podian crear y asignar: conservan los dos.
        DB::table('usuario')->where('puede_crear_auxiliares', true)->update(['puede_asignar_auxiliares' => true]);
    }

    public function down(): void
    {
        if (Schema::hasColumn('usuario', 'puede_asignar_auxiliares')) {
            Schema::table('usuario', function (Blueprint $tabla): void {
                $tabla->dropColumn('puede_asignar_auxiliares');
            });
        }
    }
};
