<?php

/**
 * @file    2026_11_28_000001_quitar_estado_cancelado_de_invitaciones.php
 *
 * @author  Valery D. Ortuno P <valerydariana98@gmail.com>
 *
 * @created 2026-11-28
 *
 * @description
 * Quita el estado `cancelado` de las invitaciones a examenes compartidos (HU
 * 131): una invitacion enviada ya no se cancela, queda hasta que el invitado
 * la acepte o la rechace.
 *
 *  1. Borra las invitaciones canceladas y sus avisos (`notificacion_docente`
 *     tiene una clave foranea a la invitacion). Nunca llegaron a valer.
 *  2. En Postgres el estado es el enum `invitacion_estado`, y un valor de un
 *     enum no se puede quitar: se crea el tipo nuevo sin `cancelado`, la
 *     columna pasa al tipo nuevo y se borra el viejo. El indice parcial de
 *     invitaciones activas depende del tipo, asi que se rehace.
 *
 * `down()` vuelve a agregar el valor al enum, pero las invitaciones borradas
 * no se recuperan.
 *
 * @changelog
 * - 2026-11-28  [Valery D. Ortuno P]  feat: creacion inicial.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const INDICE_ACTIVAS = 'invitacion_examen_activa_unique';

    public function up(): void
    {
        $canceladas = DB::table('invitacion_examen_compartido')->where('estado', 'cancelado')->select('id_invitacion');

        DB::table('notificacion_docente')->whereIn('id_invitacion', $canceladas)->delete();
        DB::table('invitacion_examen_compartido')->where('estado', 'cancelado')->delete();

        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('DROP INDEX IF EXISTS '.self::INDICE_ACTIVAS);
        DB::statement('ALTER TYPE invitacion_estado RENAME TO invitacion_estado_viejo');
        DB::statement("CREATE TYPE invitacion_estado AS ENUM ('aceptado', 'rechazado', 'pendiente')");
        DB::statement('ALTER TABLE invitacion_examen_compartido ALTER COLUMN estado TYPE invitacion_estado USING estado::text::invitacion_estado');
        DB::statement('DROP TYPE invitacion_estado_viejo');
        DB::statement('CREATE UNIQUE INDEX '.self::INDICE_ACTIVAS.' ON invitacion_examen_compartido (id_examen, id_docente_invitado)'
            ." WHERE estado IN ('pendiente', 'aceptado')");
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TYPE invitacion_estado ADD VALUE IF NOT EXISTS 'cancelado'");
        }
    }
};
