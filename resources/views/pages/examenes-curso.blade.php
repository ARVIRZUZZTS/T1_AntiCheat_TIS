{{--
    @file    examenes-curso.blade.php
    @author  Valery D. Ortuno P <valerydariana98@gmail.com>
    @created 2026-10-24

    @description
    Exámenes de un curso, a la que se llega desde la sección Exámenes
    eligiendo un curso y "Ver exámenes". La página solo aporta el encabezado
    (nombre del curso y flecha de volver a Exámenes); el listado, sus botones
    y la selección de un examen viven en el parcial examenes-curso.

    @changelog
    - 2026-10-24  [Valery D. Ortuno P]  feat: creación inicial de la página.

    @see  App\Http\Controllers\ExamenController
    @see  resources/views/partials/examenes-curso.blade.php
--}}

@extends('layouts.app')

@section('title', $curso->nombre_curso)

@section('subtitle', 'Examenes del curso')

@section('content')
    <div class="px-4 py-4 sm:px-6 sm:py-6 lg:px-8">
        @include('partials.examenes-curso', ['curso' => $curso])
    </div>
@endsection
