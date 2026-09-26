<?php

/**
 * @file    EstudianteExamenController.php
 *
 * @author  Diego Tejerina <josediegotejerinamolina@gmail.com>
 *
 * @created 2026-09-24
 *
 * @updated 2026-09-24
 *
 * @description
 * Controlador API de la feature Examenes: devuelve la lista de estudiantes de
 * un curso con su estado de habilitación en el examen actual, con filtro por
 * estado y búsqueda por nombre/código SIS. Es delgado: solo delega en el
 * servicio. De solo lectura (el estado solo se edita desde la vista de examen).
 *
 * @see  App\Services\Examen\ListarEstudiantesCursoConEstadoService
 *
 * @changelog
 * - 2026-09-24  [T1]  feat: creación inicial del controlador.
 */

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Examen\ListarEstudiantesCursoConEstadoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class EstudianteExamenController extends Controller
{
    public function index(
        int $idCurso,
        Request $request,
        ListarEstudiantesCursoConEstadoService $servicio,
    ): JsonResponse {
        try {
            $estudiantes = $servicio->ejecutar(
                $idCurso,
                $request->query('estado') ?: null,
                $request->query('busqueda') ?: null,
                max(1, (int) $request->query('pagina', 1)),
                max(1, min(50, (int) $request->query('por_pagina', 10)))
            );
        } catch (InvalidArgumentException $e) {
            return response()->json(['mensaje' => $e->getMessage()], 422);
        }

        $sinResultados = $request->query('busqueda') && $estudiantes->total() === 0;

        return response()->json([
            'datos' => $estudiantes->items(),
            'paginacion' => [
                'pagina' => $estudiantes->currentPage(),
                'por_pagina' => $estudiantes->perPage(),
                'total' => $estudiantes->total(),
                'ultima_pagina' => $estudiantes->lastPage(),
                'de' => $estudiantes->firstItem(),
                'a' => $estudiantes->lastItem(),
            ],
            'conteos' => $servicio->conteosPorEstado($idCurso),
            'mensaje' => $sinResultados ? 'No se encontraron resultados para la búsqueda' : null,
        ]);
    }
}
