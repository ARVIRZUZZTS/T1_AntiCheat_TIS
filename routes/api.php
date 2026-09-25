<?php

use App\Http\Controllers\Api\CursoEstudianteController;
use App\Http\Controllers\Api\ExamenMonitoreoController;
use Illuminate\Support\Facades\Route;

Route::get('/cursos/{idCurso}/estudiantes', [CursoEstudianteController::class, 'index'])
    ->name('api.cursos.estudiantes.listar');

Route::get('/examenes/{idExamen}/monitoreo', [ExamenMonitoreoController::class, 'asistencia'])
    ->name('api.examenes.monitoreo.asistencia');
