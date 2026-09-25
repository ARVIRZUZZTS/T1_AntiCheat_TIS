<?php

use App\Http\Controllers\ExamMonitoringController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/examenes/{examen}/monitoreo', [ExamMonitoringController::class, 'attendance']);
