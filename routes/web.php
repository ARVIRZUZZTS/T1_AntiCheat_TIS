<?php

/**
 * @file    web.php
 *
 * @author  David E. Chavez T. <virzuzz12345@gmail.com>
 *
 * @created 2026-09-29
 *
 * @updated 2026-10-05
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
 *   ambientes, que alimenta el buscador del modal de alta de examen.
 */

use App\Http\Controllers\MonitoreoController;
use App\Livewire\Examenes\EstudiantesCurso;
use App\Livewire\Monitoreo\BuscadorRegistro;
use App\Livewire\Monitoreo\RegistrarIncidencia;
use App\Models\Curso;
use App\Services\Ambiente\ListarAmbientesService;
use App\Services\CentralRiesgo\ListarAlertasService;
use App\Services\Examen\ListarEstudiantesCursoConEstadoService;
use App\Services\Examen\ListarExamenesCursoService;
use Illuminate\Support\Facades\Route;

Route::view('/', 'pages.inicio')->name('inicio');

Route::view('/inicio', 'pages.inicio')->name('inicio');
Route::get('/materias', fn () => view('pages.materias', [
    'cursos' => Curso::query()->orderBy('id_curso')->get(),
]))->name('materias');
Route::view('/examenes', 'pages.examenes')->name('examenes');
Route::get('/monitoreo', MonitoreoController::class)->name('monitoreo');
Route::get('/monitoreo/registro-ingreso', BuscadorRegistro::class)->name('monitoreo.registro-ingreso');
Route::get('/central-riesgo', fn () => view('pages.central-riesgo', [
    'alertas' => app(ListarAlertasService::class)->ejecutar(),
]))->name('central-riesgo');
Route::view('/usuarios', 'pages.usuarios')->name('usuarios');
Route::view('/reportes', 'pages.reportes')->name('reportes');

Route::get('/cursos/{curso}/estudiantes/estado', EstudiantesCurso::class)
    ->name('cursos.estudiantes.estado');

Route::get('/materias/{curso}', function (Curso $curso) {
    $conteos = app(ListarEstudiantesCursoConEstadoService::class)
        ->conteosPorEstado($curso->id_curso);

    return view('pages.materia-estudiantes', [
        'curso' => $curso,
        'conteos' => $conteos,
        'examenes' => app(ListarExamenesCursoService::class)->ejecutar($curso->id_curso),
        // Catálogo de ambientes para el buscador del modal de alta de examen.
        // Lo resuelve el catálogo entero y no el curso: un examen puede tomar en
        // más de una aula y el buscador filtra en el navegador.
        'ambientes' => app(ListarAmbientesService::class)->ejecutar(),
    ]);
})->name('materias.detalle');

// TODO(@valerydariana98, 2026-09-25): proteger con el middleware de rol que
// restringe el registro de incidencias a docentes y auxiliares (#69).
Route::get('/buscador-incidencia', RegistrarIncidencia::class)->name('buscador-incidencia');
Route::get('/registrar-incidencia', RegistrarIncidencia::class)->name('registrar-incidencia');
