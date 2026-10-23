<?php

/**
 * @file    web.php
 *
 * @author  David E. Chavez T. <virzuzz12345@gmail.com>
 *
 * @created 2026-09-29
 *
 * @updated 2026-10-10
 * @updated 2026-10-11
 * @updated 2026-10-12
 * @updated 2026-10-21
 *
 * @description
 * Definición de rutas web del panel. Incluye vistas estáticas y componentes Livewire.
 *
 * @changelog
 * - 2026-09-29  [David E. Chavez T.]  feat: rutas web del panel.
 * - 2026-10-01  [Alex Candia]  feat: añadida la pantalla de registro de ingreso.
 * - 2026-10-01  [Alex Candia]  refactor: la pantalla de registro de ingreso se
 *   enruta directo a su componente Livewire (`BuscadorRegistro`) en vez de pasar
 *   por `MonitoreoController`, con lo que el controlador deja de renderizar
 *   vistas de componentes y la ruta queda sin lógica.
 * - 2026-10-05  [Alex Candia]  feat: el detalle de la materia también entrega los
 *   exámenes del curso, para la pestaña Exámenes.
 * - 2026-10-05  [Alex Candia]  feat: el detalle entrega además el catálogo de
 *   ambientes, el de materiales y el de normas, que alimentan el modal de alta.
 * - 2026-10-05  [Alex Candia]  feat: endpoint POST de alta de examen, que es a
 *   donde envía el modal de la pestaña Exámenes.
 * - 2026-10-09  [Alisson D. Alvarado]  feat: ruta de la pantalla de
 *   notificaciones del docente. Por ahora renderiza una vista con datos
 *   mockeados; el endpoint real se conecta en una iteración posterior.
 * - 2026-10-09  [OchoaCesar]  refactor: la pestaña Exámenes deja de recibir los
 *   exámenes por la ruta; el parcial los consulta con fetch al endpoint
 *   GET /api/cursos/{idCurso}/examenes cuando se abre.
 * - 2026-10-09  [Alisson D. Alvarado]  refactor: la ruta de la central de
 *   riesgos pasa a ser una vista simple, ya que la pantalla es solo interfaz
 *   (datos mockeados) y llamaba a ListarAlertasService, que fallaba al leer
 *   `estado_incidencia`, columna aún no disponible en la base.
 * - 2026-10-10  [David E. Chavez T.]  refactor: el monitor en vivo se enruta
 *   directo a su componente Livewire (`MonitorEnVivo`) en vez de pasar por
 *   `MonitoreoController`; la pantalla ahora persiste el flujo de ingreso desde
 *   los modales y el controlador se elimina.
 * - 2026-10-10  [Valery D. Ortuno P]  docs: la central de riesgos ya no es un
 *   mock; su vista incrusta el componente Livewire ListaEstudiantes (HU 10).
 * - 2026-10-10  [Alisson D. Alvarado]  fix: la central de riesgos se enruta a
 *   su componente Livewire (`CentralRiesgo\Index`), que consume
 *   `ListarIncidenciasTramposoService` (el mismo listado del endpoint
 *   `/api/central-riesgo`) en vez de pintar datos mockeados.
 * - 2026-10-11  [OchoaCesar]  feat: ruta temporal /dev/login, registrada solo
 *   en APP_ENV=local, que inicia sesión con el usuario 1 para poder probar el
 *   modal de detalle de incidencia (la API exige sesión y aún no existe
 *   pantalla de login).
 * - 2026-10-12  [T1]  refactor: se elimina la pantalla intermedia de registro
 *   de ingreso (rutas /monitoreo/registro-ingreso y /buscador-incidencia): el
 *   monitor abre directo el formulario de incidencia, que ahora trae su propio
 *   buscador. Queda solo /registrar-incidencia.
 * - 2026-10-21  [Alex Candia]  feat: /reportes deja de ser vista mock. La ruta
 *   delega en ListarFiltrosReporteService, que entrega las materias y los
 *   exámenes agrupados por curso para los selectores dinámicos.
 */

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ExamenController;
use App\Livewire\Examenes\EstudiantesCurso;
use App\Livewire\Monitoreo\MonitorEnVivo;
use App\Livewire\Monitoreo\AccesoMonitor;
use App\Livewire\Monitoreo\RegistrarIncidencia;
use App\Livewire\Auxiliares\GestionAuxiliares;
use App\Models\Curso;
use App\Services\Curso\ListarMateriasConMetricasService;
use App\Models\Usuario;
use App\Services\Ambiente\ListarAmbientesService;
use App\Services\Examen\ListarEstudiantesCursoConEstadoService;
use App\Services\Material\ListarMaterialesService;
use App\Services\Norma\ListarNormasService;
use App\Services\Reporte\ListarFiltrosReporteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function (): void {
Route::view('/', 'pages.inicio')->name('inicio');

Route::view('/inicio', 'pages.inicio')->name('inicio');
Route::get('/materias', fn () => view('pages.materias', [
    'materias' => app(ListarMateriasConMetricasService::class)->ejecutar(),
]))->name('materias');
Route::view('/examenes', 'pages.examenes')->name('examenes');
Route::get('/monitoreo', MonitorEnVivo::class)->name('monitoreo');
Route::get('/monitoreo/acceso', AccesoMonitor::class)->name('monitoreo.acceso');
Route::get('/auxiliares', GestionAuxiliares::class)->name('auxiliares');
Route::view('/monitoreo/mock', 'livewire.monitoreo.mock')->name('monitoreo.mock');
// La vista solo aporta el layout; los datos los resuelve el componente Livewire
// ListaEstudiantes que incrusta, por eso la ruta sigue sin lógica.
Route::view('/central-riesgo', 'pages.central-riesgo')->name('central-riesgo');
Route::view('/usuarios', 'pages.usuarios')->name('usuarios');

Route::get('/reportes', fn (Request $request, ListarFiltrosReporteService $filtros) => view(
    'pages.reportes',
    $filtros->ejecutar($request->user()),
))->name('reportes');
Route::view('/notificaciones', 'pages.notificaciones')->name('notificaciones');

Route::get('/cursos/{curso}/estudiantes/estado', EstudiantesCurso::class)
    ->name('cursos.estudiantes.estado');

Route::get('/materias/{curso}', function (Curso $curso) {
    $conteos = app(ListarEstudiantesCursoConEstadoService::class)
        ->conteosPorEstado($curso->id_curso);

    return view('pages.materia-estudiantes', [
        'curso' => $curso,
        'conteos' => $conteos,
        // Catálogos del modal de alta de examen: el buscador de ambientes los
        // filtra en el navegador, así que se resuelven enteros y no por curso.
        'ambientes' => app(ListarAmbientesService::class)->ejecutar(),
        'materiales' => app(ListarMaterialesService::class)->ejecutar(),
        'normas' => app(ListarNormasService::class)->ejecutar(),
        // Vista de detalle: x-ui.page-header muestra la flecha "Volver" en vez
        // del hamburguesa cuando se le pasa $backRoute.
        'backRoute' => route('materias'),
    ]);
})->name('materias.detalle');

// Alta de examen desde el modal de la pestaña Exámenes. La validación vive en
// StoreExamenRequest y el alta en RegistrarExamenService; la ruta solo enruta.
Route::post('/cursos/{curso}/examenes', [ExamenController::class, 'store'])
    ->name('cursos.examenes.store');

// Destino de "Ver detalle" de las notificaciones (HU 12 · T4). La pantalla de
// detalle aún no existe: se deja la ruta lista con un placeholder navegable.
Route::get('/incidencias/{id}', fn (int $id) => view('pages.incidencia-detalle', ['id' => $id]))
    ->whereNumber('id')
    ->name('incidencias.detalle');

// TODO(@valerydariana98, 2026-09-25): proteger con el middleware de rol que
// restringe el registro de incidencias a docentes y auxiliares (#69).
Route::get('/registrar-incidencia', RegistrarIncidencia::class)->name('registrar-incidencia');
});

// Autenticación real (reemplaza a /dev/login).
Route::middleware('guest')->group(function (): void {
    Route::view('/login', 'pages.login')->name('login');
    Route::view('/registro', 'pages.registro-docente')->name('registro');
    Route::view('/solicitud-revision', 'pages.solicitud-revision')->name('solicitud-revision');
    Route::post('/login', [AuthController::class, 'login'])->name('login.store');
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware(['auth', 'docente'])->group(function (): void {
    Route::view('/docentes', 'pages.docentes')->name('docentes');
});
