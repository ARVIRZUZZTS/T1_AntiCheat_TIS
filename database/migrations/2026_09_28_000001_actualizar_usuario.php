<?php

/**
 * @file    2026_09_28_000001_actualizar_usuario.php
 *
 * @author  T1 - Tech One SRL
 *
 * @created 2026-09-28
 *
 * @updated 2026-09-28
 *
 * @description
 * Ajustes de esquema posteriores a `2026_09_27_000001_migracion_servidor_oficial`.
 *
 * Esa migración ya corrió en las bases que estaban inicializadas, así que
 * editarla no las actualiza: por eso este archivo aparte. Es la versión
 * ejecutable de `database/sql/004_actualizar_usuario.sql`, y hace lo mismo:
 *
 * 1. `central_riesgo.estado_incidencia`: `App\Models\CentralRiesgo` la exige
 *    (NOT NULL) pero la base no la tenía. Sin esta columna, registrar una
 *    incidencia falla con "column does not exist".
 * 2. `usuario.contraseña` -> `usuario.password`: el nombre con ENIE se
 *    rompe en consolas Windows que no usan UTF-8
 *    (`ERROR: secuencia de bytes no válida para codificación «UTF8»`), y
 *    `password` es el nombre que Laravel ya asume por defecto. Con esto
 *    `usuario` queda lista para ser la tabla de autenticacion
 *    (ver `config/auth.php`, que apunta a `App\Models\Usuario`).
 * 3. `central_riesgo.fecha_registro` a `date`: la migración inicial la había
 *    creado como `timestamp`, divergiendo del `001_schema.sql` oficial y de
 *    la base del servidor. Se corrige a `date`, que es lo que se formatea en
 *    la interfaz (`ConsultarEstadoEstudianteService` usa `d/m/Y`).
 *
 * Todo es idempotente: se puede correr contra una base recién creada por la
 * migración anterior, contra una base cargada a mano con los `.sql`, o las
 * veces que haga falta.
 *
 * @changelog
 * - 2026-09-28  [T1]  fix: agregar `central_riesgo.estado_incidencia`.
 * - 2026-09-28  [T1]  fix: renombrar `usuario.contraseña` a `usuario.password`.
 * - 2026-09-28  [T1]  fix: `central_riesgo.fecha_registro` a `date`.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->agregarEstadoIncidencia();
        $this->renombrarColumnaPassword();
        $this->corregirFechaRegistro();
    }

    public function down(): void
    {
        // Volver `password` a `contraseña` es posible, pero no deshace el
        // hash: las claves quedan hasheadas igual. Se deja el rename sin
        // reversa a proposito para no fingir un rollback que no es real.
        $this->corregirFechaRegistro();
    }

    /**
     * Crea el ENUM y la columna `estado_incidencia` si falta.
     *
     * La columna es NOT NULL y la tabla puede tener filas, asi que se
     * agrega con DEFAULT y despues se saca el DEFAULT: las inserciones
     * nuevas tienen que declararla, igual que en el esquema oficial.
     */
    private function agregarEstadoIncidencia(): void
    {
        $existe = DB::selectOne(
            "SELECT 1 FROM pg_type WHERE typname = 'estado_incidencia' LIMIT 1"
        );

        if (! $existe) {
            DB::statement(
                "CREATE TYPE estado_incidencia AS ENUM ('Confirmado', 'Pendiente')"
            );
        }

        if (! Schema::hasColumn('central_riesgo', 'estado_incidencia')) {
            DB::statement(
                'ALTER TABLE central_riesgo
                 ADD COLUMN estado_incidencia estado_incidencia NOT NULL DEFAULT \'Pendiente\''
            );
        }

        DB::statement(
            'ALTER TABLE central_riesgo ALTER COLUMN estado_incidencia DROP DEFAULT'
        );
    }

    /** Renombra `usuario.contraseña` a `usuario.password` si todavía existe. */
    private function renombrarColumnaPassword(): void
    {
        if (! Schema::hasColumn('usuario', 'contraseña')) {
            return;
        }

        if (Schema::hasColumn('usuario', 'password')) {
            return;
        }

        DB::statement('ALTER TABLE usuario RENAME COLUMN "contraseña" TO password');
    }

    /**
     * Deja `fecha_registro` como `date`, que es lo que dice el esquema
     * oficial y lo que espera la interfaz.
     */
    private function corregirFechaRegistro(): void
    {
        $tipo = DB::selectOne(
            "SELECT data_type FROM information_schema.columns
             WHERE table_schema = 'public' AND table_name = 'central_riesgo'
               AND column_name = 'fecha_registro'"
        );

        if (! $tipo || $tipo->data_type === 'date') {
            return;
        }

        DB::statement(
            'ALTER TABLE central_riesgo
             ALTER COLUMN fecha_registro TYPE date USING fecha_registro::date'
        );
    }
};
