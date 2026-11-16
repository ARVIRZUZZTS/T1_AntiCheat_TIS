<?php

/**
 * @file    ExamenDocenteController.php
 *
 * @author  Alex Candia <alex.leonar.candia@gmail.com>
 *
 * @created 2026-11-14
 *
 * @description
 * Endpoint de lectura con los examenes del docente autenticado. El docente sale
 * de la sesion, nunca de un parametro, para que un docente no consulte los
 * examenes de otro.
 *
 * @see  App\Services\Examen\ListarExamenesDocenteService
 */

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Examen\ListarExamenesCursoService;
use App\Services\Examen\ListarExamenesDocenteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExamenDocenteController extends Controller
{
    public function index(
        Request $request,
        ListarExamenesDocenteService $servicio,
    ): JsonResponse {
        $examenes = $servicio->ejecutar((int) $request->user()->id_usuario);

        return response()->json([
            'datos' => array_map(function (array $examen): array {
                $examen['estado'] = match ($examen['estado']) {
                    ListarExamenesCursoService::ESTADO_EN_CURSO => 'En curso',
                    ListarExamenesCursoService::ESTADO_FINALIZADO => 'Finalizado',
                    default => 'Programado',
                };

                return $examen;
            }, $examenes),
        ]);
    }
}
