<?php

/**
 * @file    ExamenEdicionController.php
 *
 * @author  Alex Candia <alex.leonar.candia@gmail.com>
 *
 * @created 2026-11-05
 *
 * @description
 * Endpoint de lectura que entrega a la pestana Editar Examen los datos base de
 * un examen ya formateados para el formulario. Solo el creador del examen puede
 * consultarlos; el listado de la pestana ya oculta los examenes ajenos.
 *
 * @see  App\Services\Examen\ObtenerExamenEdicionService
 * @see  App\Policies\ExamenPolicy::editar()
 */

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Examen;
use App\Services\Examen\ObtenerExamenEdicionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class ExamenEdicionController extends Controller
{
    public function mostrar(Examen $examen, ObtenerExamenEdicionService $servicio): JsonResponse
    {
        Gate::authorize('editar', $examen);

        return response()->json([
            'datos' => $servicio->ejecutar($examen),
        ]);
    }
}
