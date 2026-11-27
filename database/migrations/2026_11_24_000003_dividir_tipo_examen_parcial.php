<?php

/**
 * @file    2026_11_24_000003_dividir_tipo_examen_parcial.php
 *
 * @author  T1 - Tech One SRL
 *
 * @created 2026-11-24
 *
 * @description Divide el tipo `examen parcial` en `examen primer parcial`,
 * `examen segundo parcial` y `evaluacion corriente`. Los exámenes que ya tenían
 * el tipo parcial pasan a ser de primer parcial; los dos tipos nuevos reciben
 * los ids 4 y 5. En PostgreSQL se recrea el enum `tipo_examen_nombre` porque no
 * se pueden quitar valores con ALTER TYPE, y se comprueba antes que el tipo no
 * esté usado por otra columna.
 *
 * @changelog
 * - 2026-11-24  [Equipo T1]  feat: catálogo de tipos de examen con primer
 *   parcial, segundo parcial y evaluación corriente.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const TIPO_ENUM = 'tipo_examen_nombre';

    private const TABLA = 'tipo_examen';

    private const COLUMNA = 'nombre_tipo_examen';

    /** @var array<int, string> */
    private array $tipos = [
        1 => 'examen primer parcial',
        2 => 'examen final',
        3 => 'segunda instancia',
        4 => 'examen segundo parcial',
        5 => 'evaluacion corriente',
    ];

    /** @var array<int, string> */
    private array $tiposAnteriores = [
        1 => 'examen parcial',
        2 => 'examen final',
        3 => 'segunda instancia',
    ];

    /**
     * @throws RuntimeException Si el enum está en uso por otra columna.
     * @throws QueryException Si PostgreSQL rechaza el DDL.
     */
    public function up(): void
    {
        $pgsql = DB::getDriverName() === 'pgsql';

        if ($pgsql) {
            $this->verificarUsoDelEnum();
            $this->columnaATexto();
        }

        $this->fijarCatalogo($this->tipos);

        if ($pgsql) {
            $this->recrearEnum($this->tipos);
            $this->columnaAEnum();
        }
    }

    /**
     * @throws RuntimeException Si el enum está en uso por otra columna.
     * @throws QueryException Si PostgreSQL rechaza el DDL.
     */
    public function down(): void
    {
        $pgsql = DB::getDriverName() === 'pgsql';

        if ($pgsql) {
            $this->verificarUsoDelEnum();
            $this->columnaATexto();
        }

        $this->fijarCatalogo($this->tiposAnteriores, true);

        if ($pgsql) {
            $this->recrearEnum($this->tiposAnteriores);
            $this->columnaAEnum();
        }
    }

    /** @param array<int, string> $tipos */
    private function fijarCatalogo(array $tipos, bool $revertir = false): void
    {
        if ($revertir) {
            DB::table('examen')->whereIn('tipo_examen', [4, 5])->update(['tipo_examen' => 1]);
        }

        foreach ($tipos as $id => $nombre) {
            if (DB::table(self::TABLA)->where('id_tipo_examen', $id)->exists()) {
                DB::table(self::TABLA)->where('id_tipo_examen', $id)
                    ->update([self::COLUMNA => $nombre]);

                continue;
            }

            DB::table(self::TABLA)->insert([
                'id_tipo_examen' => $id,
                self::COLUMNA => $nombre,
            ]);
        }

        $sobrantes = DB::table(self::TABLA)
            ->whereNotIn('id_tipo_examen', array_keys($tipos))
            ->pluck('id_tipo_examen');

        foreach ($sobrantes as $idSobrante) {
            if (DB::table('examen')->where('tipo_examen', $idSobrante)->exists()) {
                throw new RuntimeException(sprintf(
                    'El tipo de examen %d está en uso y no entra en el catálogo nuevo.',
                    $idSobrante
                ));
            }

            DB::table(self::TABLA)->where('id_tipo_examen', $idSobrante)->delete();
        }
    }

    /** @throws RuntimeException Si el enum está en uso por otra columna. */
    private function verificarUsoDelEnum(): void
    {
        $columnas = DB::select(
            'SELECT c.relname AS tabla, a.attname AS columna
               FROM pg_attribute a
               JOIN pg_class c ON c.oid = a.attrelid
               JOIN pg_namespace n ON n.oid = c.relnamespace
               JOIN pg_type t ON t.oid = a.atttypid
               JOIN pg_namespace tn ON tn.oid = t.typnamespace
              WHERE tn.nspname = ? AND t.typname = ? AND a.attnum > 0 AND NOT a.attisdropped',
            ['public', self::TIPO_ENUM]
        );

        $enUso = array_map(
            fn (object $columna): array => [(string) $columna->tabla, (string) $columna->columna],
            $columnas
        );

        if ($enUso !== [[self::TABLA, self::COLUMNA]]) {
            throw new RuntimeException(sprintf(
                'El enum %s también lo usa %s. Revisar a mano antes de recrearlo.',
                self::TIPO_ENUM,
                json_encode($enUso, JSON_UNESCAPED_UNICODE)
            ));
        }
    }

    /** @throws QueryException Si PostgreSQL rechaza el DDL. */
    private function columnaATexto(): void
    {
        DB::statement(
            'ALTER TABLE '.self::TABLA.' ALTER COLUMN '.self::COLUMNA
            .' TYPE text USING '.self::COLUMNA.'::text'
        );
    }

    /**
     * @param  array<int, string>  $tipos
     *
     * @throws QueryException Si PostgreSQL rechaza el DDL.
     */
    private function recrearEnum(array $tipos): void
    {
        $valores = implode(', ', array_map(
            fn (string $valor): string => "'".str_replace("'", "''", $valor)."'",
            array_values($tipos)
        ));

        DB::statement('DROP TYPE IF EXISTS public.'.self::TIPO_ENUM);
        DB::statement('CREATE TYPE public.'.self::TIPO_ENUM.' AS ENUM ('.$valores.')');
    }

    /** @throws QueryException Si PostgreSQL rechaza el DDL. */
    private function columnaAEnum(): void
    {
        DB::statement(
            'ALTER TABLE '.self::TABLA.' ALTER COLUMN '.self::COLUMNA
            .' TYPE public.'.self::TIPO_ENUM.' USING '.self::COLUMNA.'::public.'.self::TIPO_ENUM
        );
    }
};
