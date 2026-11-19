{{--
    @file    examenes-eliminar.blade.php
    @author  Valery D. Ortuno P <valerydariana98@gmail.com>
    @created 2026-11-19

    @description
    Pagina de la subopcion "Eliminar examen": reutiliza el parcial de la seccion
    Examenes acotado a la pestana de lista con $soloTab. Al elegir un curso,
    cada tarjeta ofrece "Eliminar", con el modal de confirmacion y el bloqueo de
    examenes ya iniciados que ya trae el parcial (OchoaCesar / Alex Candia).

    @changelog
    - 2026-11-19  [Valery D. Ortuno P]  feat: pagina propia de Eliminar examen
      reutilizando el parcial acotado.

    @see  resources/views/partials/examenes-curso.blade.php
    @see  App\Http\Controllers\ExamenController::eliminarVista()
--}}

@extends('layouts.app')

@section('title', 'Eliminar examen')

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
                'puedeCrearExamen' => false,
                'soloTab' => $soloTab,
            ])
        @endif
    </div>
@endsection
