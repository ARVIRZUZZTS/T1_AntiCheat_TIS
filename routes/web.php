<?php

//use Illuminate\Support\Facades\Route;

//Route::get('/', function () {
  //  return view('welcome');
//});
use App\Livewire\Asistencia\Lista;
use Illuminate\Support\Facades\Route;

// TEMPORAL: sin auth, para probar durante el sprint
Route::get('/examenes/{idExamen}/asistencia', Lista::class)
    ->name('asistencia.lista');
