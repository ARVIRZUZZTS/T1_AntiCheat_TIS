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
 * @updated 2026-10-25
 * @updated 2026-10-26
 *
 * @description
 * Controlador HTTP de la sección Exámenes del docente. Solo orquesta:
 *
 * - `index`: selector de materia y las cuatro pestañas de exámenes en una sola
 *   página.
 * - `curso`: página anterior de un curso (se mantiene por compatibilidad).
 * - `crear`: el formulario de alta de un examen, como página propia.
 * - `store`: valida (StoreExamenRequest), delega el alta en
 *   RegistrarExamenService y vuelve a la página única con la materia elegida
 *   y un mensaje de éxito.
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
 * - 2026-10-25  [Alex Candia]  feat: la página del curso entrega la policy y
 *   los catálogos para incrustar el formulario de alta en la pestaña Crear
 *   Examen.
 * - 2026-10-25  [Alex Candia]  feat: Exámenes pasa a una sola página con
 *   selector de materia; el alta vuelve a esa página con la materia elegida.
 * - 2026-10-26  [Valery D. Ortuno P]  perf: los catálogos del formulario salen
 *   de CatalogosExamenService (cacheados); antes eran tres consultas cada vez
 *   que se abría el alta.
 *
 * @see  StoreExamenRequest
 * @see  App\Services\Examen\CatalogosExamenService
 * @see  App\Services\Examen\RegistrarExamenService
 * @see  App\Services\Curso\ListarCursosDocenteService
 * @see  App\Policies\CursoPolicy
 */

namespace App\Http\Controllers;

use App\Http\Requests\StoreExamenRequest;
use App\Models\Curso;
use App\Services\Curso\ListarCursosDocenteService;
use App\Services\Examen\CatalogosExamenService;
use App\Services\Examen\RegistrarExamenService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ExamenController extends Controller
{
    /**
     * Sección Exámenes: selector de materia y, debajo, las cuatro pestañas de
     * gestión del curso elegido. La página no navega: el selector y las
     * pestañas viven en el mismo documento.
     *
     * @param  Request  $request  Request con el docente autenticado.
     * @param  ListarCursosDocenteService  $cursos  Servicio de cursos del docente.
     * @param  CatalogosExamenService  $catalogos  Catálogos del formulario de alta.
     *
     * @return View  Vista pages.examenes.
     *
     * @author Valery D. Ortuno P <valerydariana98@gmail.com>
     * @since  2026-10-24
     */
    public function index(
        Request $request,
        ListarCursosDocenteService $cursos,
        CatalogosExamenService $catalogos,
    ): View {
        $cursos = $cursos->activos((int) $request->user()->id_usuario);

        // El curso puede venir de la URL (recarga o enlace directo) o quedar en
        // el input viejo cuando falló el alta. Solo se acepta si es del docente.
        $cursoId = (int) $request->old('curso_id', 0);
        if ($cursoId === 0) {
            $cursoId = $request->integer('curso');
        }

        $cursoInicial = $cursoId > 0
            ? $cursos->firstWhere('id_curso', $cursoId)
            : null;

        // Si el alta falló, se vuelve a abrir la pestaña del formulario.
        $hayErrores = (bool) $request->session()->get('errors')?->any();
        $vieneDelAlta = $request->old('curso_id') !== null;
        $tabInicial = $hayErrores && $vieneDelAlta && $cursoInicial !== null
            ? 'crear'
            : 'lista';

        $catalogo = $cursos->isNotEmpty()
            ? [
                'ambientes' => $catalogos->ambientes(),
                'materiales' => $catalogos->materiales(),
                'normas' => $catalogos->normas(),
            ]
            : ['ambientes' => [], 'materiales' => [], 'normas' => []];

        return view('pages.examenes', [
            'cursos' => $cursos,
            'cursoInicial' => $cursoInicial,
            'tabInicial' => $tabInicial,
            'ambientes' => $catalogo['ambientes'],
            'materiales' => $catalogo['materiales'],
            'normas' => $catalogo['normas'],
        ]);
    }

    /**
     * Exámenes de un curso. El listado lo consulta la vista con fetch al
     * endpoint GET /api/cursos/{idCurso}/examenes; los catálogos se pasan para
     * poder incrustar el formulario de alta en su pestaña.
     *
     * @param  Curso  $curso  Curso de la URL.
     * @param  CatalogosExamenService  $catalogos  Catálogos del formulario.
     *
     * @return View  Vista pages.examenes-curso.
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException  Si el curso no es del docente (403).
     *
     * @author Valery D. Ortuno P <valerydariana98@gmail.com>
     * @since  2026-10-24
     */
    public function curso(Curso $curso, CatalogosExamenService $catalogos): View
    {
        Gate::authorize('gestionar', $curso);

        // El formulario solo se muestra si el curso sigue habilitado para crear
        // exámenes; así no se consultan catálogos que no se van a usar.
        $puedeCrearExamen = Gate::allows('crearExamen', $curso);

        return view('pages.examenes-curso', [
            'curso' => $curso,
            'ambientes' => $puedeCrearExamen ? $catalogos->ambientes() : [],
            'materiales' => $puedeCrearExamen ? $catalogos->materiales() : [],
            'normas' => $puedeCrearExamen ? $catalogos->normas() : [],
            'puedeCrearExamen' => $puedeCrearExamen,
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
    public function crear(Curso $curso, CatalogosExamenService $catalogos): View
    {
        Gate::authorize('crearExamen', $curso);

        return view('pages.examen-crear', [
            'curso' => $curso,
            // Catálogos del formulario, cacheados. El buscador de ambientes los
            // filtra en el navegador, así que se resuelven enteros y no por curso.
            'ambientes' => $catalogos->ambientes(),
            'materiales' => $catalogos->materiales(),
            'normas' => $catalogos->normas(),
            'backRoute' => route('examenes'),
        ]);
    }

    public function store(StoreExamenRequest $request, Curso $curso, RegistrarExamenService $servicio): RedirectResponse
    {
        $servicio->ejecutar($curso, $request->validated());

        return redirect()
            ->route('examenes', ['curso' => $curso->id_curso])
            ->with('mensaje', 'Examen creado con exito');
    }
}
