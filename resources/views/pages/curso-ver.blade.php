{{--
    @file    curso-ver.blade.php
    @author  OchoaCesar <cesareduardonick@gmail.com>
    @author  Diego Tejerina <josediegotejerinamolina@gmail.com>
    @created 2026-11-15
    @updated 2026-11-19

    @description
    Página de las subopciones "Ver estudiantes" y "Habilitar estudiantes" del
     sidebar: un selector con los cursos activos del docente y, debajo, solo la
     sección pedida del elegido (lista de estudiantes en lectura o habilitación, sin
    tarjetas de conteo), reutilizando el parcial curso-contenido. El selector es
    server-driven: al cambiarlo recarga con ?curso=ID. Si se llega desde la
    lista con ese query, viene preseleccionado.

    @changelog
    - 2026-11-15  [Diego Tejerina]  feat: página con selector de curso + el
      contenido del detalle (OchoaCesar) reutilizado debajo.
    - 2026-11-15  [Alex Candia]  refactor: la página sirve las subopciones
      "Ver estudiantes" y "Habilitar estudiantes" (título, acción del selector
      y sección según $soloTab).
    - 2026-11-19  [Valery D. Ortuno P]  refactor: las dos paginas dejan de
      recibir y mostrar los conteos del curso (Inscritos, Habilitados,
      Deshabilitados, Central de Riesgo); basta con el curso elegido.

    @see  App\Http\Controllers\CursoController::verEstudiantes()
    @see  App\Http\Controllers\CursoController::habilitarEstudiantes()
    @see  resources/views/partials/curso-contenido.blade.php
--}}

@extends('layouts.app')

@section('title', $titulo)

@section('content')
    @if (session('mensaje'))
        <x-ui.modal-aviso :mensaje="session('mensaje')" />
    @endif

    <div class="px-4 pt-4 sm:px-6 sm:pt-6 lg:px-8">
        @if ($cursos->isEmpty())
            <p class="mx-auto max-w-2xl rounded-base border border-default bg-neutral-primary-soft p-8 text-center text-sm text-body shadow-xs">
                No tienes cursos registrados.
            </p>
        @else
            <form method="GET" action="{{ $accionUrl }}"
                  class="mx-auto max-w-2xl rounded-base border border-default bg-neutral-primary-soft p-4 shadow-xs lg:mx-0">
                <x-ui.select
                    label="Curso y gestión"
                    name="curso"
                    :options="$cursos->mapWithKeys(fn ($curso) => [
                        $curso->id_curso => $curso->nombre_curso.($curso->gestion ? ' - '.$curso->gestion : ''),
                    ])->all()"
                    :selected="$cursoSeleccionado?->id_curso"
                    placeholder="Seleccione un curso"
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

    @if ($cursoSeleccionado)
        @include('partials.curso-contenido', [
            'curso' => $cursoSeleccionado,
            'soloTab' => $soloTab,
        ])
    @elseif ($cursos->isNotEmpty())
        <p class="mx-auto mt-4 max-w-2xl px-4 text-sm text-body sm:px-6 lg:mx-0 lg:px-8">
            Elige un curso para continuar.
        </p>
    @endif
@endsection
