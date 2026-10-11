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
 * @description Rutas JSON del backend.
 *
 * @changelog
 * - 2026-10-09  [Diego Tejerina]  feat: agregar listado de exámenes por curso (#138).
 * - 2026-10-09  [Diego Tejerina]  feat: confirmar y rechazar incidencias (#142).
 * - 2026-10-09  [Alisson D. Alvarado]  feat: detalle de incidencia de la central
 *   de riesgos, con error controlado 404 si no existe.
 */

use App\Http\Controllers\Api\CentralRiesgoController;
use App\Http\Controllers\Api\CursoEstudianteController;
use App\Http\Controllers\Api\CursoExamenController;
use App\Http\Controllers\Api\DetalleIncidenciaController;
use App\Http\Controllers\Api\EstudianteExamenController;
use App\Http\Controllers\Api\ExamenMonitoreoController;
use App\Http\Controllers\Api\ResolverIncidenciaController;
use Illuminate\Support\Facades\Route;

// El proyecto autentica con sesiones Laravel: web aporta sesión y protección CSRF.
Route::middleware(['web', 'auth:web'])->group(function (): void {
    Route::post('/incidencias/{idIncidencia}/confirmar', [ResolverIncidenciaController::class, 'confirmar'])
        ->whereNumber('idIncidencia')
        ->name('api.incidencias.confirmar');

    Route::post('/incidencias/{idIncidencia}/rechazar', [ResolverIncidenciaController::class, 'rechazar'])
        ->whereNumber('idIncidencia')
        ->name('api.incidencias.rechazar');

    // Detalle de una incidencia: lo consume la futura vista "ver detalle"
    // de la central de riesgos. Requiere sesión porque expone datos del estudiante.
    Route::get('/incidencias/{idIncidencia}', [DetalleIncidenciaController::class, 'index'])
        ->whereNumber('idIncidencia')
        ->name('api.incidencias.detalle');
});
Route::get('/cursos/{idCurso}/examenes', [CursoExamenController::class, 'index'])
    ->whereNumber('idCurso')
    ->name('api.cursos.examenes.listar');

Route::get('/cursos/{idCurso}/estudiantes', [CursoEstudianteController::class, 'index'])
    ->name('api.cursos.estudiantes.listar');

Route::get('/cursos/{idCurso}/estudiantes/estado', [EstudianteExamenController::class, 'index'])
    ->name('api.cursos.estudiantes.estado.listar');

Route::get('/examenes/{idExamen}/monitoreo', [ExamenMonitoreoController::class, 'asistencia'])
    ->name('api.examenes.monitoreo.asistencia');

Route::get('/central-riesgo', [CentralRiesgoController::class, 'index'])
    ->name('api.central-riesgo.listar');
