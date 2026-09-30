<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Curso\ListarEstudiantesDeCursoService;
use Illuminate\Http\JsonResponse;

class CursoEstudianteController extends Controller
{
    public function index(
        int $idCurso,
        ListarEstudiantesDeCursoService $servicio
    ): JsonResponse {
        $estudiantes = $servicio->ejecutar($idCurso);

        return response()->json([
            'datos' => $estudiantes->map(fn ($estudiante) => [
                'sis'      => $estudiante->sis_estudiante,
                'nombre'   => $estudiante->nombre_estudiante,
                'apellido' => $estudiante->apellido_estudiante,
                'carrera'  => $estudiante->carrera
            ]),
        ]);
    }
}