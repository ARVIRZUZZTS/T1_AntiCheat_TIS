{{--
    @file    examenes.blade.php
    @author  David E. Chavez T. <virzuzz12345@gmail.com>
    @created 2026-09-29
    @updated 2026-09-29
    @updated 2026-10-24

    @description
    Sección Exámenes del docente: botones "Ver exámenes" y "Crear examen" y,
    debajo, la tabla de sus cursos activos. Se elige un curso tocando su fila
    y recién ahí se activan los botones, que llevan a los exámenes de ese
    curso o al formulario de alta de un examen para ese curso.

    La selección es solo de Alpine: los botones arman la URL con el id del
    curso elegido, sin ida y vuelta al servidor.

    @changelog
    - 2026-09-29  [David E. Chavez T.]  feat: vista de exámenes.
    - 2026-10-12  [Diego Tejerina]  fix: la tabla fija un ancho mínimo
      (min-w-[640px]) para que en mobile las columnas no se aplasten; el
      scroll horizontal queda dentro de la tabla, no en la página.
    - 2026-10-24  [Valery D. Ortuno P]  feat: la vista deja de ser un mock: lista
      los cursos activos del docente para elegir uno y ver o crear sus exámenes.
    - 2026-10-24  [Valery D. Ortuno P]  style: la tabla con radio pasa a filas
      seleccionables con el anillo azul (como las tarjetas de exámenes), un
      clic fuera de las filas suelta la selección y los textos visibles van
      sin acentos.

    @see  App\Http\Controllers\ExamenController
    @see  App\Services\Curso\ListarCursosDocenteService
    @see  resources/views/pages/examenes-curso.blade.php
    @see  resources/views/pages/examen-crear.blade.php
--}}

@extends('layouts.app')

@section('title', 'Examenes')

@php
    // Clases de x-ui.button: se repiten acá porque el estado deshabilitado
    // depende de la selección de Alpine y el componente lo resuelve en Blade.
    $botonBase = 'inline-flex items-center justify-center box-border border focus:ring-4 shadow-xs font-medium leading-5 focus:outline-none rounded-base px-6 py-2.5 text-sm font-semibold shrink-0 whitespace-nowrap';
    $botonActivo = 'text-white bg-brand border-transparent hover:bg-brand-strong focus:ring-brand-medium cursor-pointer';
    $botonInactivo = 'text-fg-disabled bg-disabled border-default-medium cursor-not-allowed';

    // El id real lo pone Alpine; la ruta se arma una sola vez con un marcador.
    $urlVer = route('examenes.curso', ['curso' => '__CURSO__']);
    $urlCrear = route('examenes.crear', ['curso' => '__CURSO__']);
@endphp

@section('content')
    <div
        x-data="{
            seleccionado: null,
            ir(plantilla) {
                if (this.seleccionado === null) {
                    return;
                }

                window.location.href = plantilla.replace('__CURSO__', this.seleccionado);
            },
            // Un clic que no cae en una fila ni en los botones que la usan
            // suelta la selección. Los clics fuera del componente los cubre
            // @click.outside.
            soltarSeleccion(evento) {
                if (! evento.target.closest('[data-seleccionable], [data-accion-seleccion]')) {
                    this.seleccionado = null;
                }
            }
        }"
        @click="soltarSeleccion($event)"
        @click.outside="seleccionado = null"
        class="px-4 py-4 sm:px-6 sm:py-6 lg:px-8"
    >
        <div class="flex gap-2 overflow-x-auto" role="group" aria-label="Acciones de examenes">
            <button type="button" class="{{ $botonBase }}" data-accion-seleccion
                    :class="seleccionado === null ? '{{ $botonInactivo }}' : '{{ $botonActivo }}'"
                    :disabled="seleccionado === null"
                    @click="ir(@js($urlVer))">
                Ver examenes
            </button>
            <button type="button" class="{{ $botonBase }}" data-accion-seleccion
                    :class="seleccionado === null ? '{{ $botonInactivo }}' : '{{ $botonActivo }}'"
                    :disabled="seleccionado === null"
                    @click="ir(@js($urlCrear))">
                Crear examen
            </button>
        </div>

        <p class="mt-4 text-sm text-body" x-show="seleccionado === null">
            Selecciona un curso para ver o crear sus examenes.
        </p>

        @if ($cursos->isEmpty())
            <p class="mt-4 rounded-base border border-default bg-neutral-primary-soft p-8 text-center text-sm text-body shadow-xs">
                No tienes cursos activos.
            </p>
        @else
            {{-- Filas seleccionables: la fila elegida queda con el anillo azul,
                 el mismo de las tarjetas de examenes y de cursos. En mobile
                 cada fila apila sus datos con su etiqueta. --}}
            <div class="mt-4 hidden grid-cols-3 gap-4 px-6 text-sm font-medium text-body sm:grid" aria-hidden="true">
                <span>Curso</span>
                <span>Estudiantes</span>
                <span>Examenes</span>
            </div>

            <ul class="mt-2 flex flex-col gap-2" role="listbox" aria-label="Cursos activos">
                @foreach ($cursos as $curso)
                    <li role="option" tabindex="0" data-seleccionable
                        :aria-selected="seleccionado === {{ $curso->id_curso }} ? 'true' : 'false'"
                        :class="seleccionado === {{ $curso->id_curso }} ? 'border-brand ring-2 ring-brand' : 'border-default hover:border-brand-subtle'"
                        @click="seleccionado = {{ $curso->id_curso }}"
                        @keydown.enter.prevent="seleccionado = {{ $curso->id_curso }}"
                        @keydown.space.prevent="seleccionado = {{ $curso->id_curso }}"
                        class="grid cursor-pointer grid-cols-2 gap-2 rounded-base border bg-neutral-primary-soft px-6 py-4 text-sm text-body shadow-xs sm:grid-cols-3 sm:gap-4">
                        <span class="col-span-2 font-medium text-heading sm:col-span-1">{{ $curso->nombre_curso }}</span>
                        <span><span class="sm:hidden">Estudiantes: </span>{{ $curso->estudiantes_count }}</span>
                        <span><span class="sm:hidden">Examenes: </span>{{ $curso->examenes_count }}</span>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
@endsection
