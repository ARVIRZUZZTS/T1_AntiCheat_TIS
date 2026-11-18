<?php

/**
 * @file    CursoController.php
 *
 * @author  Valery D. Ortuno P <valerydariana98@gmail.com>
 *
 * @created 2026-10-24
 *
 * @updated 2026-11-04
 * @updated 2026-11-15
 * @updated 2026-11-16
 *
 * @description
 * Controlador HTTP de la sección Cursos del docente: la lista de sus cursos,
 * el formulario de alta ("Crear curso"), el alta y la eliminación del curso,
 * la página "Eliminar curso" y la vista de un curso con sus estudiantes y
 * habilitaciones. Solo orquesta: los datos los resuelven los Services y la
 * pertenencia del curso la decide CursoPolicy.
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
 * - 2026-11-15  [Alex Candia]  feat: "Ver curso" se divide en verEstudiantes y
 *   habilitarEstudiantes; ambas páginas comparten selector de curso y muestran
 *   solo su sección.
 * - 2026-11-16  [Alex Candia]  feat: eliminación de un curso propio con el
 *   conteo previo de auxiliares y estudiantes afectados, y página con la lista
 *   de cursos para eliminar (sin modal de confirmación todavía).
 * - 2026-11-16  [Alex Candia]  feat: el borrado del curso elimina también sus
 *   exámenes con EliminarExamenCursoService.
 *
 * @see  App\Services\Curso\ContarAfectadosCursoService
 * @see  App\Services\Curso\EliminarCursoService
 * @see  App\Services\Curso\ListarCursosDocenteService
 * @see  App\Services\Curso\ListarGestionesService
 * @see  App\Services\Curso\RegistrarCursoService
 * @see  App\Services\Examen\EliminarExamenCursoService
 * @see  App\Services\Examen\ListarEstudiantesCursoConEstadoService
 * @see  App\Http\Requests\StoreCursoRequest
 * @see  App\Policies\CursoPolicy
 * @see  resources/views/pages/curso-eliminar.blade.php
 */

namespace App\Http\Controllers;

