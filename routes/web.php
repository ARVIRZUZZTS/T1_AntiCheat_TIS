<?php

use App\Livewire\Examenes\EstudiantesCurso;
use App\Livewire\Monitoreo\RegistrarIncidencia;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::view('/inicio', 'pages.inicio')->name('inicio');
// Pantalla de Inicio del docente: notificaciones de posibles tramposos (HU 12).
Route::view('/notificaciones', 'pages.notificaciones')->name('notificaciones');
Route::view('/materias', 'pages.materias')->name('materias');
Route::view('/examenes', 'pages.examenes')->name('examenes');
Route::view('/monitoreo', 'pages.monitoreo')->name('monitoreo');
Route::view('/central-riesgo', 'pages.central-riesgo')->name('central-riesgo');
Route::view('/usuarios', 'pages.usuarios')->name('usuarios');
Route::view('/reportes', 'pages.reportes')->name('reportes');

Route::get('/cursos/{curso}/estudiantes/estado', EstudiantesCurso::class)
    ->name('cursos.estudiantes.estado');

Route::get('/materias/{materia}', fn (string $codigo) => view('pages.materia-estudiantes', ['codigo' => $codigo]))
    ->name('materias.detalle');

// Destino de "Ver detalle" de las notificaciones (HU 12 · T4). La pantalla de
// detalle aún no existe: se deja la ruta lista con un placeholder navegable.
Route::get('/incidencias/{id}', fn (int $id) => view('pages.incidencia-detalle', ['id' => $id]))
    ->whereNumber('id')
    ->name('incidencias.detalle');

// TODO(@valerydariana98, 2026-09-25): proteger con el middleware de rol que
// restringe el registro de incidencias a docentes y auxiliares (#69).
Route::get('/registrar-incidencia', RegistrarIncidencia::class)->name('registrar-incidencia');
