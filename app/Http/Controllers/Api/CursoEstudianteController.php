<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Curso;
use App\Services\Curso\ListarEstudiantesDeCursoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class CursoEstudianteController extends Controller
{
    public function index(
        int $idCurso,
        Request $request,
        ListarEstudiantesDeCursoService $servicio
    ): JsonResponse {
        Gate::forUser($request->user())->authorize('gestionar', Curso::query()->findOrFail($idCurso));
        $estudiantes = $servicio->ejecutar($idCurso);

        return response()->json([
            'datos' => $estudiantes->map(fn ($estudiante) => [
                'sis' => $estudiante->sis_estudiante,
                'nombre' => $estudiante->nombre_estudiante,
                'apellido' => $estudiante->apellido_estudiante,
                'carrera' => $estudiante->carrera,
            ]),
        ]);
    }
}
