<?php

/**
 * @file    GestionarAmbientesService.php
 *
 * @author  Valery D. Ortuno P <valerydariana98@gmail.com>
 *
 * @created 2026-11-19
 *
 * @description
 * Alta de ambientes desde el selector de Crear / Editar examen: `crear` arma
 * el nombre con el tipo y el identificador ("Aula 625", "Laboratorio 3",
 * "Auditorio Central"), igual que los ambientes que ya estan en la tabla, y
 * rechaza uno que ya exista aunque este escrito distinto: se comparan por
 * `clave` (minusculas, sin acentos, sin espacios ni guiones y sin ceros a la
 * izquierda), asi "Aula 692 A", "aula 692-a" y "Aula 692A" son el mismo. El
 * formulario usa la misma regla para avisar antes de enviar.
 *
 * El tipo va dentro del nombre, como en los datos existentes, asi que no hace
 * falta columna nueva. Tras el alta se olvida el catalogo cacheado de
 * CatalogosExamenService para que el formulario y su validacion lo vean.
 *
 * @changelog
 * - 2026-11-19  [Valery D. Ortuno P]  feat: creacion inicial.
 * - 2026-11-19  [Valery D. Ortuno P]  refactor: se quita `eliminar`; los
 *   ambientes solo se agregan.
 * - 2026-11-20  [Valery D. Ortuno P]  fix: el duplicado se detecta aunque cambien
 *   espacios, guiones, acentos o ceros a la izquierda, y el aviso dice cual es
 *   el ambiente que ya existe.
 *
 * @see  App\Http\Controllers\Api\AmbienteController
 * @see  App\Services\Ambiente\ListarAmbientesService
 */

namespace App\Services\Ambiente;

use App\Services\Examen\CatalogosExamenService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class GestionarAmbientesService
{
    /** Tipos de ambiente: clave del formulario => palabra con la que empieza el nombre. */
    public const TIPOS = [
        'aula' => 'Aula',
        'laboratorio' => 'Laboratorio',
        'auditorio' => 'Auditorio',
    ];

    /** Largo de `ambiente.nombre_ambiente` (varchar 50). */
    public const NOMBRE_MAXIMO = 50;

    public const MENSAJE_TIPO = 'Elige si es aula, laboratorio o auditorio.';

    public const MENSAJE_IDENTIFICADOR = 'Escribe el numero o nombre del ambiente.';

    public const MENSAJE_CARACTERES = 'Usa solo letras, numeros, espacios o guiones.';

    public const MENSAJE_LARGO = 'El nombre del ambiente admite hasta 50 caracteres.';

    public const MENSAJE_DUPLICADO = 'Ese ambiente ya existe';

    /**
     * @return array{id: int, nombre: string, termino: string}
     *
     * @throws ValidationException Si el tipo o el identificador no son validos o ya existe.
     */
    public function crear(mixed $tipo, mixed $identificador): array
    {
        $tipo = is_string($tipo) ? mb_strtolower(trim($tipo)) : '';
        $identificador = is_scalar($identificador) ? (string) preg_replace('/\s+/u', ' ', trim((string) $identificador)) : '';

        if (! isset(self::TIPOS[$tipo])) {
            throw ValidationException::withMessages(['tipo' => self::MENSAJE_TIPO]);
        }

        if ($identificador === '') {
            throw ValidationException::withMessages(['nombre' => self::MENSAJE_IDENTIFICADOR]);
        }

        if (! preg_match('/^[\pL\pN][\pL\pN \-]*$/u', $identificador)) {
            throw ValidationException::withMessages(['nombre' => self::MENSAJE_CARACTERES]);
        }

        $nombre = self::TIPOS[$tipo].' '.$identificador;

        if (mb_strlen($nombre) > self::NOMBRE_MAXIMO) {
            throw ValidationException::withMessages(['nombre' => self::MENSAJE_LARGO]);
        }

        $id = DB::transaction(function () use ($nombre): int {
            // Serializa las altas: el id es max + 1 y el nombre no debe repetirse.
            if (DB::getDriverName() === 'pgsql') {
                DB::statement('LOCK TABLE ambiente IN SHARE ROW EXCLUSIVE MODE');
            }

            // El catalogo es chico: se compara en PHP con la misma clave que
            // usa el formulario (SQLite y Postgres no normalizan igual).
            $clave = self::clave($nombre);
            $existente = DB::table('ambiente')
                ->pluck('nombre_ambiente')
                ->first(fn (string $registrado): bool => self::clave($registrado) === $clave);

            if ($existente !== null) {
                throw ValidationException::withMessages([
                    'nombre' => self::MENSAJE_DUPLICADO.': '.trim($existente).'.',
                ]);
            }

            $id = (int) DB::table('ambiente')->max('id_ambiente') + 1;
            DB::table('ambiente')->insert(['id_ambiente' => $id, 'nombre_ambiente' => $nombre]);

            return $id;
        });

        $this->olvidarCatalogo();

        return ['id' => $id, 'nombre' => $nombre, 'termino' => mb_strtolower($nombre)];
    }

    /**
     * Clave para comparar nombres de ambiente: minusculas, sin acentos, sin
     * espacios ni guiones y sin ceros a la izquierda en los numeros.
     * "Aula 0692 - A" y "aula 692a" dan la misma clave.
     */
    public static function clave(string $nombre): string
    {
        $texto = mb_strtolower(Str::ascii($nombre));
        $texto = (string) preg_replace('/[\s\-]+/u', '', $texto);

        return (string) preg_replace_callback('/\d+/', fn (array $numero): string => ltrim($numero[0], '0') ?: '0', $texto);
    }

    private function olvidarCatalogo(): void
    {
        Cache::forget(CatalogosExamenService::CACHE_PREFIJO.'ambientes');
    }
}
