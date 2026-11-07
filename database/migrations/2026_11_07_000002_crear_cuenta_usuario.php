<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const INDICE_USUARIO = 'usuario_nombre_usuario_unique';

    public function up(): void
    {
        if (! Schema::hasColumn('usuario', 'nombre')) {
            Schema::table('usuario', function (Blueprint $tabla): void {
                $tabla->string('nombre', 50)->nullable();
            });
        }

        if (! Schema::hasColumn('usuario', 'fecha_creacion')) {
            Schema::table('usuario', function (Blueprint $tabla): void {
                $tabla->timestamp('fecha_creacion')->nullable()->useCurrent();
            });
        }

        DB::table('usuario')
            ->whereNull('nombre')
            ->update(['nombre' => DB::raw('nombre_usuario')]);

        DB::statement(
            'CREATE UNIQUE INDEX IF NOT EXISTS '.self::INDICE_USUARIO
            .' ON usuario (lower(btrim(nombre_usuario)))'
        );
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS '.self::INDICE_USUARIO);

        if (Schema::hasColumn('usuario', 'fecha_creacion')) {
            Schema::table('usuario', function (Blueprint $tabla): void {
                $tabla->dropColumn('fecha_creacion');
            });
        }

        if (Schema::hasColumn('usuario', 'nombre')) {
            Schema::table('usuario', function (Blueprint $tabla): void {
                $tabla->dropColumn('nombre');
            });
        }
    }
};
