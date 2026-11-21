<?php

/**
 * @file    2026_11_20_000001_agregar_archivado_a_curso_estado.php
 *
 * @author  Equipo T1 <tis@umss.edu.bo>
 *
 * @created 2026-11-20
 *
 * @description
 * Agrega el valor `Archivado` al enum de PostgreSQL `curso_estado` (HU #321,
 * Task #323). `Archivado` es un tercer estado independiente de `EnCurso` y
 * `Finalizo`: no altera la lógica existente de finalización por fecha.
 *
 * La operación es idempotente y solo aplica en PostgreSQL/Supabase, donde el
 * tipo `curso_estado` ya existe. `ALTER TYPE ... ADD VALUE` no puede ejecutarse
 * dentro de un bloque transaccional, por eso la migración corre fuera de
 * transacción.
 *
 * @changelog
 * - 2026-11-20  [Equipo T1]  feat: alta del estado `Archivado` en `curso_estado`.
 *
 * @see  App\Enums\CursoEstado
 * @see  App\Models\Curso
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * ALTER TYPE ... ADD VALUE no puede ejecutarse dentro de una transacción.
     */
    public $withinTransaction = false;

    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        $tipoExiste = DB::selectOne(
            "SELECT 1 FROM pg_type WHERE typname = 'curso_estado' LIMIT 1"
        );

        if (! $tipoExiste) {
            return;
        }

        $valorExiste = DB::selectOne(
            "SELECT 1
             FROM pg_enum e
             JOIN pg_type t ON t.oid = e.enumtypid
             WHERE t.typname = 'curso_estado' AND e.enumlabel = 'Archivado'
             LIMIT 1"
        );

        if ($valorExiste) {
            return;
        }

        DB::statement("ALTER TYPE curso_estado ADD VALUE IF NOT EXISTS 'Archivado'");
    }

    public function down(): void
    {
        // PostgreSQL no permite quitar valores de un ENUM sin recrearlo.
    }
};
