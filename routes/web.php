<?php

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
