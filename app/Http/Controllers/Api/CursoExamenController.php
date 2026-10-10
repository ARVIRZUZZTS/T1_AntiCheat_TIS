<?php

/**
 * @file    CursoExamenController.php
 *
 * @author  Diego Tejerina <josediegotejerinamolina@gmail.com>
 *
 * @created 2026-10-09
 *
 * @updated 2026-10-09
 *
 * @description
 * Endpoint de lectura de exámenes por curso. Reutiliza el servicio del listado
 * de la materia y presenta los dos estados del contrato de la task #138.
 *
 * @changelog
 * - 2026-10-09  [Diego Tejerina]  feat: agregar endpoint de exámenes por curso.
 *
 * @see App\Services\Examen\ListarExamenesCursoService
 */

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Examen\ListarExamenesCursoService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;

/** Presenta los exámenes sin trasladar las consultas ni el ordenamiento al HTTP. */
class CursoExamenController extends Controller
{
    /**
     * @param  int  $idCurso  Identificador del curso solicitado.
     * @param  ListarExamenesCursoService  $servicio  Lógica compartida con la vista.
     * @return JsonResponse Listado bajo la clave datos, igual que las otras APIs.
     *
     * @throws ModelNotFoundException Si el curso no existe (respuesta HTTP 404).
     */
    public function index(int $idCurso, ListarExamenesCursoService $servicio): JsonResponse
    {
        $examenes = $servicio->ejecutar($idCurso);

        return response()->json([
            'datos' => array_map(function (array $examen): array {
                // La vista conserva "en curso"; el contrato API utiliza dos etiquetas.
                $examen['estado'] = match ($examen['estado']) {
                    ListarExamenesCursoService::ESTADO_FINALIZADO => 'Finalizado',
                    default => 'Programado',
                };

                return $examen;
            }, $examenes),
        ]);
    }
}