use App\Http\Requests\StoreCursoRequest;
use App\Models\Curso;
use App\Models\Usuario;
use App\Services\Curso\ContarAfectadosCursoService;
use App\Services\Curso\EliminarCursoService;
use App\Services\Curso\ListarCursosDocenteService;
use App\Services\Curso\ListarGestionesService;
use App\Services\Curso\RegistrarCursoService;
use App\Services\Examen\ListarEstudiantesCursoConEstadoService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
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
     * @return View Vista pages.cursos.
     *
     * @author Valery D. Ortuno P <valerydariana98@gmail.com>
     *
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
     * @return View Vista pages.curso-crear.
     *
     * @author Valery D. Ortuno P <valerydariana98@gmail.com>
     *
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
     * Página "Eliminar curso": lista simple de todos los cursos del docente,
     * cada uno con su botón "Eliminar curso". El modal de confirmación se
     * integra en una iteración posterior.
     *
     * @param  Request  $request  Request con el docente autenticado.
     * @return View Vista pages.curso-eliminar.
     *
     * @author Alex Candia
     *
     * @since  2026-11-16
     */
    public function eliminar(Request $request): View
    {
        return view('pages.curso-eliminar', [
            'cursos' => Curso::query()
                ->where('sis_doc', $request->user()->id_usuario)
                ->orderBy('nombre_curso')
                ->get(['id_curso', 'nombre_curso']),
        ]);
    }

    /**
     * Guarda el curso creado desde el formulario y vuelve a la lista de
     * cursos, donde se muestra el aviso de éxito.
     *
     * @param  StoreCursoRequest  $request  Datos validados (materia, grupo y gestión).
     * @param  RegistrarCursoService  $servicio  Servicio de alta.
     * @return RedirectResponse Redirige al listado de cursos con mensaje de éxito.
     *
     * @author Valery D. Ortuno P <valerydariana98@gmail.com>
     *
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
     * Devuelve cuántos auxiliares y estudiantes quedarían desvinculados si se
     * elimina el curso. Alimenta la advertencia del modal "Eliminar curso".
     *
     * @param  Curso  $curso  Curso de la URL.
     * @param  ContarAfectadosCursoService  $afectados  Servicio de conteos.
     * @return JsonResponse Conteos de auxiliares y estudiantes.
     *
     * @throws AuthorizationException Si el curso no es del docente (403).
     *
     * @author Alex Candia
     *
     * @since  2026-11-16
     */
    public function afectados(Curso $curso, ContarAfectadosCursoService $afectados): JsonResponse
    {
        Gate::authorize('eliminar', $curso);

        return response()->json($afectados->ejecutar($curso));
    }

    /**
     * Elimina el curso propio y vuelve a la lista con el aviso de éxito. Si la
     * petición espera JSON (el modal de la página "Eliminar curso"), devuelve
     * el mensaje para que la lista se actualice sin recargar. El servicio de
     * curso se encarga de eliminar también los exámenes del curso.
     *
     * @param  Request  $request  Request de la eliminación.
     * @param  Curso  $curso  Curso de la URL.
     * @param  EliminarCursoService  $servicio  Servicio de borrado.
     * @return RedirectResponse|JsonResponse Redirección al listado o mensaje JSON.
     *
     * @throws AuthorizationException Si el curso no es del docente (403).
     *
     * @author Alex Candia
     *
     * @since  2026-11-16
     */
    public function destroy(
        Request $request,
        Curso $curso,
        EliminarCursoService $servicio,
    ): RedirectResponse|JsonResponse {
        Gate::authorize('eliminar', $curso);

        $usuario = $request->user();
        abort_unless($usuario instanceof Usuario, 401);

        $servicio->ejecutar($usuario, $curso);

        if ($request->expectsJson()) {
            return response()->json(['mensaje' => 'Curso eliminado con exito']);
        }

        return redirect()
            ->route('cursos')
            ->with('mensaje', 'Curso eliminado con exito');
    }

    /**
     * Vista de un curso: tarjetas de conteo y las secciones Estudiantes,
     * Habilitaciones y Auxiliares.
     *
     * @param  Curso  $curso  Curso de la URL.
     * @param  ListarEstudiantesCursoConEstadoService  $estudiantes  Servicio de conteos del curso.
     * @return View Vista pages.curso-detalle.
     *
     * @throws AuthorizationException Si el curso no es del docente (403).
     *
     * @author Valery D. Ortuno P <valerydariana98@gmail.com>
     *
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
     * Página "Ver estudiantes": un selector con los cursos del docente y,
     * debajo, la sección Estudiantes del curso elegido.
     *
     * @param  Request  $request  Request con el docente autenticado.
     * @param  ListarEstudiantesCursoConEstadoService  $estudiantes  Servicio de conteos del curso.
     * @return View Vista pages.curso-ver.
     *
     * @throws AuthorizationException Si el curso no es del docente (403).
     *
     * @author Diego Tejerina <josediegotejerinamolina@gmail.com>
     *
     * @since  2026-11-15
     */
    public function verEstudiantes(
        Request $request,
        ListarEstudiantesCursoConEstadoService $estudiantes,
    ): View {
        return $this->vistaSeccionCurso($request, $estudiantes, 'estudiantes');
    }

    /**
     * Página "Habilitar estudiantes": mismo selector de curso y la sección
     * Habilitaciones del elegido, con sus modales de cambio de estado.
     *
     * @param  Request  $request  Request con el docente autenticado.
     * @param  ListarEstudiantesCursoConEstadoService  $estudiantes  Servicio de conteos del curso.
     * @return View Vista pages.curso-ver.
     *
     * @throws AuthorizationException Si el curso no es del docente (403).
     *
     * @author Diego Tejerina <josediegotejerinamolina@gmail.com>
     *
     * @since  2026-11-15
     */
    public function habilitarEstudiantes(
        Request $request,
        ListarEstudiantesCursoConEstadoService $estudiantes,
    ): View {
        return $this->vistaSeccionCurso($request, $estudiantes, 'habilitacion');
    }

    /**
     * Arma la página de una sola sección del curso: select de cursos del
     * docente, curso inicial desde ?curso=ID y conteos del elegido.
     *
     * @param  Request  $request  Request con el docente autenticado.
     * @param  ListarEstudiantesCursoConEstadoService  $estudiantes  Servicio de conteos del curso.
     * @param  string  $soloTab  Sección única a mostrar (estudiantes / habilitacion).
     * @return View Vista pages.curso-ver acotada a la sección.
     *
     * @throws AuthorizationException Si el curso no es del docente (403).
     */
    private function vistaSeccionCurso(
        Request $request,
        ListarEstudiantesCursoConEstadoService $estudiantes,
        string $soloTab,
    ): View {
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

        $esEstudiantes = $soloTab === 'estudiantes';

        return view('pages.curso-ver', [
            'cursos' => $cursos,
            'cursoSeleccionado' => $cursoSeleccionado,
            'conteos' => $conteos,
            'soloTab' => $soloTab,
            'titulo' => $esEstudiantes ? 'Ver estudiantes' : 'Habilitar estudiantes',
            'accionUrl' => $esEstudiantes
                ? route('cursos.ver-estudiantes')
                : route('cursos.habilitar-estudiantes'),
        ]);
    }
}
