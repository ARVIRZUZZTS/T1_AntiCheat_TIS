<?php

/**
 * @file    ExamenController.php
 *
 * @author  Alex Candia <alex.leonar.candia@gmail.com>
 *
 * @created 2026-10-05
 *
 * @updated 2026-10-10
 *
 * @description
 * Controlador HTTP del alta de exámenes del modal de la materia. Solo orquesta:
 * valida (StoreExamenRequest), delega el alta en RegistrarExamenService y
 * devuelve la persona al detalle de la materia con un mensaje de éxito.
 *
 * Si la validación falla no hace falta manejar nada: el request redirige de
 * vuelta con los errores y el input viejo, y la vista reabre el modal.
 *
 * @changelog
 * - 2026-10-05  [Alex Candia]  feat: creación inicial del controlador.
 *
 * @see  StoreExamenRequest
 * @see  App\Services\Examen\RegistrarExamenService
 */

namespace App\Http\Controllers;

use App\Http\Requests\StoreExamenRequest;
use App\Models\Curso;
use App\Services\Examen\RegistrarExamenService;
use Illuminate\Http\RedirectResponse;

class ExamenController extends Controller
{
    public function store(StoreExamenRequest $request, Curso $curso, RegistrarExamenService $servicio): RedirectResponse
    {
        $servicio->ejecutar($curso, $request->validated());

        return redirect()
            ->route('materias.detalle', $curso->id_curso)
            ->with('mensaje', 'Examen creado con éxito');
    }
}
