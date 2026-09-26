<?php

namespace App\Http\Controllers\Api;

use App\Actions\RegistrarAsistencia;
use App\Http\Controllers\Controller;
use App\Http\Requests\RegistrarAsistenciaRequest;
use Illuminate\Http\JsonResponse;

class AsistenciaController extends Controller
{
    public function store(
        RegistrarAsistenciaRequest $request,
        int $idExamen,
        RegistrarAsistencia $action,
    ): JsonResponse {
        $registro = $action($idExamen, $request->validated()['sis_estudiante']);

        return response()->json([
            'datos' => [
                'id_ingreso'   => $registro->id_ingreso,
                'hora_ingreso' => $registro->hora_ingreso,
            ],
        ], 201);
    }
}