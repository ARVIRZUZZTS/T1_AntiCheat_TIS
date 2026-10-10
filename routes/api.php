<?php

/**
 * @file    api.php
 *
 * @author  T1 - Tech One SRL
 *
 * @created 2026-09-24
 *
 * @updated 2026-10-09
 *
 * @description Rutas de consulta JSON del backend.
 *
 * @changelog
 * - 2026-10-09  [Diego Tejerina]  feat: agregar listado de exámenes por curso (#138).
 */

use App\Http\Controllers\Api\CursoEstudianteController;
use App\Http\Controllers\Api\CursoExamenController;
use App\Http\Controllers\Api\EstudianteExamenController;
use App\Http\Controllers\Api\ExamenMonitoreoController;
use Illuminate\Support\Facades\Route;

Route::get('/cursos/{idCurso}/examenes', [CursoExamenController::class, 'index'])
    ->whereNumber('idCurso')
    ->name('api.cursos.examenes.listar');

Route::get('/cursos/{idCurso}/estudiantes', [CursoEstudianteController::class, 'index'])
    ->name('api.cursos.estudiantes.listar');

Route::get('/cursos/{idCurso}/estudiantes/estado', [EstudianteExamenController::class, 'index'])
    ->name('api.cursos.estudiantes.estado.listar');

Route::get('/examenes/{idExamen}/monitoreo', [ExamenMonitoreoController::class, 'asistencia'])
    ->name('api.examenes.monitoreo.asistencia');
