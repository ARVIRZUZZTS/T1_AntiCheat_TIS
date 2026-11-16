{{--
    @file    examenes-editar.blade.php
    @author  Alex Candia <alex.leonar.candia@gmail.com>
    @author  Valery D. Ortuno P <valerydariana98@gmail.com>
    @created 2026-11-15

    @description
    Página de la subopción "Editar examen": reutiliza el parcial de la sección
    Exámenes (de Alex Candia / Valery) acotado a la pestaña Editar con $soloTab,
    así que muestra solo el selector de materia, el selector del examen a editar
    (exámenes propios y futuros) y el formulario de edición, sin la barra de
    pestañas. La edición carga y guarda por la API como ya lo hacía.

    @changelog
    - 2026-11-15  [Diego Tejerina]  feat: página propia de Editar examen
      reutilizando el parcial acotado.

    @see  resources/views/partials/examenes-curso.blade.php
    @see  App\Http\Controllers\ExamenController::editarVista()
--}}

@extends('layouts.app')

@section('title', 'Editar examen')

@section('content')
    <div class="px-4 py-4 sm:px-6 sm:py-6 lg:px-8">
        @if ($cursos->isEmpty())
            <p class="rounded-base border border-default bg-neutral-primary-soft p-8 text-center text-sm text-body shadow-xs">
                No tienes cursos activos.
            </p>
        @else
            @include('partials.examenes-curso', [
                'cursos' => $cursos,
                'cursoInicial' => $cursoInicial,
                'ambientes' => $ambientes,
                'materiales' => $materiales,
                'normas' => $normas,
                'puedeCrearExamen' => true,
                'soloTab' => $soloTab,
            ])
        @endif
    </div>
@endsection
