<?php

/**
 * @file    2026_10_10_000001_reparar_enums_y_personalizadas.php
 *
 * @author  Alex Candia <alex.leonar.candia@gmail.com>
 *
 * @created 2026-10-10
 *
 * @updated 2026-10-10
 *
 * @description
 * Repara lo que quedó a medias en el esquema después de los cambios de enums y de
 * un borrado accidental, que dejaron roto el alta de examen del modal:
 *
 *  1. Recrea `material_personalizado` y `norma_personalizada`, que se perdieron.
 *     Las dos se las escribe `App\Services\Examen\RegistrarExamenService` al
 *     guardar, así que sin ellas el alta fallaba con `42P01 Undefined table`.
 *  2. Devuelve las nueve reglas de `norma_personalizada` que estaban cargadas y
 *     se perdieron con la tabla.
 *  3. Deja `tipo_examen_nombre` con los tres valores vigentes, todo en minúscula
 *     (`examen parcial`, `examen final`, `segunda instancia`), y el catálogo
 *     `tipo_examen` en la misma forma.
 *
 * El tipo se RECREA en vez de usar `ALTER TYPE ... ADD VALUE` porque en
 * PostgreSQL los valores de un ENUM no se pueden quitar: con ADD VALUE el tipo
 * quedaría con los seis valores viejos más los tres nuevos, y el catálogo y la
 * aplicación seguirían viendo tipos que ya no existen. Recrearlo deja el tipo
 * con exactamente los valores que el dominio reconoce. Antes de soltar el tipo
 * se comprueba que ninguna otra columna lo use, y si alguna lo hiciera la
 * migración se detiene con un error claro en vez de romper esa tabla.
 *
 * Es idempotente: se puede correr más de una vez. Si el enum ya está bien, no
 * toca nada; si las tablas ya existen, no las vuelve a crear; y las nueve reglas
 * solo se insertan cuando la tabla quedó vacía.
 *
 * @see  App\Enums\TipoExamen
 * @see  App\Services\Examen\RegistrarExamenService
 *
 * @changelog
 * - 2026-10-10  [Alex Candia]  feat: recrea las tablas de material y normas
 *   personalizados con sus nueve reglas, y normaliza el enum de tipos de examen.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Nombre del tipo ENUM de PostgreSQL con los tipos de examen.
     */
    private const TIPO_EXAMEN = 'tipo_examen_nombre';

    /**
     * Tabla y columna que usan ese ENUM. Si aparece cualquier otra columna
     * usando el tipo, la migración no lo toca: soltar el tipo la dejaría rota.
     */
    private const COLUMNA_DEL_TIPO = ['tipo_examen', 'nombre_tipo_examen'];

    /**
     * Los tres tipos de examen, en el orden de sus id. Todo en minúscula: es la
     * forma en que quedaron las filas del catálogo y la que espera la
     * aplicación (`App\Enums\TipoExamen`).
     *
     * @var list<string>
     */
    private array $tiposExamen = [
        'examen parcial',
        'examen final',
        'segunda instancia',
    ];

    /**
     * Las nueve reglas que había en `norma_personalizada`, recuperadas del
     * volcado previo al borrado de la tabla. Se insertan solo si la tabla
     * recreada queda vacía.
     *
     * @var list<array{0: int, 1: int, 2: string}> id_examen, numero_norma, detalle.
     */
    private array $normasPersonalizadas = [
        [1, 1, 'Se permite el uso de calculadora científica no programable.'],
        [1, 2, 'No se permite hojas adicionales, usar el reverso del examen.'],
        [2, 1, 'El código debe compilar sin errores para ser evaluado.'],
        [2, 2, 'Prohibido el acceso a internet o repositorios externos.'],
        [3, 1, 'Tiempo estricto de 45 minutos. No hay prórroga.'],
        [4, 1, 'Uso obligatorio de bata de laboratorio y gafas de seguridad.'],
        [4, 2, 'Entregar el reporte de datos antes de salir del aula.'],
        [5, 1, 'Responder únicamente con bolígrafo de tinta negra o azul.'],
        [5, 2, 'Desactivar y guardar teléfonos móviles en la mochila.'],
    ];

    /**
     * Repara el esquema.
     *
     * @throws RuntimeException Si el enum está en uso por otra columna o si el
     *                          catálogo tiene tipos que no entran en el tipo nuevo.
     * @throws QueryException Si el esquema no admite las tablas o el tipo.
     */
    public function up(): void
    {
        $this->crearMaterialPersonalizado();
        $this->crearNormaPersonalizada();
        $this->restaurarNormasPersonalizadas();

        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        $this->normalizarTiposExamen();
    }

    /**
     * Revierte la migración.
     *
     * Se van las dos tablas con los datos que se restauraron: no hay forma de
     * deshacer un borrado de tabla sin perder lo que se volvió a escribir.
     * El enum NO se revierte, porque PostgreSQL no permite quitar valores de un
     * ENUM y el tipo nuevo es el que la aplicación espera.
     *
     * @throws QueryException Si no puede soltar las tablas.
     */
    public function down(): void
    {
        Schema::dropIfExists('material_personalizado');
        Schema::dropIfExists('norma_personalizada');
    }

    /**
     * Recrea `material_personalizado` si no está.
     *
     * @throws QueryException Si el esquema no admite la tabla.
     */
    private function crearMaterialPersonalizado(): void
    {
        if (Schema::hasTable('material_personalizado')) {
            return;
        }

        Schema::create('material_personalizado', function (Blueprint $tabla): void {
            $tabla->integer('id_examen')->nullable(false);
            $tabla->integer('numero_material')->nullable(false);
            $tabla->text('descripcion_material')->nullable(false);

            $tabla->index('id_examen');
            $tabla->foreign('id_examen', 'fk_material_personalizado_examen')
                ->references('id_examen')->on('examen');
        });
    }

    /**
     * Recrea `norma_personalizada` si no está.
     *
     * @throws QueryException Si el esquema no admite la tabla.
     */
    private function crearNormaPersonalizada(): void
    {
        if (Schema::hasTable('norma_personalizada')) {
            return;
        }

        Schema::create('norma_personalizada', function (Blueprint $tabla): void {
            $tabla->integer('id_examen')->nullable(false);
            $tabla->integer('numero_norma')->nullable(false);
            $tabla->text('descripcion_norma')->nullable(false);

            $tabla->index('id_examen');
            $tabla->foreign('id_examen', 'fk_norma_personalizada_examen')
                ->references('id_examen')->on('examen');
        });
    }

    /**
     * Devuelve las nueve reglas, solo si la tabla quedó vacía: volver a correr
     * la migración no debe duplicarlas.
     *
     * Se omiten las reglas cuyo examen ya no existe, porque la clave foránea
     * rechazaría el insert.
     */
    private function restaurarNormasPersonalizadas(): void
    {
        if (DB::table('norma_personalizada')->count() > 0) {
            return;
        }

        $idsValidos = DB::table('examen')->pluck('id_examen')->all();

        $filas = array_values(array_filter(
            $this->normasPersonalizadas,
            fn (array $norma): bool => in_array($norma[0], $idsValidos, true)
        ));

        foreach (array_chunk($filas, 50) as $lote) {
            DB::table('norma_personalizada')->insert($lote);
        }
    }

    /**
     * Deja el enum y el catálogo de tipos de examen en minúscula.
     *
     * @throws RuntimeException Si otra columna usa el enum, o si el catálogo
     *                          tiene tipos usados por exámenes que no entran
     *                          en el tipo nuevo.
     * @throws QueryException Si PostgreSQL rechaza el DDL.
     */
    private function normalizarTiposExamen(): void
    {
        if (! Schema::hasTable(self::COLUMNA_DEL_TIPO[0])) {
            throw new RuntimeException('La tabla tipo_examen no existe: esta migración no puede normalizar el enum.');
        }

        if ($this->valoresDelTipo(self::TIPO_EXAMEN) === $this->tiposExamen) {
            return;
        }

        $this->verificarQueSoloLaUsaLaColumnaEsperada();
        $this->soltarElEnumDeLaColumna();
        $this->dejarElCatalogoEnTextoPlano();
        $this->recrearElTipo();
        $this->colgarElEnumDeLaColumna();
    }

    /**
     * Comprueba que el único uso del enum sea la columna del catálogo. Si hay
     * otra, soltar el tipo dejaría esa tabla con un tipo que ya no existe.
     *
     * @throws RuntimeException Si el enum se usa en otra columna.
     */
    private function verificarQueSoloLaUsaLaColumnaEsperada(): void
    {
        $columnas = DB::select(
            'SELECT c.relname AS tabla, a.attname AS columna
               FROM pg_attribute a
               JOIN pg_class c ON c.oid = a.attrelid
               JOIN pg_namespace n ON n.oid = c.relnamespace
               JOIN pg_type t ON t.oid = a.atttypid
               JOIN pg_namespace tn ON tn.oid = t.typnamespace
              WHERE tn.nspname = ? AND t.typname = ? AND a.attnum > 0 AND NOT a.attisdropped',
            ['public', self::TIPO_EXAMEN]
        );

        $enUso = array_map(
            fn (object $columna): array => [(string) $columna->tabla, (string) $columna->columna],
            $columnas
        );

        if ($enUso !== [self::COLUMNA_DEL_TIPO]) {
            throw new RuntimeException(sprintf(
                'El enum %s también lo usa %s. Revisar a mano antes de recrearlo.',
                self::TIPO_EXAMEN,
                json_encode($enUso, JSON_UNESCAPED_UNICODE)
            ));
        }
    }

    /**
     * Pasa la columna a texto: es lo que permite soltar y rehacer el tipo.
     *
     * @throws QueryException Si PostgreSQL rechaza el DDL.
     */
    private function soltarElEnumDeLaColumna(): void
    {
        DB::statement(
            'ALTER TABLE '.self::COLUMNA_DEL_TIPO[0].' ALTER COLUMN '.self::COLUMNA_DEL_TIPO[1]
            .' TYPE text USING '.self::COLUMNA_DEL_TIPO[1].'::text'
        );
    }

    /**
     * Fija el catálogo por id, en minúscula, todavía como texto.
     *
     * No se copia el valor viejo y se pasa a minúscula: se escribe el nombre
     * canónico de cada id, para que el catálogo no dependa de cómo estaba escrito.
     * Las filas que se quedan fuera del tipo nuevo se borran si ningún examen las
     * usa; si algún examen las usa, la migración se detiene y avisa en vez de dejar
     * el catálogo a medias.
     *
     * @throws RuntimeException Si un tipo sobrante está en uso por un examen.
     */
    private function dejarElCatalogoEnTextoPlano(): void
    {
        foreach ($this->tiposExamen as $indice => $nombre) {
            $id = $indice + 1;

            if (DB::table('tipo_examen')->where('id_tipo_examen', $id)->exists()) {
                DB::table('tipo_examen')->where('id_tipo_examen', $id)
                    ->update(['nombre_tipo_examen' => $nombre]);

                continue;
            }

            DB::table('tipo_examen')->insert([
                'id_tipo_examen' => $id,
                'nombre_tipo_examen' => $nombre,
            ]);
        }

        $sobrantes = DB::table('tipo_examen')
            ->whereNotIn('id_tipo_examen', array_keys($this->tiposExamen))
            ->pluck('id_tipo_examen');

        foreach ($sobrantes as $idSobrante) {
            if (DB::table('examen')->where('tipo_examen', $idSobrante)->exists()) {
                throw new RuntimeException(sprintf(
                    'El tipo de examen %d está en uso por algún examen y no entra en el tipo nuevo. '
                    .'Revisar esos exámenes antes de correr la migración.',
                    $idSobrante
                ));
            }

            DB::table('tipo_examen')->where('id_tipo_examen', $idSobrante)->delete();
        }
    }

    /**
     * Reemplaza el tipo por uno con los tres valores en minúscula.
     *
     * @throws QueryException Si PostgreSQL rechaza el DDL.
     */
    private function recrearElTipo(): void
    {
        $valores = implode(', ', array_map(
            fn (string $valor): string => "'".str_replace("'", "''", $valor)."'",
            $this->tiposExamen
        ));

        DB::statement('DROP TYPE IF EXISTS public.'.self::TIPO_EXAMEN);
        DB::statement('CREATE TYPE public.'.self::TIPO_EXAMEN.' AS ENUM ('.$valores.')');
    }

    /**
     * Vuelve a colgar el enum en la columna del catálogo.
     *
     * @throws QueryException Si algún valor del catálogo no está en el tipo.
     */
    private function colgarElEnumDeLaColumna(): void
    {
        DB::statement(
            'ALTER TABLE '.self::COLUMNA_DEL_TIPO[0].' ALTER COLUMN '.self::COLUMNA_DEL_TIPO[1]
            .' TYPE public.'.self::TIPO_EXAMEN
            .' USING '.self::COLUMNA_DEL_TIPO[1].'::text::public.'.self::TIPO_EXAMEN
        );
    }

    /**
     * Valores de un ENUM de PostgreSQL, en el orden del tipo, o null si el tipo
     * no existe.
     *
     * @return list<string>|null
     */
    private function valoresDelTipo(string $nombre): ?array
    {
        $filas = DB::select(
            'SELECT e.enumlabel AS etiqueta
               FROM pg_type t
               JOIN pg_enum e ON e.enumtypid = t.oid
               JOIN pg_namespace n ON n.oid = t.typnamespace
              WHERE n.nspname = ? AND t.typname = ?
              ORDER BY e.enumsortorder',
            ['public', $nombre]
        );

        if ($filas === []) {
            return null;
        }

        return array_map(fn (object $fila): string => (string) $fila->etiqueta, $filas);
    }
};