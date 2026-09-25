<?php

use App\Http\Controllers\Api\CursoEstudianteController;
use Illuminate\Support\Facades\Route;

Route::get('/cursos/{idCurso}/estudiantes', [CursoEstudianteController::class, 'index'])
    ->name('api.cursos.estudiantes.listar');