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
 * @updated 2026-11-14
 * @updated 2026-11-19
 * @updated 2026-11-20
 * @updated 2026-11-24
 *
 * @description
 * Controlador HTTP de la sección Exámenes del docente. Solo orquesta:
 *
 * - `index`: listado global de exámenes futuros y las cuatro pestañas en una
 *   sola página; el curso del alta se elige dentro del formulario.
 * - `curso`: página anterior de un curso (se mantiene por compatibilidad).
 * - `crear`: el formulario de alta de un examen, como página propia.
 * - `eliminarVista`: la subopción "Eliminar examen" del sidebar, con la lista
 *   de todos los exámenes del docente ordenada (programados primero).
 * - `editarVista`/`dividirVista`: el parcial de exámenes con el mapa completo
 *   de gestiones del docente, para que todas las etiquetas lleven gestión.
 * - `store`: valida (StoreExamenRequest), delega el alta en
 *   RegistrarExamenService y vuelve a la página única con el curso elegido y
 *   un mensaje de éxito.
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
 * - 2026-11-20  [OchoaCesar]  feat: `eliminarVista` sirve la subopción
 *   "Eliminar examen" del sidebar: lista de todos los exámenes del docente,
 *   programados primero y finalizados al final, con los cursos que el docente
 *   gestiona por examen para el botón de eliminación.
 * - 2026-11-24  [Equipo T1]  fix: el mapa de gestiones del parcial cubre todos
 *   los cursos del docente, no solo los activos.
 *
 * @see  StoreExamenRequest
 * @see  App\Services\Examen\CatalogosExamenService
 * @see  App\Services\Examen\RegistrarExamenService
 * @see  App\Services\Examen\ListarExamenesDocenteService
 * @see  App\Services\Curso\ListarCursosDocenteService
 * @see  App\Policies\CursoPolicy
 */

namespace App\Http\Controllers;

