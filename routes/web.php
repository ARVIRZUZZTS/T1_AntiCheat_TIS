<?php

/**
 * @file    web.php
 *
 * @author  David E. Chavez T. <virzuzz12345@gmail.com>
 *
 * @created 2026-09-29
 *
 * @updated 2026-09-29
 *
 * @description
 * Definición de rutas web del panel. Incluye vistas estáticas y componentes Livewire.
 *
 * @changelog
 * - 2026-09-29  [David E. Chavez T.]  feat: rutas web del panel.
 */

use App\Http\Controllers\MonitoreoController;
use App\Livewire\Examenes\EstudiantesCurso;
use App\Livewire\Monitoreo\RegistrarIncidencia;
use Illuminate\Support\Facades\Route;

Route::view('/', 'pages.inicio')->name('inicio');

Route::view('/inicio', 'pages.inicio');
Route::view('/materias', 'pages.materias')->name('materias');
Route::view('/materias/{codigo}', 'pages.materia-estudiantes')
    ->defaults('codigo', 'MAT-101')
    ->name('materias.detalle');
Route::view('/examenes', 'pages.examenes')->name('examenes');
Route::get('/monitoreo', MonitoreoController::class)->name('monitoreo');
Route::view('/central-riesgo', 'pages.central-riesgo')->name('central-riesgo');
Route::view('/usuarios', 'pages.usuarios')->name('usuarios');
Route::view('/reportes', 'pages.reportes')->name('reportes');

Route::get('/cursos/{curso}/estudiantes/estado', EstudiantesCurso::class)
    ->name('cursos.estudiantes.estado');

// TODO(@valerydariana98, 2026-09-25): proteger con el middleware de rol que
// restringe el registro de incidencias a docentes y auxiliares (#69).
Route::get('/registrar-incidencia', RegistrarIncidencia::class)->name('registrar-incidencia');
