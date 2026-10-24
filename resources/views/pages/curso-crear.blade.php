{{--
    @file    curso-crear.blade.php
    @author  Valery D. Ortuno P <valerydariana98@gmail.com>
    @created 2026-10-24

    @description
    "Crear curso": página propia (con flecha de volver a Cursos) para el
    formulario de alta de un curso. Por ahora solo arma la navegación; el
    formulario (materia + grupo, que juntos forman el nombre del curso, y sus
    validaciones) llega con la HU de creación de cursos.

    @changelog
    - 2026-10-24  [Valery D. Ortuno P]  feat: creación inicial de la página.

    @see  App\Http\Controllers\CursoController
--}}

@extends('layouts.app')

@section('title', 'Crear curso')

@section('content')
    <div class="px-4 py-4 sm:px-6 sm:py-6 lg:px-8">
        <div class="rounded-base border border-default bg-neutral-primary-soft p-8 text-center shadow-xs">
            <p class="text-sm text-body">El formulario de creacion de cursos estara disponible proximamente.</p>
        </div>
    </div>
@endsection
