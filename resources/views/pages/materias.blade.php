{{--
    @file    materias.blade.php
    @author  David E. Chavez T. <virzuzz12345@gmail.com>
    @created 2026-09-29
    @updated 2026-10-12

    @description
    Vista de la lista de materias, rediseñada según el Figma de referencia:
    buscador + píldoras de filtro (Todas / Con examen activo / Sin
    estudiantes) y una card de ancho completo por materia con sus métricas
    (inscritos, habilitados, deshabilitados, incidencias) y el botón "Abrir
    materia" hacia `materias.detalle`.

    Los datos vienen de ListarMateriasConMetricasService (ver routes/web.php),
    que resuelve todo en un puñado de consultas agrupadas, nunca una por
    curso. "Examen activo" es un dato derivado (hora actual dentro de la
    ventana del examen más reciente del curso), no una columna de la base.

    El Figma muestra "SIS-3340 · Grupo 2" bajo el nombre: la tabla `curso` NO
    tiene columnas de sigla ni de grupo (ver ListarMateriasConMetricasService),
    así que esa línea no se puede mostrar y se omite. Tampoco se agrega el
    botón "Registrar nueva materia" del Figma, a pedido explícito.

    La búsqueda y los filtros son solo de Alpine (mostrar/ocultar las cards
    ya traídas): la lista de materias es chica y ya viaja completa en la
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

    @see  App\Services\Curso\ListarMateriasConMetricasService
    @see  resources/views/pages/materia-estudiantes.blade.php
    @see  routes/web.php
--}}

@extends('layouts.app')

@section('title', 'Materias')

@section('content')
    <div
        x-data="{
            busqueda: '',
            filtro: 'todas',
            materias: @js($materias->map(fn ($fila) => [
                'nombre' => $fila['curso']->nombre_curso,
                'activo' => $fila['examen_activo'],
                'sinEstudiantes' => $fila['inscritos'] === 0,
            ])->values()),
            coincide(nombre, activo, sinEstudiantes) {
                const termino = this.busqueda.trim().toLowerCase();

                if (termino !== '' && !nombre.toLowerCase().includes(termino)) {
                    return false;
                }

                if (this.filtro === 'activo' && !activo) {
                    return false;
                }

                if (this.filtro === 'sin_estudiantes' && !sinEstudiantes) {
                    return false;
                }

                return true;
            },
            hayVisibles() {
                return this.materias.some((m) => this.coincide(m.nombre, m.activo, m.sinEstudiantes));
            }
        }"
        class="px-4 py-4 sm:px-6 sm:py-6 lg:px-8"
    >
        @php
            $totalActivas = $materias->where('examen_activo', true)->count();
            $totalSinEstudiantes = $materias->where('inscritos', 0)->count();

            $pillBase = 'shrink-0 whitespace-nowrap inline-flex items-center justify-center box-border border focus:ring-4 shadow-xs font-medium leading-5 rounded-full focus:outline-none px-4 py-2.5 text-sm';
            $pillActiva = 'text-white bg-brand border-transparent hover:bg-brand-strong focus:ring-brand-medium';
            $pillInactiva = 'text-body bg-neutral-primary-soft border-default hover:bg-neutral-secondary-medium hover:text-heading focus:ring-neutral-tertiary-soft';
        @endphp

        {{-- Barra blanca: buscador + píldoras de filtro. --}}
        <div class="bg-neutral-primary-soft border border-default rounded-base shadow-xs p-3 flex flex-col gap-3 sm:flex-row sm:items-center">
            <div class="flex-1 min-w-0">
                <x-ui.search-input
                    name="busqueda-materia"
                    placeholder="Buscar materia por nombre"
                    :show-button="false"
                    pill
                    x-model="busqueda"
                />
            </div>

            <div class="flex gap-2 overflow-x-auto sm:shrink-0">
                <button type="button" class="{{ $pillBase }}" :class="filtro === 'todas' ? '{{ $pillActiva }}' : '{{ $pillInactiva }}'" @click="filtro = 'todas'">
                    Todas {{ $materias->count() }}
                </button>
                <button type="button" class="{{ $pillBase }}" :class="filtro === 'activo' ? '{{ $pillActiva }}' : '{{ $pillInactiva }}'" @click="filtro = 'activo'">
                    Con examen activo {{ $totalActivas }}
                </button>
                <button type="button" class="{{ $pillBase }}" :class="filtro === 'sin_estudiantes' ? '{{ $pillActiva }}' : '{{ $pillInactiva }}'" @click="filtro = 'sin_estudiantes'">
                    Sin estudiantes {{ $totalSinEstudiantes }}
                </button>
            </div>
        </div>

        {{-- Lista de materias: una card de ancho completo por materia. --}}
        <div class="mt-4 space-y-3">
            @forelse ($materias as $fila)
                @php
                    $curso = $fila['curso'];
                    $sinEstudiantes = $fila['inscritos'] === 0;
                    $activo = $fila['examen_activo'];

                    $metricas = [
                        ['valor' => $fila['inscritos'], 'etiqueta' => 'inscritos', 'color' => 'text-heading'],
                        ['valor' => $fila['habilitados'], 'etiqueta' => 'habilitados', 'color' => 'text-fg-brand'],
                        ['valor' => $fila['deshabilitados'], 'etiqueta' => 'deshabilit.', 'color' => 'text-fg-danger-strong'],
                        ['valor' => $fila['incidencias'], 'etiqueta' => 'incidencias', 'color' => 'text-fg-warning'],
                    ];
                @endphp

                <div
                    x-show="coincide(@js($curso->nombre_curso), @js($activo), @js($sinEstudiantes))"
                    @class([
                        'rounded-base shadow-xs p-4 sm:p-5 flex flex-col gap-4 sm:flex-row sm:items-center border',
                        'border-brand border-2' => $activo,
                        'border-default' => ! $activo,
                        'bg-warning-soft' => $sinEstudiantes,
                        'bg-neutral-primary-soft' => ! $sinEstudiantes,
                    ])
                >
                    {{-- Ícono + nombre + sigla (omitida, no existe en la base) + badge. --}}
                    <div class="flex items-center gap-3 sm:w-64 sm:shrink-0 min-w-0">
                        <span class="shrink-0 flex items-center justify-center w-10 h-10 rounded-base bg-brand-softer text-fg-brand">
                            <svg class="w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25"/></svg>
                        </span>
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="font-semibold text-heading truncate">{{ $curso->nombre_curso }}</p>
                                <x-ui.badge :type="$curso->estado === 'EnCurso' ? 'status-habilitado' : 'gray'">
                                    {{ $curso->estado === 'EnCurso' ? 'Activo' : 'Finalizado' }}
                                </x-ui.badge>
                            </div>
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

                    <x-ui.button :href="route('materias.detalle', $curso->id_curso)" variant="default" class="w-full sm:w-auto shrink-0">
                        Abrir materia
                    </x-ui.button>
                </div>
            @empty
                <p class="text-sm text-body text-center py-8">No hay materias registradas.</p>
            @endforelse

            @if ($materias->isNotEmpty())
                <p x-show="!hayVisibles()" class="text-sm text-body text-center py-8">
                    No se encontraron materias con ese criterio.
                </p>
            @endif
        </div>
    </div>
@endsection