use App\Http\Requests\StoreExamenRequest;
use App\Models\Curso;
use App\Services\Curso\ListarCursosDocenteService;
use App\Services\Examen\CatalogosExamenService;
use App\Services\Examen\ListarExamenesCursoService;
use App\Services\Examen\ListarExamenesDocenteService;
use App\Services\Examen\RegistrarExamenService;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class ExamenController extends Controller
{
    /**
     * Sección Exámenes: listado global con las cuatro pestañas de gestión. La
     * página no navega; el formulario de Crear Examen elige el curso con un
     * selector propio. Si la URL o el input viejo traen un curso válido, el
     * selector arranca con ese curso.
     *
     * @param  Request  $request  Request con el docente autenticado.
     * @param  ListarCursosDocenteService  $cursos  Servicio de cursos del docente.
     * @param  CatalogosExamenService  $catalogos  Catálogos del formulario de alta.
     * @return View Vista pages.examenes.
     *
     * @author Valery D. Ortuno P <valerydariana98@gmail.com>
     *
     * @since  2026-10-24
     */
    public function index(
        Request $request,
        ListarCursosDocenteService $cursos,
        CatalogosExamenService $catalogos,
    ): View {
        $gestionesPorCurso = $cursos->cursosConGestion((int) $request->user()->id_usuario);
        $cursos = $cursos->conExamenes((int) $request->user()->id_usuario);

        // El curso puede venir de la URL (recarga o enlace directo) o quedar en
        // el input viejo cuando falló el alta. Solo se acepta si es del docente.
        $cursoId = (int) $request->old('curso_id');
        if ($cursoId === 0) {
            $cursoId = $request->integer('curso');
        }

        $cursoInicial = $cursoId > 0
            ? $cursos->firstWhere('id_curso', $cursoId)
            : null;

        // Si el alta falló, se vuelve a abrir la pestaña del formulario para
        // mostrar los errores junto al curso que se alcanzó a elegir.
        $hayErrores = (bool) $request->session()->get('errors')?->any();
        $vieneDelAlta = $request->session()->hasOldInput();
        $tabInicial = $hayErrores && $vieneDelAlta && $cursos->isNotEmpty()
            ? 'crear'
            : 'lista';

        return view('pages.examenes', [
            'cursos' => $cursos,
            'cursoInicial' => $cursoInicial,
            'gestionesPorCurso' => $gestionesPorCurso,
            'tabInicial' => $tabInicial,
            'ambientes' => $catalogos->ambientes(),
            'materiales' => $catalogos->materiales(),
            'normas' => $catalogos->normas(),
        ]);
    }

    /**
     * Exámenes de un curso. El listado lo consulta la vista con fetch al
     * endpoint GET /api/cursos/{idCurso}/examenes; los catálogos se pasan para
     * poder incrustar el formulario de alta en su pestaña.
     *
     * @param  Curso  $curso  Curso de la URL.
     * @param  CatalogosExamenService  $catalogos  Catálogos del formulario.
     * @param  ListarCursosDocenteService  $cursos  Servicio de cursos del docente.
     * @return View Vista pages.examenes-curso.
     *
     * @throws AuthorizationException Si el curso no es del docente (403).
     *
     * @author Valery D. Ortuno P <valerydariana98@gmail.com>
     *
     * @since  2026-10-24
     */
    public function curso(
        Curso $curso,
        CatalogosExamenService $catalogos,
        ListarCursosDocenteService $cursos,
    ): View {
        Gate::authorize('gestionar', $curso);

        // La edicion tambien usa los catalogos, aunque el curso ya no permita
        // crear examenes nuevos.
        $puedeCrearExamen = Gate::allows('crearExamen', $curso);

        return view('pages.examenes-curso', [
            'curso' => $curso,
            'gestionesPorCurso' => $cursos->cursosConGestion((int) auth()->id()),
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
     * @return View Vista pages.examen-crear.
     *
     * @throws AuthorizationException Si el curso no es del docente o ya finalizó (403).
     *
     * @author Valery D. Ortuno P <valerydariana98@gmail.com>
     *
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
     * @return View Vista pages.examen-crear-seleccion.
     *
     * @author Diego Tejerina <josediegotejerinamolina@gmail.com>
     *
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
     *
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
     *
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
     * Subopción "Eliminar examen": la lista de todos los exámenes del docente
     * (propios y con invitación aceptada), con los programados primero y los
     * finalizados al final. Por cada examen entrega además los cursos que el
     * docente gestiona (es el dueño), que son los únicos donde sale el botón
     * "Eliminar": un examen compartido donde solo es invitado se muestra sin
     * botón.
     *
     * @param  Request  $request  Request con el docente autenticado.
     * @param  ListarExamenesDocenteService  $examenes  Listado del docente.
     * @return View Vista pages.examen-eliminar.
     *
     * @author OchoaCesar <cesareduardonick@gmail.com>
     *
     * @since  2026-11-20
     */
    public function eliminarVista(Request $request, ListarExamenesDocenteService $examenes): View
    {
        $idDocente = (int) $request->user()->id_usuario;
        $ahora = Carbon::now();

        $listado = array_values(array_filter(
            array_map(function (array $examen): array {
                $examen['estado'] = match ($examen['estado']) {
                    ListarExamenesCursoService::ESTADO_EN_CURSO => 'En curso',
                    ListarExamenesCursoService::ESTADO_FINALIZADO => 'Finalizado',
                    default => 'Programado',
                };

                return $examen;
            }, $examenes->ejecutar($idDocente, $ahora)),
            fn (array $examen): bool => $examen['estado'] === 'Programado',
        ));

        // Cursos del docente vinculados a cada examen: solo de esos cursos
        // sale el botón "Eliminar" en la tarjeta.
        $cursosPropios = DB::table('examen_curso as ec')
            ->join('curso as c', 'c.id_curso', '=', 'ec.id_curso')
            ->where('c.sis_doc', $idDocente)
            ->whereIn('ec.id_examen', array_column($listado, 'id'))
            ->get(['ec.id_examen', 'ec.id_curso', 'c.nombre_curso'])
            ->groupBy('id_examen');

        foreach ($listado as $indice => $examen) {
            $listado[$indice]['cursos_propios'] = $cursosPropios
                ->get($examen['id'])
                ?->map(fn ($fila): array => [
                    'id' => (int) $fila->id_curso,
                    'nombre' => (string) $fila->nombre_curso,
                ])
                ->values()
                ->all() ?? [];
        }

        usort($listado, static function (array $primero, array $segundo): int {
            return [$primero['fecha'] ?? '9999-12-31', $primero['hora_inicio'] ?? '', $primero['id']]
                <=> [$segundo['fecha'] ?? '9999-12-31', $segundo['hora_inicio'] ?? '', $segundo['id']];
        });

        return view('pages.examen-eliminar', [
            'examenes' => $listado,
            'ahora' => $ahora->format('Y-m-d H:i'),
        ]);
    }

    /**
     * Arma los datos del parcial de exámenes acotado a una sola pestaña.
     *
     * @param  string  $soloTab  Pestaña única a mostrar (editar / dividir).
     * @param  string  $vista  Vista contenedora.
     */
    private function seccionAcotada(
        Request $request,
        ListarCursosDocenteService $cursos,
        CatalogosExamenService $catalogos,
        string $soloTab,
        string $vista,
    ): View {
        $idDocente = (int) $request->user()->id_usuario;
        $gestionesPorCurso = $cursos->cursosConGestion($idDocente);
        $cursos = $cursos->activos($idDocente);

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
            'gestionesPorCurso' => $gestionesPorCurso,
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
