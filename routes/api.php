<?php

use App\Http\Controllers\Api\CursoEstudianteController;
use App\Http\Controllers\Api\AsistenciaController;
use Illuminate\Support\Facades\Route;

Route::get('/cursos/{idCurso}/estudiantes', [CursoEstudianteController::class, 'index'])
    ->name('api.cursos.estudiantes.listar');
Route::post('/examenes/{idExamen}/asistencia', [AsistenciaController::class, 'store'])
    ->name('api.examenes.asistencia.registrar');