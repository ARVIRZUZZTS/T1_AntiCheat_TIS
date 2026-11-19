{{--
    @file    central-riesgo-sospechosos.blade.php
    @author  Alisson D. Alvarado <alvaradoalissondalet@gmail.com>
    @author  Valery D. Ortuno P <valerydariana98@gmail.com>
    @created 2026-11-15

    @description
    Pagina "Sospechosos" de la central de riesgos: la lista de incidencias
    sospechosas que el docente debe revisar (confirmar o rechazar). La vista y la
    logica son de ListaEstudiantes (Valery D. Ortuno P / Alisson D. Alvarado);
    aqui solo se monta en modo 'sospechosos', que fija ese alcance y oculta las
    pestanhas de Institucion / Mis materias (esas viven en la pagina Incidencias).

    @changelog
    - 2026-11-09  [Alisson D. Alvarado]  feat: revisión de sospechosos del docente.
    - 2026-11-15  [Diego Tejerina]  refactor: se mueve a su propia página,
      separada de Incidencias.
    - 2026-11-19  [Valery D. Ortuno P]  refactor: el titulo pasa a "Incidencias pendientes", como en el sidebar.

    @see  App\Livewire\CentralRiesgo\ListaEstudiantes
--}}

@extends('layouts.app')

@section('title', 'Incidencias pendientes')

@section('content')
    @livewire(\App\Livewire\CentralRiesgo\ListaEstudiantes::class, ['modo' => 'sospechosos'])
@endsection
