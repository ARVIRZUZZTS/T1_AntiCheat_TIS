{{--
    @file    examenes-curso.blade.php
    @author  Valery D. Ortuno P <valerydariana98@gmail.com>
    @created 2026-10-24
    @updated 2026-10-25

    @description
    Exámenes de un curso. Página anterior, se mantiene por compatibilidad: la
    sección Exámenes ya es una sola página con selector de materia. Aporta el
    encabezado (nombre del curso y flecha de volver a Exámenes) y pasa los
    catálogos del formulario; las pestañas de listado, alta, invitación y
    división viven en el parcial examenes-curso.

    @changelog
    - 2026-10-24  [Valery D. Ortuno P]  feat: creación inicial de la página.
    - 2026-10-25  [Alex Candia]  feat: la página entrega los catálogos y el
      permiso de alta para incrustar el formulario en la pestaña Crear Examen.
    - 2026-10-25  [Alex Candia]  refactor: queda como página de compatibilidad;
      la sección Exámenes pasa a una sola página con selector de materia.

    @see  App\Http\Controllers\ExamenController
    @see  resources/views/partials/examenes-curso.blade.php
--}}

@extends('layouts.app')

@section('title', $curso->nombre_curso)

@section('subtitle', 'Examenes del curso')

@section('content')
    <div class="px-4 py-4 sm:px-6 sm:py-6 lg:px-8">
        @include('partials.examenes-curso', [
            'curso' => $curso,
            'ambientes' => $ambientes,
            'materiales' => $materiales,
            'normas' => $normas,
            'puedeCrearExamen' => $puedeCrearExamen,
        ])
    </div>
@endsection
