<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Curso\ListarEstudiantesDeCursoService;
use Illuminate\Http\JsonResponse;

class CursoEstudianteController extends Controller
{
    public function index(
        int $idCurso,
        ListarEstudiantesDeCursoService $service
    ): JsonResponse {
        $estudiantes = $service->ejecutar($idCurso);

        return response()->json([
            'data' => $estudiantes->map(fn ($e) => [
                'sis'      => $e->sis_estudiante,
                'nombre'   => $e->nombre_estudiante,
                'apellido' => $e->apellido_estudiante,
                'carrera'  => $e->carrera,
            ]),
        ]);
    }
}