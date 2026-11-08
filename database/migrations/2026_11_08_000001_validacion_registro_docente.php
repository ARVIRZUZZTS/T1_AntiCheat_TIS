<?php

/**
 * @file    2026_11_08_000001_validacion_registro_docente.php
 *
 * @author  Valery D. Ortuno P <valerydariana98@gmail.com>
 *
 * @created 2026-11-08
 *
 * @description
 * Validación del registro de nuevos docentes (HU 26).
 *
 *  1. `usuario.estado_cuenta`: pendiente, habilitada o rechazada. Por defecto
 *     habilitada, así las cuentas que ya existen y las que crea el sistema
 *     (auxiliares) siguen entrando. Las que no tienen ningún rol vienen del
 *     registro (que solo crea docentes) y nadie las validó: quedan pendientes
 *     y reciben el rol docente.
 *  2. `validacion_registro`: la decisión de cada docente sobre cada
 *     solicitud (aprobado o desaprobado, con el motivo si desaprobó). La clave
 *     es el par solicitante-validador, así un docente decide una sola vez.
 *
 * Es idempotente. Al revertir se pierden las decisiones y el estado, y las
 * cuentas pendientes vuelven a quedar sin rol.
 *
 * @changelog
 * - 2026-11-08  [Valery D. Ortuno P]  feat: creación inicial (HU 26).
 *
 * @see  EstadoCuenta
 * @see  ValidarRegistroDocenteService
 */

use App\Enums\EstadoCuenta;
use App\Services\Docente\ValidarRegistroDocenteService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $pgsql = DB::getDriverName() === 'pgsql';

        if (! Schema::hasColumn('usuario', 'estado_cuenta')) {
            Schema::table('usuario', function (Blueprint $tabla): void {
                $tabla->string('estado_cuenta', 10)->default('habilitada');
            });

            if ($pgsql) {
                DB::statement("ALTER TABLE usuario ADD CONSTRAINT usuario_estado_cuenta_check
                    CHECK (estado_cuenta IN ('pendiente', 'habilitada', 'rechazada'))");
            }

            // Sin rol: vienen del registro, que solo crea docentes, y nadie las
            // validó. Quedan pendientes y con el rol docente.
            $sinRol = DB::table('usuario')
                ->whereNotExists(fn ($q) => $q->from('rol_usuario')->whereColumn('rol_usuario.id_usuario', 'usuario.id_usuario'))
                ->pluck('id_usuario');
            $idDocente = DB::table('rol')->where('nombre_rol', 'docente')->value('id_rol');

            if ($sinRol->isNotEmpty()) {
                DB::table('usuario')->whereIn('id_usuario', $sinRol)->update(['estado_cuenta' => 'pendiente']);

                if ($idDocente !== null) {
                    DB::table('rol_usuario')->insert(
                        $sinRol->map(fn ($id): array => ['id_usuario' => $id, 'id_rol' => $idDocente])->all()
                    );
                }
            }
        }

        if (! Schema::hasTable('validacion_registro')) {
            Schema::create('validacion_registro', function (Blueprint $tabla): void {
                $tabla->integer('id_solicitante');
                $tabla->integer('id_validador');
                $tabla->string('decision', 11);
                $tabla->string('motivo', 300)->nullable();
                $tabla->timestamp('fecha')->useCurrent();
                $tabla->primary(['id_solicitante', 'id_validador']);
                $tabla->foreign('id_solicitante')->references('id_usuario')->on('usuario')->cascadeOnDelete();
                $tabla->foreign('id_validador')->references('id_usuario')->on('usuario')->cascadeOnDelete();
            });

            if ($pgsql) {
                DB::statement("ALTER TABLE validacion_registro ADD CONSTRAINT validacion_registro_decision_check
                    CHECK (decision IN ('aprobado', 'desaprobado'))");
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('validacion_registro');

        if (Schema::hasColumn('usuario', 'estado_cuenta')) {
            // Las pendientes vuelven a quedar sin rol, como antes: así un
            // up() posterior las reconoce como pendientes otra vez.
            DB::table('rol_usuario')
                ->whereIn('id_usuario', DB::table('usuario')->where('estado_cuenta', 'pendiente')->select('id_usuario'))
                ->delete();

            if (DB::getDriverName() === 'pgsql') {
                DB::statement('ALTER TABLE usuario DROP CONSTRAINT IF EXISTS usuario_estado_cuenta_check');
            }

            Schema::table('usuario', function (Blueprint $tabla): void {
                $tabla->dropColumn('estado_cuenta');
            });
        }
    }
};
