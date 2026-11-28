<?php

/**
 * @file    2026_11_27_000001_agregar_campos_auxiliar_a_usuario.php
 *
 * @author  Diego Tejerina <josediegotejerinamolina@gmail.com>
 *
 * @created 2026-11-27
 *
 * @description
 * Campos que el nuevo alcance de auxiliares necesita en `usuario`:
 *
 *  - `carnet`: carnet de identidad (CI) opcional de un auxiliar externo; único
 *    cuando está presente (índice parcial).
 *  - `tipo_auxiliar`: 'UMSS' (tiene código SIS) o 'externo' (sin SIS). Null en
 *    las cuentas que no son auxiliares.
 *  - `puede_crear_auxiliares`: permiso de coordinador (solo lo otorga un
 *    docente). Por defecto false.
 *  - `id_docente_responsable`: docente al que queda relacionado el auxiliar.
 *  - `id_creador`: quién creó la cuenta (la fecha es `fecha_creacion`).
 *
 * A los auxiliares que ya existen se les marca `tipo_auxiliar = 'UMSS'` (tienen
 * código SIS) y se les completa, cuando se puede, el docente responsable a
 * partir del curso que tienen asignado. No se cambia su `cod_sis` ni su acceso.
 *
 * Idempotente: se puede correr varias veces. Pensada para PostgreSQL, que es la
 * base del proyecto y la de las pruebas.
 *
 * @changelog
 * - 2026-11-27  [Diego Tejerina]  feat: carnet, tipo de auxiliar, permiso de
 *   coordinador, docente responsable y creador en `usuario`.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const INDICE_CARNET = 'usuario_carnet_unico';

    private const CHECK_TIPO = 'usuario_tipo_auxiliar_check';

    public function up(): void
    {
        Schema::table('usuario', function (Blueprint $tabla): void {
            if (! Schema::hasColumn('usuario', 'carnet')) {
                $tabla->string('carnet', 10)->nullable();
            }

            if (! Schema::hasColumn('usuario', 'tipo_auxiliar')) {
                $tabla->string('tipo_auxiliar', 20)->nullable();
            }

            if (! Schema::hasColumn('usuario', 'puede_crear_auxiliares')) {
                $tabla->boolean('puede_crear_auxiliares')->default(false);
            }

            if (! Schema::hasColumn('usuario', 'id_docente_responsable')) {
                $tabla->integer('id_docente_responsable')->nullable();
            }

            if (! Schema::hasColumn('usuario', 'id_creador')) {
                $tabla->integer('id_creador')->nullable();
            }
        });

        // Las cuentas creadas antes de esta columna no pueden quedar en NULL:
        // el permiso es un no por defecto.
        DB::table('usuario')->whereNull('puede_crear_auxiliares')->update(['puede_crear_auxiliares' => false]);

        $this->agregarIndiceCarnet();
        $this->agregarCheckTipoAuxiliar();
        $this->agregarLlaves();
        $this->marcarAuxiliaresExistentes();
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE usuario DROP CONSTRAINT IF EXISTS usuario_docente_responsable_fk');
        DB::statement('ALTER TABLE usuario DROP CONSTRAINT IF EXISTS usuario_creador_fk');
        DB::statement('ALTER TABLE usuario DROP CONSTRAINT IF EXISTS '.self::CHECK_TIPO);
        DB::statement('DROP INDEX IF EXISTS '.self::INDICE_CARNET);

        Schema::table('usuario', function (Blueprint $tabla): void {
            foreach (['id_creador', 'id_docente_responsable', 'puede_crear_auxiliares', 'tipo_auxiliar', 'carnet'] as $columna) {
                if (Schema::hasColumn('usuario', $columna)) {
                    $tabla->dropColumn($columna);
                }
            }
        });
    }

    /** El carnet es único solo cuando está presente (los externos pueden no tenerlo). */
    private function agregarIndiceCarnet(): void
    {
        DB::statement(
            'CREATE UNIQUE INDEX IF NOT EXISTS '.self::INDICE_CARNET
            .' ON usuario (carnet) WHERE carnet IS NOT NULL'
        );
    }

    private function agregarCheckTipoAuxiliar(): void
    {
        if ($this->existeConstraint(self::CHECK_TIPO)) {
            return;
        }

        DB::statement(
            'ALTER TABLE usuario ADD CONSTRAINT '.self::CHECK_TIPO
            ." CHECK (tipo_auxiliar IS NULL OR tipo_auxiliar IN ('UMSS', 'externo'))"
        );
    }

    private function agregarLlaves(): void
    {
        if (! $this->existeConstraint('usuario_docente_responsable_fk')) {
            DB::statement(
                'ALTER TABLE usuario ADD CONSTRAINT usuario_docente_responsable_fk'
                .' FOREIGN KEY (id_docente_responsable) REFERENCES usuario(id_usuario) ON DELETE SET NULL'
            );
        }

        if (! $this->existeConstraint('usuario_creador_fk')) {
            DB::statement(
                'ALTER TABLE usuario ADD CONSTRAINT usuario_creador_fk'
                .' FOREIGN KEY (id_creador) REFERENCES usuario(id_usuario) ON DELETE SET NULL'
            );
        }
    }

    /**
     * Marca a los auxiliares ya existentes como 'UMSS' (tienen código SIS) y, si
     * se puede, les completa el docente responsable desde el curso asignado.
     */
    private function marcarAuxiliaresExistentes(): void
    {
        DB::statement(
            "UPDATE usuario SET tipo_auxiliar = 'UMSS'"
            .' WHERE tipo_auxiliar IS NULL AND id_usuario IN ('
            .' SELECT ru.id_usuario FROM rol_usuario ru JOIN rol r ON r.id_rol = ru.id_rol'
            ." WHERE r.nombre_rol = 'auxiliar')"
        );

        DB::statement(
            'UPDATE usuario u SET id_docente_responsable = sub.sis_doc FROM ('
            .' SELECT ac.id_auxiliar, MIN(c.sis_doc) AS sis_doc FROM auxiliar_curso ac'
            .' JOIN curso c ON c.id_curso = ac.id_curso GROUP BY ac.id_auxiliar'
            .' ) sub WHERE u.id_usuario = sub.id_auxiliar AND u.id_docente_responsable IS NULL'
        );
    }

    private function existeConstraint(string $nombre): bool
    {
        return DB::selectOne(
            'SELECT 1 FROM pg_constraint WHERE conname = ? LIMIT 1',
            [$nombre]
        ) !== null;
    }
};
