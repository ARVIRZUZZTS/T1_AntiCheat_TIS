<?php

/**
 * @file    web.php
 *
 * @author  David E. Chavez T. <virzuzz12345@gmail.com>
 *
 * @created 2026-09-29
 *
 * @updated 2026-10-09
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
 * - 2026-10-09  [Alisson D. Alvarado]  refactor: la ruta de la central de
 *   riesgos pasa a ser una vista simple, ya que la pantalla es solo interfaz
 *   (datos mockeados) y llamaba a ListarAlertasService, que fallaba al leer
 *   `estado_incidencia`, columna aún no disponible en la base.
 */

use App\Http\Controllers\ExamenController;
use App\Http\Controllers\MonitoreoController;
use App\Livewire\Examenes\EstudiantesCurso;
use App\Livewire\Monitoreo\BuscadorRegistro;
use App\Livewire\Monitoreo\RegistrarIncidencia;
use App\Models\Curso;
use App\Services\Ambiente\ListarAmbientesService;
use App\Services\Examen\ListarEstudiantesCursoConEstadoService;
use App\Services\Examen\ListarExamenesCursoService;
use App\Services\Material\ListarMaterialesService;
use App\Services\Norma\ListarNormasService;
use Illuminate\Support\Facades\Route;

Route::view('/', 'pages.inicio')->name('inicio');

Route::view('/inicio', 'pages.inicio')->name('inicio');
Route::get('/materias', fn () => view('pages.materias', [
    'cursos' => Curso::query()->orderBy('id_curso')->get(),
]))->name('materias');
Route::view('/examenes', 'pages.examenes')->name('examenes');
Route::get('/monitoreo', MonitoreoController::class)->name('monitoreo');
Route::get('/monitoreo/registro-ingreso', BuscadorRegistro::class)->name('monitoreo.registro-ingreso');
// Por ahora la central de riesgos es solo interfaz con datos mockeados: la
// ruta renderiza la vista sin consultar la base, hasta que exista el endpoint.
Route::view('/central-riesgo', 'pages.central-riesgo')->name('central-riesgo');
Route::view('/usuarios', 'pages.usuarios')->name('usuarios');
Route::view('/reportes', 'pages.reportes')->name('reportes');
Route::view('/notificaciones', 'pages.notificaciones')->name('notificaciones');

Route::get('/cursos/{curso}/estudiantes/estado', EstudiantesCurso::class)
    ->name('cursos.estudiantes.estado');

Route::get('/materias/{curso}', function (Curso $curso) {
    $conteos = app(ListarEstudiantesCursoConEstadoService::class)
        ->conteosPorEstado($curso->id_curso);

    return view('pages.materia-estudiantes', [
        'curso' => $curso,
        'conteos' => $conteos,
        'examenes' => app(ListarExamenesCursoService::class)->ejecutar($curso->id_curso),
        // Catálogos del modal de alta de examen: el buscador de ambientes los
        // filtra en el navegador, así que se resuelven enteros y no por curso.
        'ambientes' => app(ListarAmbientesService::class)->ejecutar(),
        'materiales' => app(ListarMaterialesService::class)->ejecutar(),
        'normas' => app(ListarNormasService::class)->ejecutar(),
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
Route::get('/buscador-incidencia', RegistrarIncidencia::class)->name('buscador-incidencia');
Route::get('/registrar-incidencia', RegistrarIncidencia::class)->name('registrar-incidencia');
