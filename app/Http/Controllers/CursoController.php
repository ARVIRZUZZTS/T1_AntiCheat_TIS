<?php

/**
 * @file    CursoController.php
 *
 * @author  Valery D. Ortuno P <valerydariana98@gmail.com>
 *
 * @created 2026-10-24
 *
 * @updated 2026-11-04
 *
 * @description
 * Controlador HTTP de la sección Cursos del docente: la lista de sus cursos,
 * el formulario de alta ("Crear curso"), el alta del curso y la vista de un
 * curso con sus estudiantes y habilitaciones. Solo orquesta: los datos los
 * resuelven los Services y la pertenencia del curso la decide CursoPolicy.
 *
 * Reemplaza a las closures de `/materias` y `/materias/{curso}` que vivían
 * en routes/web.php.
 *
 * @changelog
 * - 2026-10-24  [Valery D. Ortuno P]  feat: creación inicial del controlador.
 * - 2026-10-24  [Valery D. Ortuno P]  feat: alta de curso con materia y grupo
 *   concatenados y su gestión con año; al guardar vuelve a la lista de cursos.
 * - 2026-11-04  [Valery D. Ortuno P]  fix: si el curso ya existe vuelve al
 *   formulario con el aviso (`curso`) y lo escrito.
 *
 * @see  App\Services\Curso\ListarCursosDocenteService
 * @see  App\Services\Curso\ListarGestionesService
 * @see  App\Services\Curso\RegistrarCursoService
 * @see  App\Services\Examen\ListarEstudiantesCursoConEstadoService
 * @see  App\Http\Requests\StoreCursoRequest
 * @see  App\Policies\CursoPolicy
 */

namespace App\Http\Controllers;

use App\Http\Requests\StoreCursoRequest;
use App\Models\Curso;
use App\Services\Curso\ListarCursosDocenteService;
use App\Services\Curso\ListarGestionesService;
use App\Services\Curso\RegistrarCursoService;
use App\Services\Examen\ListarEstudiantesCursoConEstadoService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;

class CursoController extends Controller
{
    /**
     * Lista de cursos del docente autenticado.
     *
     * @param  Request  $request  Request con el docente autenticado.
     * @param  ListarCursosDocenteService  $cursos  Servicio de cursos del docente.
     *
     * @return View  Vista pages.cursos.
     *
     * @author Valery D. Ortuno P <valerydariana98@gmail.com>
     * @since  2026-10-24
     */
    public function index(Request $request, ListarCursosDocenteService $cursos): View
    {
        return view('pages.cursos', [
            'cursos' => $cursos->conMetricas((int) $request->user()->id_usuario),
        ]);
    }

    /**
     * "Crear curso": formulario de alta de un curso.
     *
     * @param  ListarGestionesService  $gestiones  Catálogo del selector "Gestion".
     *
     * @return View  Vista pages.curso-crear.
     *
     * @author Valery D. Ortuno P <valerydariana98@gmail.com>
     * @since  2026-10-24
     */
    public function crear(ListarGestionesService $gestiones): View
    {
        // Sin backRoute: "Crear curso" es una subopción del sidebar, no una
        // vista de detalle, así que x-ui.page-header no muestra flecha de volver.
        return view('pages.curso-crear', [
            'gestiones' => $gestiones->ejecutar(),
        ]);
    }

    /**
     * Guarda el curso creado desde el formulario y vuelve a la lista de
     * cursos, donde se muestra el aviso de éxito.
     *
     * @param  StoreCursoRequest  $request  Datos validados (materia, grupo y gestión).
     * @param  RegistrarCursoService  $servicio  Servicio de alta.
     *
     * @return RedirectResponse  Redirige al listado de cursos con mensaje de éxito.
     *
     * @author Valery D. Ortuno P <valerydariana98@gmail.com>
     * @since  2026-10-24
     */
    public function store(StoreCursoRequest $request, RegistrarCursoService $servicio): RedirectResponse
    {
        try {
            $servicio->ejecutar(
                $request->nombreCurso(),
                (int) $request->user()->id_usuario,
                $request->gestion(),
            );
        } catch (InvalidArgumentException $error) {
            // Curso repetido: el aviso va arriba del formulario y se conserva
            // lo escrito, como con cualquier error de validación.
            return back()->withErrors(['curso' => $error->getMessage()])->withInput();
        }

        return redirect()
            ->route('cursos')
            ->with('mensaje', 'Curso creado con exito');
    }

    /**
     * Vista de un curso: tarjetas de conteo y las secciones Estudiantes,
     * Habilitaciones y Auxiliares.
     *
     * @param  Curso  $curso  Curso de la URL.
     * @param  ListarEstudiantesCursoConEstadoService  $estudiantes  Servicio de conteos del curso.
     *
     * @return View  Vista pages.curso-detalle.
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException  Si el curso no es del docente (403).
     *
     * @author Valery D. Ortuno P <valerydariana98@gmail.com>
     * @since  2026-10-24
     */
    public function detalle(Curso $curso, ListarEstudiantesCursoConEstadoService $estudiantes): View
    {
        Gate::authorize('gestionar', $curso);

        return view('pages.curso-detalle', [
            'curso' => $curso,
            'conteos' => $estudiantes->conteosPorEstado($curso->id_curso),
            // x-ui.page-header muestra la flecha "Volver" en vez del
            // hamburguesa cuando recibe $backRoute.
            'backRoute' => route('cursos'),
        ]);
    }

    /**
     * Página "Ver curso": un selector con los cursos del docente y, debajo, la
     * información del elegido (mismo contenido que el detalle, en un parcial
     * compartido). Si se llega desde la lista con ?curso=ID, viene preseleccionado.
     *
     * @param  Request  $request  Request con el docente autenticado.
     * @param  ListarEstudiantesCursoConEstadoService  $estudiantes  Servicio de conteos del curso.
     *
     * @return View  Vista pages.curso-ver.
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException  Si el curso no es del docente (403).
     *
     * @author Diego Tejerina <josediegotejerinamolina@gmail.com>
     * @since  2026-11-15
     */
    public function ver(Request $request, ListarEstudiantesCursoConEstadoService $estudiantes): View
    {
        $cursos = Curso::query()
            ->where('sis_doc', $request->user()->id_usuario)
            ->orderBy('nombre_curso')
            ->get();

        $cursoId = $request->integer('curso');
        $cursoSeleccionado = $cursoId > 0 ? $cursos->firstWhere('id_curso', $cursoId) : null;

        $conteos = null;
        if ($cursoSeleccionado !== null) {
            // Red de seguridad: el select solo ofrece cursos propios, pero el id
            // llega por el query y se autoriza igual que el detalle.
            Gate::authorize('gestionar', $cursoSeleccionado);
            $conteos = $estudiantes->conteosPorEstado($cursoSeleccionado->id_curso);
        }

        return view('pages.curso-ver', [
            'cursos' => $cursos,
            'cursoSeleccionado' => $cursoSeleccionado,
            'conteos' => $conteos,
        ]);
    }
}
