<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('curso', function (Blueprint $tabla): void {
            $tabla->integer('id_ambiente')->nullable();
            $tabla->index('id_ambiente', 'idx_curso_ambiente');
            $tabla->foreign('id_ambiente', 'fk_curso_ambiente')
                ->references('id_ambiente')
                ->on('ambiente');
        });

        Schema::table('examen', function (Blueprint $tabla): void {
            $tabla->string('nombre_examen', 100)->nullable();
        });

        DB::statement('ALTER TABLE examen ALTER COLUMN tipo_examen DROP NOT NULL');
    }

    public function down(): void
    {
        if (DB::table('examen')->whereNull('tipo_examen')->exists()) {
            throw new RuntimeException(
                'No se puede revertir la migracion mientras existan examenes sin tipo_examen.'
            );
        }

        DB::statement('ALTER TABLE examen ALTER COLUMN tipo_examen SET NOT NULL');

        Schema::table('examen', function (Blueprint $tabla): void {
            $tabla->dropColumn('nombre_examen');
        });

        Schema::table('curso', function (Blueprint $tabla): void {
            $tabla->dropForeign('fk_curso_ambiente');
            $tabla->dropIndex('idx_curso_ambiente');
            $tabla->dropColumn('id_ambiente');
        });
    }
};
