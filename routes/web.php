<?php

use App\Livewire\Examenes\EstudiantesCurso;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::view('/inicio', 'pages.inicio')->name('inicio');
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
