<?php

/**
 * @file    CrearMateriaService.php
 *
 * @author  Diego Tejerina <josediegotejerinamolina@gmail.com>
 *
 * @created 2026-11-28
 *
 * @description
 * Crea una materia "agregada por usuario" desde Crear curso (cuando la materia
 * escrita no está en el catálogo). Rechaza duplicados por código y por nombre
 * normalizado (sin tildes ni mayúsculas): "Calculo I" y "cálculo i" son la
 * misma materia.
 *
 * @changelog
 * - 2026-11-28  [Diego Tejerina]  feat: creación inicial.
 *
 * @see  App\Models\Materia
 * @see  App\Support\Validadores
 */

namespace App\Services\Curso;

use App\Models\Materia;
use App\Support\Validadores;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CrearMateriaService
{
    public const MENSAJE_DUPLICADA = 'Esta materia ya existe.';

    /**
     * Crea la materia o lanza si ya existe (por código o nombre normalizado).
     *
     * @param  string   $nombre     Nombre de la materia.
     * @param  ?string  $codigo     Código SIS (7 dígitos) o null.
     * @param  ?int     $semestre   Semestre (1-12) o null.
     * @param  ?int     $creadoPor  Usuario que la agrega, o null.
     *
     * @return Materia  La materia creada.
     *
     * @throws ValidationException  Si ya existe una materia con ese código o nombre.
     */
    public function crear(string $nombre, ?string $codigo, ?int $semestre, ?int $creadoPor): Materia
    {
        $nombre = Validadores::normalizarMateria($nombre);
        $codigo = $codigo !== null && trim($codigo) !== '' ? trim($codigo) : null;
        $normalizado = Validadores::normalizarNombreMateria($nombre);

        return DB::transaction(function () use ($nombre, $codigo, $semestre, $creadoPor, $normalizado): Materia {
            DB::statement('LOCK TABLE materia IN SHARE ROW EXCLUSIVE MODE');

            $errores = [];

            if ($codigo !== null && Materia::query()->where('codigo', $codigo)->exists()) {
                $errores['codigo'] = self::MENSAJE_DUPLICADA;
            }

            if (Materia::query()->where('nombre_normalizado', $normalizado)->exists()) {
                $errores['nombre'] = self::MENSAJE_DUPLICADA;
            }

            if ($errores !== []) {
                throw ValidationException::withMessages($errores);
            }

            return Materia::query()->create([
                'codigo' => $codigo,
                'nombre' => $nombre,
                'nombre_normalizado' => $normalizado,
                'semestre' => $semestre,
                'origen' => Materia::ORIGEN_USUARIO,
                'creado_por' => $creadoPor,
                'fecha_creacion' => now(),
            ]);
        });
    }
}
