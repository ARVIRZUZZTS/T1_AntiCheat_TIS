<?php

/**
 * @file    2026_11_28_000003_crear_docente_materia.php
 *
 * @author  Diego Tejerina <josediegotejerinamolina@gmail.com>
 *
 * @created 2026-11-28
 *
 * @description
 * Relación docente ↔ materia: las materias que dicta cada docente. El tope de
 * 5 materias por docente se valida en el servidor (DocenteMateriaService), no
 * con un CHECK (un CHECK no puede contar filas de otra). FK a `usuario` y a
 * `materia`; PK compuesta (no se repite la misma materia para un docente).
 *
 * Idempotente (hasTable) y reversible (down borra la tabla). No toca datos.
 *
 * @changelog
 * - 2026-11-28  [Diego Tejerina]  feat: creación de docente_materia.
 *
 * @see  App\Services\Docente\DocenteMateriaService
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('docente_materia')) {
            return;
        }

        Schema::create('docente_materia', function (Blueprint $tabla): void {
            $tabla->unsignedBigInteger('id_docente');
            $tabla->unsignedBigInteger('id_materia');

            $tabla->primary(['id_docente', 'id_materia'], 'docente_materia_pk');
            $tabla->foreign('id_docente', 'docente_materia_docente_fk')
                ->references('id_usuario')->on('usuario')->cascadeOnDelete();
            $tabla->foreign('id_materia', 'docente_materia_materia_fk')
                ->references('id_materia')->on('materia')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('docente_materia');
    }
};
