{{--
    @file    auxiliar-cursos.blade.php
    @author  Valery D. Ortuno P <valerydariana98@gmail.com>
    @created 2026-10-24

    @description
    "Cursos" del auxiliar. No es la sección Cursos del docente: acá irán los
    cursos a los que el auxiliar está asignado y, al elegir uno, los exámenes
    que le tocan. Por ahora es un marcador navegable; el contenido se define
    en una HU posterior.

    @changelog
    - 2026-10-24  [Valery D. Ortuno P]  feat: creación inicial de la página.

    @see  resources/views/components/ui/sidebar.blade.php
--}}

@extends('layouts.app')

@section('title', 'Cursos')

@section('content')
    <div class="px-4 py-4 sm:px-6 sm:py-6 lg:px-8">
        <div class="rounded-base border border-default bg-neutral-primary-soft p-8 text-center shadow-xs">
            <p class="text-sm text-body">Pronto podras ver aqui los cursos a los que estas asignado.</p>
        </div>
    </div>
@endsection
