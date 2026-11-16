{{--
    @file    examenes-dividir.blade.php
    @author  Alex Candia <alex.leonar.candia@gmail.com>
    @author  Valery D. Ortuno P <valerydariana98@gmail.com>
    @created 2026-11-15

    @description
    Página de la subopción "Dividir examen": reutiliza el parcial de la sección
    Exámenes acotado a la pestaña Dividir con $soloTab. Muestra el selector de
    materia y, debajo, el selector del examen y —si tiene dos o más aulas— la
    configuración de división (previsualización y confirmación por la API, de
    DividirExamenService), sin la barra de pestañas.

    @changelog
    - 2026-11-15  [Diego Tejerina]  feat: página propia de Dividir examen
      reutilizando el parcial acotado.

    @see  resources/views/partials/examenes-curso.blade.php
    @see  App\Http\Controllers\ExamenController::dividirVista()
    @see  App\Services\Examen\DividirExamenService
--}}

@extends('layouts.app')

@section('title', 'Dividir examen')

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
