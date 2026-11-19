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
 * rechaza uno que ya exista (sin distinguir mayusculas ni espacios de mas).
 *
 * El tipo va dentro del nombre, como en los datos existentes, asi que no hace
 * falta columna nueva. Tras el alta se olvida el catalogo cacheado de
 * CatalogosExamenService para que el formulario y su validacion lo vean.
 *
 * @changelog
 * - 2026-11-19  [Valery D. Ortuno P]  feat: creacion inicial.
 * - 2026-11-19  [Valery D. Ortuno P]  refactor: se quita `eliminar`; los
 *   ambientes solo se agregan.
 *
 * @see  App\Http\Controllers\Api\AmbienteController
 * @see  App\Services\Ambiente\ListarAmbientesService
 */

namespace App\Services\Ambiente;

use App\Services\Examen\CatalogosExamenService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
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

    public const MENSAJE_DUPLICADO = 'Ese ambiente ya existe.';

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

            $existe = DB::table('ambiente')
                ->whereRaw('lower(trim(nombre_ambiente)) = lower(?)', [$nombre])
                ->exists();

            if ($existe) {
                throw ValidationException::withMessages(['nombre' => self::MENSAJE_DUPLICADO]);
            }

            $id = (int) DB::table('ambiente')->max('id_ambiente') + 1;
            DB::table('ambiente')->insert(['id_ambiente' => $id, 'nombre_ambiente' => $nombre]);

            return $id;
        });

        $this->olvidarCatalogo();

        return ['id' => $id, 'nombre' => $nombre, 'termino' => mb_strtolower($nombre)];
    }

    private function olvidarCatalogo(): void
    {
        Cache::forget(CatalogosExamenService::CACHE_PREFIJO.'ambientes');
    }
}
