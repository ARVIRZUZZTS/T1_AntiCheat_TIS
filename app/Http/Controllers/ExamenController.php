<?php

/**
 * @file    ExamenController.php
 *
 * @author  Alex Candia <alex.leonar.candia@gmail.com>
 *
 * @created 2026-10-05
 *
 * @updated 2026-10-10
 * @updated 2026-10-24
 *
 * @description
 * Controlador HTTP de la sección Exámenes del docente. Solo orquesta:
 *
 * - `index`: elegir uno de sus cursos activos para ver o crear exámenes.
 * - `curso`: los exámenes de un curso.
 * - `crear`: el formulario de alta de un examen, como página propia.
 * - `store`: valida (StoreExamenRequest), delega el alta en
 *   RegistrarExamenService y devuelve a la persona a los exámenes del curso
 *   con un mensaje de éxito.
 *
 * Si la validación falla no hace falta manejar nada: el request redirige de
 * vuelta al formulario con los errores y el input viejo.
 *
 * @changelog
 * - 2026-10-05  [Alex Candia]  feat: creación inicial del controlador.
 * - 2026-10-24  [Valery D. Ortuno P]  feat: acciones index, curso y crear de la
 *   sección Exámenes; el alta ya no vuelve al detalle de la materia sino a los
 *   exámenes del curso, y las vistas de un curso exigen que sea del docente.
 * - 2026-10-24  [Valery D. Ortuno P]  feat: el formulario de alta exige que el
 *   curso siga en curso (CursoPolicy::crearExamen) y el aviso de éxito va sin
 *   acentos.
 *
 * @see  StoreExamenRequest
 * @see  App\Services\Examen\RegistrarExamenService
 * @see  App\Services\Curso\ListarCursosDocenteService
 * @see  App\Policies\CursoPolicy
 */

namespace App\Http\Controllers;

use App\Http\Requests\StoreExamenRequest;
use App\Models\Curso;
use App\Services\Ambiente\ListarAmbientesService;
use App\Services\Curso\ListarCursosDocenteService;
use App\Services\Examen\RegistrarExamenService;
use App\Services\Material\ListarMaterialesService;
use App\Services\Norma\ListarNormasService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ExamenController extends Controller
{
    /**
     * Sección Exámenes: cursos activos del docente para elegir uno.
     *
     * @param  Request  $request  Request con el docente autenticado.
     * @param  ListarCursosDocenteService  $cursos  Servicio de cursos del docente.
     *
     * @return View  Vista pages.examenes.
     *
     * @author Valery D. Ortuno P <valerydariana98@gmail.com>
     * @since  2026-10-24
     */
    public function index(Request $request, ListarCursosDocenteService $cursos): View
    {
        return view('pages.examenes', [
            'cursos' => $cursos->activos((int) $request->user()->id_usuario),
        ]);
    }

    /**
     * Exámenes de un curso. El listado lo consulta la vista con fetch al
     * endpoint GET /api/cursos/{idCurso}/examenes.
     *
     * @param  Curso  $curso  Curso de la URL.
     *
     * @return View  Vista pages.examenes-curso.
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException  Si el curso no es del docente (403).
     *
     * @author Valery D. Ortuno P <valerydariana98@gmail.com>
     * @since  2026-10-24
     */
    public function curso(Curso $curso): View
    {
        Gate::authorize('gestionar', $curso);

        return view('pages.examenes-curso', [
            'curso' => $curso,
            'backRoute' => route('examenes'),
        ]);
    }

    /**
     * Formulario de alta de un examen en el curso.
     *
     * @param  Curso  $curso  Curso de la URL.
     *
     * @return View  Vista pages.examen-crear.
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException  Si el curso no es del docente o ya finalizó (403).
     *
     * @author Valery D. Ortuno P <valerydariana98@gmail.com>
     * @since  2026-10-24
     */
    public function crear(Curso $curso): View
    {
        Gate::authorize('crearExamen', $curso);

        return view('pages.examen-crear', [
            'curso' => $curso,
            // Catálogos del formulario. El buscador de ambientes los filtra en
            // el navegador, así que se resuelven enteros y no por curso.
            'ambientes' => app(ListarAmbientesService::class)->ejecutar(),
            'materiales' => app(ListarMaterialesService::class)->ejecutar(),
            'normas' => app(ListarNormasService::class)->ejecutar(),
            'backRoute' => route('examenes'),
        ]);
    }

    public function store(StoreExamenRequest $request, Curso $curso, RegistrarExamenService $servicio): RedirectResponse
    {
        $servicio->ejecutar($curso, $request->validated());

        return redirect()
            ->route('examenes.curso', $curso->id_curso)
            ->with('mensaje', 'Examen creado con exito');
    }
}
