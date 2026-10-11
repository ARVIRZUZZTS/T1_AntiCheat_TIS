{{--
    @file    central-riesgo.blade.php
    @author  David E. Chavez T. <virzuzz12345@gmail.com>
    @created 2026-09-29
    @updated 2026-10-10

    @description
    Pantalla de la central de riesgos. Aporta el título y el layout del panel;
    la lista de estudiantes sancionados, sus filtros y el detalle de la
    incidencia viven en el componente Livewire ListaEstudiantes.

    @changelog
    - 2026-09-24  [David E. Chavez T.]  feat: creación inicial de la vista.
    - 2026-09-28  [Candy]  feat: botón de registrar incidencia con entrada a
      `origen=central-riesgo`, sin estudiante ni rol en la URL.
    - 2026-09-29  [David E. Chavez T.]  refactor: header y espaciado consistente.
    - 2026-10-09  [Alisson D. Alvarado]  feat: rediseño de la pantalla con tabs
      tipo píldora, buscador (sin funcionalidad) y lista mock en cards (móvil)
      y tabla completa clickeable (desktop), con estados vacíos y banner de
      bloqueo automático.
    - 2026-10-10  [T1]  fix: reponer el botón "Registrar incidencia" que se
      perdió en el rediseño; abre el formulario con `origen=central-riesgo`.
    - 2026-10-10  [Valery D. Ortuno P]  feat: la lista deja de estar mockeada;
      la pantalla incrusta el componente Livewire con los datos reales (HU 10).

    @see  App\Livewire\CentralRiesgo\ListaEstudiantes
--}}

@extends('layouts.app')

@section('title', 'Central de riesgos')

@section('content')
    @livewire(\App\Livewire\CentralRiesgo\ListaEstudiantes::class)
@endsection
