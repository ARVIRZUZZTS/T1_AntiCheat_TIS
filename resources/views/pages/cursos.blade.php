{{--
    @file    cursos.blade.php
    @author  David E. Chavez T. <virzuzz12345@gmail.com>
    @created 2026-09-29
    @updated 2026-10-12
    @updated 2026-10-24

    @description
    Lista de los cursos del docente autenticado, con los botones "Crear curso"
    y "Ver curso" arriba, buscador + píldoras de filtro (Todos / Activos) y una
    card de ancho completo por curso con sus métricas (inscritos, habilitados,
    deshabilitados, incidencias). Se elige un curso tocando su card (queda con
    borde azul) y recién ahí se activa "Ver curso", que lleva a
    `cursos.detalle`. "Crear curso" es otra página.

    Los cursos llegan ordenados desde ListarCursosDocenteService: primero los
    activos y abajo los finalizados (cursos pasados), cada grupo del más
    reciente al más antiguo. "Examen activo" es un dato derivado (hora actual
    dentro de la ventana del examen más reciente del curso), no una columna de
    la base; solo resalta el borde de la card.

    El grupo ya va dentro del nombre ("Calculo I - Grupo 2", ver Crear curso)
    y bajo el nombre se muestra la gestión con su año ("Segundo semestre
    2026"), que llega de ListarCursosDocenteService.

    La búsqueda y los filtros son solo de Alpine (mostrar/ocultar las cards
    ya traídas): la lista de cursos es chica y ya viaja completa en la
    página, así que no hace falta ida y vuelta a Livewire para filtrarla.

    @changelog
    - 2026-09-24  [ARVIRZUZZTS]  feat: creación inicial de la vista.
    - 2026-09-28  [T1]  feat: listar los cursos reales de la base y enlazar cada
      uno a su detalle por id_curso (#28).
    - 2026-09-29  [David E. Chavez T.]  refactor: header y espaciado consistente.
    - 2026-10-12  [Diego Tejerina]  feat: rediseño completo según Figma — cards
      de ancho completo con métricas reales (inscritos/habilitados/
      deshabilitados/incidencias), buscador y píldoras de filtro, borde
      destacado en materias con examen activo y fondo de aviso en las que no
      tienen estudiantes.
    - 2026-10-24  [Valery D. Ortuno P]  refactor: la vista pasa de materias.blade.php
      a cursos.blade.php ("Ver cursos"): solo lista los cursos del docente, suma
      los botones Ver cursos / Crear curso, los filtros pasan a Todos / Activos y
      el botón "Info" pasa a "Ver curso".
    - 2026-10-24  [Valery D. Ortuno P]  refactor: se quita el botón "Ver cursos";
      arriba quedan "Crear curso" y "Ver curso", que se activa al seleccionar la
      card de un curso (en vez de un botón por card). Un clic fuera de las cards
      suelta la selección.
    - 2026-10-24  [Valery D. Ortuno P]  feat: muestra el aviso "Curso creado con
      exito" al volver de Crear curso y el mensaje de lista vacia pasa a "No
      existen cursos registrados.". Los cursos sin estudiantes ya no se pintan
      de amarillo: un curso recien creado siempre empieza sin inscritos. Debajo
      del nombre se muestra la gestión con su año ("Segundo semestre 2026").

    @see  App\Http\Controllers\CursoController
    @see  App\Services\Curso\ListarCursosDocenteService
    @see  resources/views/pages/curso-detalle.blade.php
    @see  resources/views/pages/curso-crear.blade.php
--}}

@extends('layouts.app')

@section('title', 'Cursos')

@php
    // Clases de x-ui.button: se repiten acá porque el estado deshabilitado
    // depende de la selección de Alpine y el componente lo resuelve en Blade.
    $botonBase = 'inline-flex items-center justify-center box-border border focus:ring-4 shadow-xs font-medium leading-5 focus:outline-none rounded-base px-6 py-2.5 text-sm font-semibold shrink-0 whitespace-nowrap';
    $botonActivo = 'text-white bg-brand border-transparent hover:bg-brand-strong focus:ring-brand-medium cursor-pointer';
    $botonInactivo = 'text-fg-disabled bg-disabled border-default-medium cursor-not-allowed';

    // El id real lo pone Alpine; la ruta se arma una sola vez con un marcador.
    $urlVerCurso = route('cursos.detalle', ['curso' => '__CURSO__']);
@endphp

@section('content')
    <div
        x-data="{
            busqueda: '',
            filtro: 'todos',
            seleccionado: null,
            verCurso() {
                if (this.seleccionado === null) {
                    return;
                }

                window.location.href = @js($urlVerCurso).replace('__CURSO__', this.seleccionado);
            },
            // Un clic que no cae en una card ni en el botón que la usa suelta
            // la selección. Los clics fuera del componente los cubre
            // @click.outside.
            soltarSeleccion(evento) {
                if (! evento.target.closest('[data-seleccionable], [data-accion-seleccion]')) {
                    this.seleccionado = null;
                }
            },
            coincide(nombre, activo) {
                const termino = this.busqueda.trim().toLowerCase();

                if (termino !== '' && !nombre.toLowerCase().includes(termino)) {
                    return false;
                }

                if (this.filtro === 'activos' && !activo) {
                    return false;
                }

                return true;
            },
            cursos: @js($cursos->map(fn ($fila) => [
                'nombre' => $fila['curso']->nombre_curso,
                'activo' => $fila['curso']->estado === 'EnCurso',
            ])->values()),
            hayVisibles() {
                return this.cursos.some((curso) => this.coincide(curso.nombre, curso.activo));
            }
        }"
        @click="soltarSeleccion($event)"
        @click.outside="seleccionado = null"
        class="px-4 py-4 sm:px-6 sm:py-6 lg:px-8"
    >
        @php
            $totalActivos = $cursos->filter(fn ($fila) => $fila['curso']->estado === 'EnCurso')->count();

            $pillBase = 'shrink-0 whitespace-nowrap inline-flex items-center justify-center box-border border focus:ring-4 shadow-xs font-medium leading-5 rounded-full focus:outline-none px-4 py-2.5 text-sm';
            $pillActiva = 'text-white bg-brand border-transparent hover:bg-brand-strong focus:ring-brand-medium';
            $pillInactiva = 'text-body bg-neutral-primary-soft border-default hover:bg-neutral-secondary-medium hover:text-heading focus:ring-neutral-tertiary-soft';
        @endphp

        {{-- Aviso de éxito del alta: "Crear curso" vuelve acá al guardar. --}}
        @if (session('mensaje'))
            <x-ui.modal-aviso :mensaje="session('mensaje')" />
        @endif

        <div class="flex gap-2 overflow-x-auto" role="group" aria-label="Acciones de cursos">
            <x-ui.button :href="route('cursos.crear')" variant="default" class="shrink-0 whitespace-nowrap px-6 font-semibold">
                Crear curso
            </x-ui.button>
            <button type="button" class="{{ $botonBase }}" data-accion-seleccion
                    :class="seleccionado === null ? '{{ $botonInactivo }}' : '{{ $botonActivo }}'"
                    :disabled="seleccionado === null"
                    @click="verCurso()">
                Ver curso
            </button>
        </div>

        <p class="mt-4 text-sm text-body" x-show="seleccionado === null">
            Selecciona un curso para verlo.
        </p>

        {{-- Barra blanca: buscador + píldoras de filtro. --}}
        <div class="mt-4 bg-neutral-primary-soft border border-default rounded-base shadow-xs p-3 flex flex-col gap-3 sm:flex-row sm:items-center">
            <div class="flex-1 min-w-0">
                <x-ui.search-input
                    name="busqueda-curso"
                    placeholder="Buscar curso por nombre"
                    :show-button="false"
                    pill
                    x-model="busqueda"
                />
            </div>

            <div class="flex gap-2 overflow-x-auto sm:shrink-0">
                <button type="button" class="{{ $pillBase }}" :class="filtro === 'todos' ? '{{ $pillActiva }}' : '{{ $pillInactiva }}'" @click="filtro = 'todos'">
                    Todos {{ $cursos->count() }}
                </button>
                <button type="button" class="{{ $pillBase }}" :class="filtro === 'activos' ? '{{ $pillActiva }}' : '{{ $pillInactiva }}'" @click="filtro = 'activos'">
                    Activos {{ $totalActivos }}
                </button>
            </div>
        </div>

        {{-- Lista de cursos: una card de ancho completo por curso. --}}
        <div class="mt-4 space-y-3">
            @forelse ($cursos as $fila)
                @php
                    $curso = $fila['curso'];
                    $esActivo = $curso->estado === 'EnCurso';
                    $examenActivo = $fila['examen_activo'];

                    $metricas = [
                        ['valor' => $fila['inscritos'], 'etiqueta' => 'inscritos', 'color' => 'text-heading'],
                        ['valor' => $fila['habilitados'], 'etiqueta' => 'habilitados', 'color' => 'text-fg-brand'],
                        ['valor' => $fila['deshabilitados'], 'etiqueta' => 'deshabilit.', 'color' => 'text-fg-danger-strong'],
                        ['valor' => $fila['incidencias'], 'etiqueta' => 'incidencias', 'color' => 'text-fg-warning'],
                    ];
                @endphp

                {{-- La card entera es la opción: al tocarla queda seleccionada con
                     el anillo azul, el mismo que usan las tarjetas de exámenes. --}}
                <div
                    x-show="coincide(@js($curso->nombre_curso), @js($esActivo))"
                    data-seleccionable
                    role="button" tabindex="0"
                    :aria-pressed="seleccionado === {{ $curso->id_curso }} ? 'true' : 'false'"
                    :class="seleccionado === {{ $curso->id_curso }} ? 'border-brand! ring-2 ring-brand' : ''"
                    @click="seleccionado = {{ $curso->id_curso }}"
                    @keydown.enter.prevent="seleccionado = {{ $curso->id_curso }}"
                    @keydown.space.prevent="seleccionado = {{ $curso->id_curso }}"
                    @class([
                        'cursor-pointer rounded-base bg-neutral-primary-soft shadow-xs p-4 sm:p-5 flex flex-col gap-4 sm:flex-row sm:items-center border',
                        'border-brand border-2' => $examenActivo,
                        'border-default' => ! $examenActivo,
                    ])
                >
                    {{-- Ícono + nombre + badge de estado. --}}
                    <div class="flex items-center gap-3 sm:w-64 sm:shrink-0 min-w-0">
                        <span class="shrink-0 flex items-center justify-center w-10 h-10 rounded-base bg-brand-softer text-fg-brand">
                            <svg class="w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25"/></svg>
                        </span>
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="font-semibold text-heading truncate">{{ $curso->nombre_curso }}</p>
                                <x-ui.badge :type="$esActivo ? 'status-habilitado' : 'gray'">
                                    {{ $esActivo ? 'Activo' : 'Finalizado' }}
                                </x-ui.badge>
                            </div>
                            @if ($fila['gestion'])
                                <p class="text-xs text-body">{{ $fila['gestion'] }}</p>
                            @endif
                        </div>
                    </div>

                    {{-- Métricas: fila de 4 columnas compactas en mobile, en línea en desktop. --}}
                    <div class="grid grid-cols-4 gap-2 sm:flex sm:flex-1 sm:justify-center sm:gap-8">
                        @foreach ($metricas as $metrica)
                            <div class="text-center">
                                <p class="text-xl sm:text-2xl font-semibold {{ $metrica['valor'] === 0 ? 'text-fg-disabled' : $metrica['color'] }}">{{ $metrica['valor'] }}</p>
                                <p class="text-[11px] sm:text-xs text-body">{{ $metrica['etiqueta'] }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            @empty
                <p class="text-sm text-body text-center py-8">No existen cursos registrados.</p>
            @endforelse

            @if ($cursos->isNotEmpty())
                <p x-show="!hayVisibles()" class="text-sm text-body text-center py-8">
                    No se encontraron cursos con ese criterio.
                </p>
            @endif
        </div>
    </div>
@endsection
