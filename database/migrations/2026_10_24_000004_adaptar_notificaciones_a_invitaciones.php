<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notificacion_docente', function (Blueprint $tabla): void {
            if (! Schema::hasColumn('notificacion_docente', 'id_docente_destinatario')) {
                $tabla->integer('id_docente_destinatario')->nullable();
            }

            if (! Schema::hasColumn('notificacion_docente', 'id_invitacion')) {
                $tabla->integer('id_invitacion')->nullable();
            }
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE notificacion_docente ALTER COLUMN id_central_riesgo DROP NOT NULL');
            DB::statement('ALTER TABLE notificacion_docente ALTER COLUMN id_curso DROP NOT NULL');
        } else {
            Schema::table('notificacion_docente', function (Blueprint $tabla): void {
                $tabla->integer('id_central_riesgo')->nullable()->change();
                $tabla->integer('id_curso')->nullable()->change();
            });
        }

        Schema::table('notificacion_docente', function (Blueprint $tabla): void {
            $tabla->foreign('id_docente_destinatario', 'fk_nd_destinatario')
                ->references('id_usuario')->on('usuario');
            $tabla->foreign('id_invitacion', 'fk_nd_invitacion')
                ->references('id_invitacion')->on('invitacion_examen_compartido');
            $tabla->index('id_docente_destinatario', 'notificacion_destinatario_index');
            $tabla->unique('id_invitacion', 'notificacion_invitacion_unique');
        });
    }

    public function down(): void
    {
        if (DB::table('notificacion_docente')->whereNotNull('id_invitacion')->exists()) {
            throw new RuntimeException('No se puede revertir mientras existan notificaciones de invitaciones.');
        }

        Schema::table('notificacion_docente', function (Blueprint $tabla): void {
            $tabla->dropUnique('notificacion_invitacion_unique');
            $tabla->dropIndex('notificacion_destinatario_index');
            $tabla->dropForeign('fk_nd_invitacion');
            $tabla->dropForeign('fk_nd_destinatario');
            $tabla->dropColumn(['id_docente_destinatario', 'id_invitacion']);
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE notificacion_docente ALTER COLUMN id_central_riesgo SET NOT NULL');
            DB::statement('ALTER TABLE notificacion_docente ALTER COLUMN id_curso SET NOT NULL');
        } else {
            Schema::table('notificacion_docente', function (Blueprint $tabla): void {
                $tabla->integer('id_central_riesgo')->nullable(false)->change();
                $tabla->integer('id_curso')->nullable(false)->change();
            });
        }
    }
};
