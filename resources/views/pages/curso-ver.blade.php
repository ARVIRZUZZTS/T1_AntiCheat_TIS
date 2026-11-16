{{--
    @file    curso-ver.blade.php
    @author  OchoaCesar <cesareduardonick@gmail.com>
    @author  Diego Tejerina <josediegotejerinamolina@gmail.com>
    @created 2026-11-15

    @description
    Página de las subopciones "Ver estudiantes" y "Habilitar estudiantes" del
    sidebar: un selector con los cursos del docente y, debajo, solo la sección
    pedida del elegido (tarjetas de conteo + lista de estudiantes en lectura o
    habilitación), reutilizando el parcial curso-contenido. El selector es
    server-driven: al cambiarlo recarga con ?curso=ID. Si se llega desde la
    lista con ese query, viene preseleccionado.

    @changelog
    - 2026-11-15  [Diego Tejerina]  feat: página con selector de curso + el
      contenido del detalle (OchoaCesar) reutilizado debajo.
    - 2026-11-15  [Alex Candia]  refactor: la página sirve las subopciones
      "Ver estudiantes" y "Habilitar estudiantes" (título, acción del selector
      y sección según $soloTab).

    @see  App\Http\Controllers\CursoController::verEstudiantes()
    @see  App\Http\Controllers\CursoController::habilitarEstudiantes()
    @see  resources/views/partials/curso-contenido.blade.php
--}}

@extends('layouts.app')

@section('title', $titulo)

@section('content')
    <div class="px-4 pt-4 sm:px-6 sm:pt-6 lg:px-8">
        @if ($cursos->isEmpty())
            <p class="mx-auto max-w-2xl rounded-base border border-default bg-neutral-primary-soft p-8 text-center text-sm text-body shadow-xs">
                No tienes cursos registrados.
            </p>
        @else
            <form method="GET" action="{{ $accionUrl }}"
                  class="mx-auto max-w-2xl rounded-base border border-default bg-neutral-primary-soft p-4 shadow-xs lg:mx-0">
                <x-ui.select
                    label="Curso"
                    name="curso"
                    :options="$cursos->mapWithKeys(fn ($curso) => [$curso->id_curso => $curso->nombre_curso])->all()"
                    :selected="$cursoSeleccionado?->id_curso"
                    placeholder="Selecciona un curso"
                    onchange="this.form.submit()"
                />
                <noscript>
                    <div class="mt-3">
                        <x-ui.button type="submit">Ver</x-ui.button>
                    </div>
                </noscript>
            </form>
        @endif
    </div>

    @if ($cursoSeleccionado && $conteos !== null)
        @include('partials.curso-contenido', [
            'curso' => $cursoSeleccionado,
            'conteos' => $conteos,
            'soloTab' => $soloTab,
        ])
    @elseif ($cursos->isNotEmpty())
        <p class="mx-auto mt-4 max-w-2xl px-4 text-sm text-body sm:px-6 lg:mx-0 lg:px-8">
            Elige un curso para continuar.
        </p>
    @endif
@endsection
