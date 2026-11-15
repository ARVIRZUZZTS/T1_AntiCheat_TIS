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
 * @updated 2026-11-05
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
 * - 2026-11-05  [Alex Candia]  feat: la página del curso entrega los catálogos
 *   también cuando ya no se pueden crear exámenes, porque Editar Examen los
 *   necesita para precargar el formulario.
 * - 2026-11-15  [Diego Tejerina]  feat: `crearFormulario` sirve la subopción
 *   "Crear examen" del sidebar: selector de curso + el formulario de alta que
 *   ya existe, sin depender del SPA de la página Exámenes.
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
        $cursoId = (int) $request->old('curso_id');
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

        // La edicion tambien usa los catalogos, aunque el curso ya no permita
        // crear examenes nuevos.
        $puedeCrearExamen = Gate::allows('crearExamen', $curso);

        return view('pages.examenes-curso', [
            'curso' => $curso,
            'ambientes' => $catalogos->ambientes(),
            'materiales' => $catalogos->materiales(),
            'normas' => $catalogos->normas(),
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

    /**
     * Pagina "Crear examen" de la subopcion del sidebar: un selector de curso
     * arriba y, al elegir uno, el formulario de alta que ya existe
     * (x-ui.form-crear-examen). El selector es server-driven (recarga con
     * ?curso=ID) para no depender del SPA de la seccion Examenes.
     *
     * @param  Request  $request  Request con el docente autenticado.
     * @param  ListarCursosDocenteService  $cursos  Cursos activos del docente.
     * @param  CatalogosExamenService  $catalogos  Catalogos del formulario.
     *
     * @return View  Vista pages.examen-crear-seleccion.
     *
     * @author Diego Tejerina <josediegotejerinamolina@gmail.com>
     * @since  2026-11-15
     */
    public function crearFormulario(
        Request $request,
        ListarCursosDocenteService $cursos,
        CatalogosExamenService $catalogos,
    ): View {
        $activos = $cursos->activos((int) $request->user()->id_usuario);

        // El curso puede venir del query (?curso=ID) o del input viejo cuando el
        // alta fallo y StoreExamenRequest redirigio de vuelta. Solo vale si es
        // del docente y todavia admite altas (CursoPolicy::crearExamen).
        $cursoId = (int) $request->old('curso_id');
        if ($cursoId === 0) {
            $cursoId = $request->integer('curso');
        }

        $cursoSeleccionado = $cursoId > 0 ? $activos->firstWhere('id_curso', $cursoId) : null;

        if ($cursoSeleccionado !== null && ! Gate::allows('crearExamen', $cursoSeleccionado)) {
            $cursoSeleccionado = null;
        }

        return view('pages.examen-crear-seleccion', [
            'cursos' => $activos,
            'cursoSeleccionado' => $cursoSeleccionado,
            'ambientes' => $catalogos->ambientes(),
            'materiales' => $catalogos->materiales(),
            'normas' => $catalogos->normas(),
        ]);
    }

    /**
     * Subopción "Editar examen": reutiliza el parcial de la sección acotado a
     * la pestaña Editar (selector de materia + examen a editar + formulario).
     *
     * @author Diego Tejerina <josediegotejerinamolina@gmail.com>
     * @since  2026-11-15
     */
    public function editarVista(
        Request $request,
        ListarCursosDocenteService $cursos,
        CatalogosExamenService $catalogos,
    ): View {
        return $this->seccionAcotada($request, $cursos, $catalogos, 'editar', 'pages.examenes-editar');
    }

    /**
     * Subopción "Dividir examen": el mismo parcial acotado a la pestaña Dividir
     * (selector de materia + examen con 2+ aulas + configuración de división).
     *
     * @author Diego Tejerina <josediegotejerinamolina@gmail.com>
     * @since  2026-11-15
     */
    public function dividirVista(
        Request $request,
        ListarCursosDocenteService $cursos,
        CatalogosExamenService $catalogos,
    ): View {
        return $this->seccionAcotada($request, $cursos, $catalogos, 'dividir', 'pages.examenes-dividir');
    }

    /**
     * Arma los datos del parcial de exámenes acotado a una sola pestaña.
     *
     * @param  string  $soloTab  Pestaña única a mostrar (editar / dividir).
     * @param  string  $vista    Vista contenedora.
     */
    private function seccionAcotada(
        Request $request,
        ListarCursosDocenteService $cursos,
        CatalogosExamenService $catalogos,
        string $soloTab,
        string $vista,
    ): View {
        $cursos = $cursos->activos((int) $request->user()->id_usuario);

        $cursoId = $request->integer('curso');
        $cursoInicial = $cursoId > 0 ? $cursos->firstWhere('id_curso', $cursoId) : null;

        $catalogo = $cursos->isNotEmpty()
            ? [
                'ambientes' => $catalogos->ambientes(),
                'materiales' => $catalogos->materiales(),
                'normas' => $catalogos->normas(),
            ]
            : ['ambientes' => [], 'materiales' => [], 'normas' => []];

        return view($vista, [
            'cursos' => $cursos,
            'cursoInicial' => $cursoInicial,
            'soloTab' => $soloTab,
            'ambientes' => $catalogo['ambientes'],
            'materiales' => $catalogo['materiales'],
            'normas' => $catalogo['normas'],
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
