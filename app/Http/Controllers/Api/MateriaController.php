<?php

/**
 * @file    MateriaController.php
 *
 * @author  Diego Tejerina <josediegotejerinamolina@gmail.com>
 *
 * @created 2026-11-28
 *
 * @description
 * Endpoint del buscador de materias de "Crear curso": agrega una materia que no
 * está en el catálogo (origen "agregada por usuario") y la devuelve para que el
 * buscador la deje seleccionada. Valida nombre (obligatorio), código (opcional,
 * 7 dígitos) y semestre (opcional, 1-12), y rechaza duplicados por código y por
 * nombre normalizado (CrearMateriaService).
 *
 * @changelog
 * - 2026-11-28  [Diego Tejerina]  feat: creación inicial.
 *
 * @see  App\Services\Curso\CrearMateriaService
 */

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Curso\CrearMateriaService;
use App\Support\Validadores;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MateriaController extends Controller
{
    public function store(Request $request, CrearMateriaService $servicio): JsonResponse
    {
        $datos = $request->validate([
            'nombre' => Validadores::reglaMateria(),
            'codigo' => Validadores::reglaCodigoMateria(),
            'semestre' => Validadores::reglaSemestreMateria(),
        ], [
            'nombre.required' => 'Ingrese el nombre de la materia.',
            'nombre.max' => 'El nombre no debe superar los 120 caracteres.',
            'codigo.regex' => 'El codigo debe tener 7 digitos.',
            'semestre.between' => 'El semestre debe estar entre 1 y 12.',
        ]);

        $materia = $servicio->crear(
            $datos['nombre'],
            $datos['codigo'] ?? null,
            isset($datos['semestre']) ? (int) $datos['semestre'] : null,
            (int) $request->user()->id_usuario,
        );

        return response()->json([
            'mensaje' => 'Materia agregada',
            'materia' => [
                'id' => (int) $materia->id_materia,
                'codigo' => $materia->codigo,
                'nombre' => (string) $materia->nombre,
                'semestre' => $materia->semestre !== null ? (int) $materia->semestre : null,
            ],
        ], 201);
    }
}
