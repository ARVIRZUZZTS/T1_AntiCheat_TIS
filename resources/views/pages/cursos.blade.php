{{--
    @file    cursos.blade.php
    @author  David E. Chavez T. <virzuzz12345@gmail.com>
    @created 2026-09-29
    @updated 2026-10-12
    @updated 2026-10-24
    @updated 2026-11-09
    @updated 2026-11-19

    @description
    Lista de los cursos del docente autenticado: buscador + filtros (Todos /
    Activos / Finalizados) y una card de ancho completo por curso con su
    gestion, el proximo examen (fecha y horario), los estudiantes inscritos y
    las incidencias. Es solo de consulta: las cards no se seleccionan. "Crear
    curso" y "Ver estudiantes" son subopciones del sidebar.

    Solo se listan los cursos propios del docente: las invitaciones son por
    examen y los cursos compartidos se manejan en la sección Exámenes.

    Los cursos llegan ordenados desde ListarCursosDocenteService: primero los
    activos y abajo los finalizados (cursos pasados), cada grupo del más
    reciente al más antiguo.

    El grupo ya va dentro del nombre ("Calculo I - Grupo 2", ver Crear curso)
    y bajo el nombre se muestra la gestión con su año ("Segundo semestre
    2026"), que llega de ListarCursosDocenteService.

    La búsqueda y los filtros son de Alpine (mostrar/ocultar las cards
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
    - 2026-11-09  [Diegootm]  fix: se quita el filtro "Invitados" y los cursos
      compartidos dejan de listarse (la invitación es por examen, no por curso).
      Se agrega el filtro "Finalizados" que exigía el criterio de aceptación.
    - 2026-11-09  [Diegootm]  fix: se quita la insignia "Anfitrion" de la lista
      de Cursos (sigue en Exámenes, que es donde importa la invitación) y se da
      más separación entre el nombre y la insignia de estado.
    - 2026-11-09  [Diegootm]  style: en desktop la card pone el encabezado a la
      izquierda y las métricas a su lado ocupando todo el ancho (rejilla de 4
      con separadores, el último número pegado al borde derecho), en vez de
      apilar todo o dejar espacio vacío a la derecha; en mobile sigue apilado.
    - 2026-11-19  [Valery D. Ortuno P]  style: la card muestra siempre todos sus
      datos (antes el proximo examen y las metricas solo aparecian al
      seleccionarla y el resto del ancho quedaba vacio): en desktop se reparte
      en tres bloques (curso | proximo examen con fecha y horario | metricas)
      que ocupan todo el ancho; en mobile se apilan. La vista es solo de
      consulta: las cards ya no se seleccionan y se quitan el icono y las
      metricas de habilitados / deshabilitados. "Inscritos" pasa a ser los
      estudiantes del curso (se ve aunque no haya examen proximo) e
      "Incidencias" cuenta solo las confirmadas de la central de riesgos.
      Textos sin acentos.

    @see  App\Http\Controllers\CursoController
    @see  App\Services\Curso\ListarCursosDocenteService
    @see  resources/views/pages/curso-detalle.blade.php
    @see  resources/views/pages/curso-crear.blade.php
--}}

@extends('layouts.app')

@section('title', 'Cursos')

@section('content')
    <div
        x-data="{
            busqueda: '',
            filtro: 'todos',
            coincide(nombre, activo) {
                const termino = this.busqueda.trim().toLowerCase();

                if (termino !== '' && !nombre.toLowerCase().includes(termino)) {
                    return false;
                }

                if (this.filtro === 'activos' && !activo) {
                    return false;
                }

                if (this.filtro === 'finalizados' && activo) {
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
        class="px-4 py-4 sm:px-6 sm:py-6 lg:px-8"
    >
        @php
            $totalActivos = $cursos->filter(fn ($fila) => $fila['curso']->estado === 'EnCurso')->count();
            $totalFinalizados = $cursos->count() - $totalActivos;

            $pillBase = 'shrink-0 whitespace-nowrap inline-flex items-center justify-center box-border border focus:ring-4 shadow-xs font-medium leading-5 rounded-full focus:outline-none px-4 py-2.5 text-sm';
            $pillActiva = 'text-white bg-brand border-transparent hover:bg-brand-strong focus:ring-brand-medium';
            $pillInactiva = 'text-body bg-neutral-primary-soft border-default hover:bg-neutral-secondary-medium hover:text-heading focus:ring-neutral-tertiary-soft';
        @endphp

        {{-- Aviso de éxito del alta: "Crear curso" vuelve acá al guardar. --}}
        @if (session('mensaje'))
            <x-ui.modal-aviso :mensaje="session('mensaje')" />
        @endif

        {{-- Ni "Crear curso" ni "Ver estudiantes" viven aca: son subopciones
             del sidebar. La lista es solo de consulta: busca y filtra. --}}

        {{-- Barra blanca: buscador + píldoras de filtro. --}}
        <div class="mt-4 bg-neutral-primary-soft border border-default rounded-base shadow-xs p-3 flex flex-col gap-3 sm:flex-row sm:items-center">
            <div class="flex-1 min-w-0">
                <x-ui.search-input
                    name="busqueda-curso"
                    placeholder="Buscar curso por nombre"
                    :show-button="false"
                    pill
                    x-model="busqueda"
                    @keydown.enter.prevent
                />
            </div>

            <div class="flex gap-2 overflow-x-auto sm:shrink-0">
                <button type="button" class="{{ $pillBase }}" :aria-pressed="filtro === 'todos'" :class="filtro === 'todos' ? '{{ $pillActiva }}' : '{{ $pillInactiva }}'" @click="filtro = 'todos'">
                    Todos {{ $cursos->count() }}
                </button>
                <button type="button" class="{{ $pillBase }}" :aria-pressed="filtro === 'activos'" :class="filtro === 'activos' ? '{{ $pillActiva }}' : '{{ $pillInactiva }}'" @click="filtro = 'activos'">
                    Activos {{ $totalActivos }}
                </button>
                <button type="button" class="{{ $pillBase }}" :aria-pressed="filtro === 'finalizados'" :class="filtro === 'finalizados' ? '{{ $pillActiva }}' : '{{ $pillInactiva }}'" @click="filtro = 'finalizados'">
                    Finalizados {{ $totalFinalizados }}
                </button>
            </div>
        </div>

        {{-- Lista de cursos: una card de ancho completo por curso. --}}
        <div class="mt-4 space-y-3">
            @forelse ($cursos as $fila)
                @php
                    $curso = $fila['curso'];
                    $esActivo = $curso->estado === 'EnCurso';
                    $examen = $fila['proximo_examen'];
                    $metricas = [
                        ['valor' => $fila['estudiantes'], 'etiqueta' => 'Inscritos', 'color' => 'text-heading'],
                        ['valor' => $fila['incidencias'], 'etiqueta' => 'Incidencias', 'color' => 'text-fg-warning'],
                    ];

                    $inicioExamen = $examen ? \Illuminate\Support\Carbon::parse($examen->fecha.' '.$examen->hora_inicio) : null;
                    $finExamen = $examen && $examen->duracion ? $inicioExamen->copy()->addMinutes((int) $examen->duracion) : null;
                @endphp

                {{-- Card de solo consulta (no se selecciona). En desktop reparte
                     el ancho en tres bloques separados por lineas: curso |
                     proximo examen | metricas. En mobile se apilan. --}}
                <article
                    x-show="coincide(@js($curso->nombre_curso), @js($esActivo))"
                    x-cloak
                    class="rounded-base bg-neutral-primary-soft shadow-xs border border-default p-4 sm:p-5 grid grid-cols-1 gap-4 lg:grid-cols-[minmax(0,1.6fr)_minmax(0,1fr)_minmax(0,1fr)] lg:items-center lg:gap-0 lg:divide-x lg:divide-default"
                >
                    {{-- Curso: nombre + estado y la gestion debajo. --}}
                    <div class="min-w-0 lg:pr-6">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-x-3 gap-y-1.5">
                                <p class="font-semibold text-heading wrap-break-word">{{ $curso->nombre_curso }}</p>
                                <x-ui.badge :type="$esActivo ? 'status-habilitado' : 'gray'">
                                    {{ $esActivo ? 'Activo' : 'Finalizado' }}
                                </x-ui.badge>
                            </div>
                            <p class="mt-1 text-xs text-body">{{ $fila['gestion'] ?? 'Gestion no registrada' }}</p>
                        </div>
                    </div>

                    {{-- Proximo examen: fecha y horario, o el aviso si no hay. --}}
                    <div class="border-t border-default pt-4 lg:border-t-0 lg:pt-0 lg:px-6">
                        <p class="text-xs font-semibold uppercase text-muted">Proximo examen:</p>
                        @if ($examen)
                            <p class="mt-1 text-sm font-medium text-heading">{{ $inicioExamen->format('d/m/Y') }}</p>
                            <p class="text-xs text-body">{{ $inicioExamen->format('H:i') }}{{ $finExamen ? ' - '.$finExamen->format('H:i') : '' }}</p>
                        @else
                            <p class="mt-1 text-sm text-body">No hay examenes programados</p>
                        @endif
                    </div>

                    {{-- Metricas del curso: inscritos e incidencias. --}}
                    <div class="grid grid-cols-2 border-t border-default pt-4 divide-x divide-default lg:border-t-0 lg:pt-0 lg:pl-2">
                        @foreach ($metricas as $metrica)
                            <div class="text-center sm:px-3">
                                <p class="text-xl sm:text-2xl font-semibold {{ $metrica['valor'] === 0 ? 'text-fg-disabled' : $metrica['color'] }}">{{ $metrica['valor'] }}</p>
                                <p class="text-[11px] sm:text-xs text-body">{{ $metrica['etiqueta'] }}</p>
                            </div>
                        @endforeach
                    </div>
                </article>
            @empty
                <p class="text-sm text-body text-center py-8">No tienes cursos registrados</p>
            @endforelse

            @if ($cursos->isNotEmpty())
                <p x-show="!hayVisibles()" x-cloak class="text-sm text-body text-center py-8">
                    No se encontraron cursos
                </p>
            @endif
        </div>
    </div>
@endsection
