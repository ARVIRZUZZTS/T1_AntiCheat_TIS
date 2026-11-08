{{--
    @file    docentes.blade.php
    @author  T1 - Tech One SRL
    @created 2026-10-17
    @updated 2026-10-17
    @updated 2026-11-08
    @description
    Pantalla "Docentes": validación del registro de nuevos docentes. Aporta el
    título y el layout; las solicitudes, los contadores y los modales viven en
    el componente Livewire ValidarRegistros.

    @changelog
    - 2026-10-17  [T1]  feat: creación inicial de la vista (mock).
    - 2026-11-08  [Valery D. Ortuno P]  feat: la lista deja de estar mockeada:
      incrusta el componente ValidarRegistros con las solicitudes reales y
      sus modales (HU 26). Se quitan la pestaña única y el "Deshacer".

    @see  App\Livewire\Docentes\ValidarRegistros
--}}
@extends('layouts.app')

@section('title', 'Docentes')
@section('subtitle', 'Validacion de registros de docentes')

@section('content')
    @livewire(\App\Livewire\Docentes\ValidarRegistros::class)
@endsection
