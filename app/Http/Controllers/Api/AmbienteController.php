<?php

/**
 * @file    AmbienteController.php
 *
 * @author  Valery D. Ortuno P <valerydariana98@gmail.com>
 *
 * @created 2026-11-19
 *
 * @description
 * Endpoint del selector de ambientes de Crear / Editar examen: `store`
 * agrega un ambiente que no esta en la lista (aula, laboratorio o auditorio)
 * y lo devuelve con el mismo formato del catalogo.
 *
 * Los errores de validacion responden 422 con el mensaje del servicio.
 *
 * @changelog
 * - 2026-11-19  [Valery D. Ortuno P]  feat: creacion inicial.
 * - 2026-11-19  [Valery D. Ortuno P]  refactor: se quita `destroy`; los
 *   ambientes solo se agregan.
 *
 * @see  App\Services\Ambiente\GestionarAmbientesService
 */

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Ambiente\GestionarAmbientesService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AmbienteController extends Controller
{
    public function store(Request $request, GestionarAmbientesService $servicio): JsonResponse
    {
        return response()->json([
            'mensaje' => 'Ambiente agregado',
            'datos' => $servicio->crear($request->input('tipo'), $request->input('nombre')),
        ], 201);
    }
}
