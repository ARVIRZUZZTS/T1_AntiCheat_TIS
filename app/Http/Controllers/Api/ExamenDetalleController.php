<?php

/**
 * @file    ExamenDetalleController.php
 *
 * @author  Valery D. Ortuno P <valerydariana98@gmail.com>
 *
 * @created 2026-11-19
 *
 * @description
 * Endpoint del modal "Ver detalle" de Ver examenes: devuelve los datos del
 * examen, sus reglas y los materiales permitidos. Es de solo lectura; lo
 * puede ver quien lo creo, el docente de uno de sus cursos o el invitado que
 * acepto (ExamenPolicy::ver).
 *
 * @changelog
 * - 2026-11-19  [Valery D. Ortuno P]  feat: creacion inicial.
 *
 * @see  App\Services\Examen\ObtenerDetalleExamenService
 * @see  App\Policies\ExamenPolicy::ver()
 */

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Examen;
use App\Services\Examen\ObtenerDetalleExamenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ExamenDetalleController extends Controller
{
    public function mostrar(Request $request, Examen $examen, ObtenerDetalleExamenService $servicio): JsonResponse
    {
        Gate::authorize('ver', $examen);

        return response()->json([
            'datos' => $servicio->ejecutar($examen, (int) $request->user()->id_usuario),
        ]);
    }
}
