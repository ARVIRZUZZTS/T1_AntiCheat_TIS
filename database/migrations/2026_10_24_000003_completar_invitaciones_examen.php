<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const INDICE_ACTIVAS = 'invitacion_examen_activa_unique';

    public function up(): void
    {
        if (! Schema::hasColumn('usuario', 'correo')) {
            Schema::table('usuario', function (Blueprint $tabla): void {
                $tabla->string('correo', 254)->nullable()->unique('usuario_correo_unique');
            });
        }

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TYPE invitacion_estado ADD VALUE IF NOT EXISTS 'cancelado'");
        }

        Schema::table('invitacion_examen_compartido', function (Blueprint $tabla): void {
            if (! Schema::hasColumn('invitacion_examen_compartido', 'id_docente_invitador')) {
                $tabla->integer('id_docente_invitador')->nullable();
            }

            if (! Schema::hasColumn('invitacion_examen_compartido', 'created_at')) {
                $tabla->timestamp('created_at')->nullable();
            }

            if (! Schema::hasColumn('invitacion_examen_compartido', 'updated_at')) {
                $tabla->timestamp('updated_at')->nullable();
            }
        });

        DB::table('invitacion_examen_compartido')
            ->whereNull('id_docente_invitador')
            ->update([
                'id_docente_invitador' => DB::raw('(SELECT creador FROM examen WHERE examen.id_examen = invitacion_examen_compartido.id_examen)'),
            ]);

        DB::table('invitacion_examen_compartido')
            ->whereNull('created_at')
            ->update(['created_at' => now(), 'updated_at' => now()]);

        $hayIncompletas = DB::table('invitacion_examen_compartido')
            ->whereNull('id_docente_invitador')
            ->orWhereNull('created_at')
            ->orWhereNull('updated_at')
            ->exists();

        if ($hayIncompletas) {
            throw new RuntimeException('Hay invitaciones antiguas que no se pudieron asociar con el creador de su examen.');
        }

        $hayDuplicadas = DB::table('invitacion_examen_compartido')
            ->select('id_examen', 'id_docente_invitado')
            ->whereIn('estado', ['pendiente', 'aceptado'])
            ->groupBy('id_examen', 'id_docente_invitado')
            ->havingRaw('COUNT(*) > 1')
            ->exists();

        if ($hayDuplicadas) {
            throw new RuntimeException('Hay invitaciones pendientes o aceptadas duplicadas; deben revisarse antes de migrar.');
        }

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE invitacion_examen_compartido ALTER COLUMN id_docente_invitador SET NOT NULL');
            DB::statement('ALTER TABLE invitacion_examen_compartido ALTER COLUMN created_at SET NOT NULL');
            DB::statement('ALTER TABLE invitacion_examen_compartido ALTER COLUMN updated_at SET NOT NULL');
            DB::statement(
                'ALTER TABLE invitacion_examen_compartido ADD CONSTRAINT fk_iec_invitador '
                .'FOREIGN KEY (id_docente_invitador) REFERENCES usuario(id_usuario)'
            );
        } else {
            Schema::table('invitacion_examen_compartido', function (Blueprint $tabla): void {
                $tabla->foreign('id_docente_invitador', 'fk_iec_invitador')
                    ->references('id_usuario')->on('usuario');
            });
        }

        DB::statement(
            'CREATE UNIQUE INDEX IF NOT EXISTS '.self::INDICE_ACTIVAS
            .' ON invitacion_examen_compartido (id_examen, id_docente_invitado)'
            ." WHERE estado IN ('pendiente', 'aceptado')"
        );

        Schema::table('invitacion_examen_compartido', function (Blueprint $tabla): void {
            $tabla->index('id_docente_invitador', 'invitacion_examen_invitador_index');
            $tabla->index('id_docente_invitado', 'invitacion_examen_invitado_index');
            $tabla->index('id_examen', 'invitacion_examen_examen_index');
        });
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS '.self::INDICE_ACTIVAS);

        Schema::table('invitacion_examen_compartido', function (Blueprint $tabla): void {
            $tabla->dropIndex('invitacion_examen_invitador_index');
            $tabla->dropIndex('invitacion_examen_invitado_index');
            $tabla->dropIndex('invitacion_examen_examen_index');
            $tabla->dropForeign('fk_iec_invitador');
            $tabla->dropColumn(['id_docente_invitador', 'created_at', 'updated_at']);
        });

        if (Schema::hasColumn('usuario', 'correo')) {
            Schema::table('usuario', function (Blueprint $tabla): void {
                $tabla->dropUnique('usuario_correo_unique');
                $tabla->dropColumn('correo');
            });
        }
    }
};
