<?php

/**
 * @file    CursoController.php
 *
 * @author  Valery D. Ortuno P <valerydariana98@gmail.com>
 *
 * @created 2026-10-24
 *
 * @description
 * Controlador HTTP de la sección Cursos del docente: la lista de sus cursos,
 * el formulario de alta ("Crear curso") y la vista de un curso con sus
 * estudiantes y habilitaciones. Solo orquesta: los datos los
 * resuelven los Services y la pertenencia del curso la decide CursoPolicy.
 *
 * Reemplaza a las closures de `/materias` y `/materias/{curso}` que vivían
 * en routes/web.php.
 *
 * @changelog
 * - 2026-10-24  [Valery D. Ortuno P]  feat: creación inicial del controlador.
 *
 * @see  App\Services\Curso\ListarCursosDocenteService
 * @see  App\Services\Examen\ListarEstudiantesCursoConEstadoService
 * @see  App\Policies\CursoPolicy
 */

namespace App\Http\Controllers;

use App\Models\Curso;
use App\Services\Curso\ListarCursosDocenteService;
use App\Services\Examen\ListarEstudiantesCursoConEstadoService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

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
     * @return View  Vista pages.curso-crear.
     *
     * @author Valery D. Ortuno P <valerydariana98@gmail.com>
     * @since  2026-10-24
     */
    public function crear(): View
    {
        return view('pages.curso-crear', [
            'backRoute' => route('cursos'),
        ]);
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
}
