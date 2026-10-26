{{--
    @file    examenes-curso.blade.php
    @author  Alex Candia <alex.leonar.candia@gmail.com>
    @created 2026-10-05
    @updated 2026-10-10
    @updated 2026-10-24
    @updated 2026-10-25

    @description
    Listado de exámenes de un curso (página examenes-curso): lista los exámenes
    del curso en tarjetas horizontales con el ícono del archivo, el tipo, el
    estado (Programado o Finalizado), la fecha, el horario, los ambientes
    asignados y la cantidad de estudiantes que registraron su ingreso.

    Arriba van los botones "Invitar docente" y "Dividir examen". Actúan sobre
    un examen concreto: se activan recién cuando se toca una tarjeta para
    seleccionarla. "Invitar docente" todavía no tiene funcionalidad. "Dividir
    examen" reemplaza el listado por el título del examen elegido y el criterio
    de división (por carrera o por orden alfabetico) con los botones "Dividir"
    (todavía sin acción) . Si el examen tiene una sola aula, en
    lugar de los controles se muestra un texto simple avisando que no se puede
    dividir.

    El listado no llega con la página: el parcial declara un x-data de Alpine
    que consulta GET /api/cursos/{idCurso}/examenes (CursoExamenController)
    cuando se inicializa y dibuja los tres estados: cargando (placeholders),
    error (con botón de reintento) y listo (con los ítems o el vacío).

    Los estados "Programado"/"Finalizado" vienen tal cual del endpoint, que
    colapsa el "en curso" interno del servicio en "Programado". La fecha se
    muestra como dd/mm/yyyy y el horario con guion ("08:00 - 10:00"), según el
    diseño aprobado de la sección; el botón "Ver Detalles" de ese diseño queda
    fuera hasta que exista la pantalla de detalle del examen.

    @changelog
    - 2026-10-05  [Alex Candia]  feat: creación inicial de la sección.
    - 2026-10-05  [Alex Candia]  feat: la sección pasa el catálogo de ambientes al
      modal de alta, que lo carga en su buscador.
    - 2026-10-05  [Alex Candia]  feat: la sección pasa también el catálogo de
      materiales y el de normas, que el modal muestra como casillas.
    - 2026-10-09  [OchoaCesar]  feat: el listado pasa a consumir con fetch el endpoint
      /api/cursos/{idCurso}/examenes al abrir la pestaña; el ítem muestra
      ambientes e ingresados (antes duración e inscritos), con el estado
      debajo del tipo y en dos etiquetas: Programado y Finalizado.
    - 2026-10-10  [Alex Candia]  refactor: el botón "Crear examen" queda solo con
      el texto (sin el ícono "+") y el aviso de éxito pasa a ser un modal chico.
    - 2026-10-10  [OchoaCesar]  refactor: las tarjetas pasan a diseño horizontal con
      ícono, badge invertido (Programado gris / Finalizado brand con ícono) y grid
      de datos con labels en mayúsculas y valores mono; la fecha pasa a dd/mm/yyyy y
      el guion del horario a " - ".
    - 2026-10-24  [Valery D. Ortuno P]  refactor: el parcial pasa de
      materia-examenes.blade.php a examenes-curso.blade.php y lo incrusta la
      página de exámenes del curso. El botón "Crear examen" y el modal de alta
      salen (el alta es una página propia desde la sección Exámenes); se suman
      "Invitar docente" y "Dividir examen", que se activan al seleccionar una
      tarjeta; un clic fuera de las tarjetas suelta la selección. Los textos
      visibles van sin acentos.
    - 2026-10-25  [Alex Candia]  feat: "Dividir examen" reemplaza el listado por
      el título del examen, el criterio de división y los botones "Dividir" y
      . Con una sola aula muestra solo un texto simple. El botón
      "Dividir" todavía no ejecuta la división.

    @see  App\Http\Controllers\Api\CursoExamenController
    @see  resources/views/pages/examenes-curso.blade.php
--}}

@php
    // Clases de x-ui.button: se repiten acá porque el estado deshabilitado
    // depende de la selección de Alpine y el componente lo resuelve en Blade.
    $botonBase = 'inline-flex items-center justify-center box-border border focus:ring-4 shadow-xs font-medium leading-5 focus:outline-none rounded-base px-6 py-2.5 text-sm font-semibold shrink-0 whitespace-nowrap';
    $botonPorExamen = 'text-body bg-neutral-primary-soft border-default hover:bg-neutral-secondary-medium hover:text-heading focus:ring-neutral-tertiary-soft cursor-pointer';
    $botonInactivo = 'text-fg-disabled bg-disabled border-default-medium cursor-not-allowed';
    $botonDivisionActivo = 'text-white bg-brand border-transparent hover:bg-brand-strong focus:ring-brand-medium cursor-pointer';

    $opcionesDivision = [
        'carrera' => 'Por carrera',
        'apellido' => 'Por orden alfabetico',
    ];
@endphp

<div class="flex flex-col gap-4"
     x-data="{
         examenes: [],
         estado: 'cargando',
         // Examen elegido para Invitar docente / Dividir examen.
         seleccionado: null,
         panelDivision: false,
         criterioDivision: '',
         get examenSeleccionado() {
             return this.examenes.find((examen) => examen.id === this.seleccionado) || null;
         },
         get aulasDelExamen() {
             return this.examenSeleccionado?.ambientes?.length ?? 0;
         },
         get puedeDividir() {
             return this.aulasDelExamen > 1;
         },
         async cargar() {
             this.estado = 'cargando';
             this.limpiarSeleccion();
             try {
                 const respuesta = await fetch('{{ route('api.cursos.examenes.listar', ['idCurso' => $curso->id_curso]) }}');
                 if (!respuesta.ok) {
                     throw new Error('HTTP ' + respuesta.status);
                 }
                 const cuerpo = await respuesta.json();
                 this.examenes = cuerpo.datos;
                 this.estado = 'listo';
             } catch (error) {
                 this.estado = 'error';
             }
         },
         init() {
             this.cargar();
         },
         seleccionar(id) {
             this.seleccionado = id;
             this.criterioDivision = '';
         },
         alternarDivision() {
             if (this.seleccionado === null) {
                 return;
             }
             this.panelDivision = ! this.panelDivision;
             this.criterioDivision = '';
         },
         salirDivision() {
             this.panelDivision = false;
             this.criterioDivision = '';
         },
         limpiarSeleccion() {
             this.seleccionado = null;
             this.panelDivision = false;
             this.criterioDivision = '';
         },
         fechaDe(examen) {
             if (!examen.fecha) {
                 return '—';
             }
             const [anio, mes, dia] = examen.fecha.split('-');
             return dia + '/' + mes + '/' + anio;
         },
         horario(examen) {
             return [examen.hora_inicio, examen.hora_fin].filter(Boolean).join(' - ') || '—';
         },
         ambientesDe(examen) {
             return (examen.ambientes || []).map((ambiente) => ambiente.nombre).join(', ') || '—';
         },
         // Un clic que no cae en una tarjeta ni en los botones que la usan
         // suelta la selección. Los clics fuera del componente los cubre
         // @click.outside.
         soltarSeleccion(evento) {
             if (! evento.target.closest('[data-seleccionable], [data-accion-seleccion]')) {
                 this.limpiarSeleccion();
             }
         },
     }"
     @click="soltarSeleccion($event)"
     @click.outside="limpiarSeleccion()">
    {{-- Aviso de éxito del alta: el formulario de crear examen vuelve acá. --}}
    @if (session('mensaje'))
        <x-ui.modal-aviso :mensaje="session('mensaje')" />
    @endif

    <header class="flex flex-col gap-3">
        <h2 class="px-3 text-2xl font-bold text-heading">Examenes</h2>

        <div class="flex gap-2 overflow-x-auto" role="group" aria-label="Acciones de examenes">
            <button type="button" class="{{ $botonBase }}" data-accion-seleccion
                    :class="seleccionado === null ? '{{ $botonInactivo }}' : '{{ $botonPorExamen }}'"
                    :disabled="seleccionado === null">
                Invitar docente
            </button>
            <button type="button" class="{{ $botonBase }}" data-accion-seleccion
                    :class="seleccionado === null
                        ? '{{ $botonInactivo }}'
                        : (panelDivision ? '{{ $botonDivisionActivo }}' : '{{ $botonPorExamen }}')"
                    :disabled="seleccionado === null"
                    :aria-expanded="panelDivision ? 'true' : 'false'"
                    @click="alternarDivision()">
                Dividir examen
            </button>
        </div>

        <p class="px-3 text-sm text-body" x-show="estado === 'listo' && examenes.length > 0 && seleccionado === null">
            Selecciona un examen para invitar a un docente o dividirlo.
        </p>
    </header>

    {{-- Vista de división: reemplaza el listado sin salir de la página. Con una
         sola aula no hay controles: solo el aviso en texto simple. --}}
    <section x-show="panelDivision" x-cloak data-accion-seleccion class="flex flex-col gap-4">
        <template x-if="! puedeDividir">
            <p class="text-sm text-body">
                No se puede dividir el examen, solo se cuenta con un aula
            </p>
        </template>

        <template x-if="puedeDividir">
            <div class="flex flex-col gap-4">
                <h2 class="text-2xl font-bold text-heading" x-text="examenSeleccionado?.tipo || 'Sin tipo'"></h2>

                <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
                    <div class="min-w-0 flex-1 sm:max-w-xs">
                        <x-ui.select
                            label="Dividir por"
                            name="division_por"
                            x-model="criterioDivision"
                            :options="$opcionesDivision"
                            placeholder="Selecciona un criterio"
                        />
                    </div>

                    <div class="flex gap-2">
                        <x-ui.button
                            variant="default"
                            x-bind:disabled="criterioDivision === ''"
                            x-bind:class="criterioDivision === '' ? 'opacity-50 cursor-not-allowed' : ''"
                        >
                            Dividir
                        </x-ui.button>
                    </div>
                </div>
            </div>
        </template>
    </section>

    {{-- Cargando: placeholders con el mismo contorno que las tarjetas. --}}
    <div class="flex flex-col gap-4" x-show="estado === 'cargando' && ! panelDivision">
        @foreach ([1, 2] as $placeholder)
            <div class="animate-pulse border border-default bg-neutral-primary-soft rounded-base shadow-xs px-5 py-6 sm:px-8 flex flex-col gap-4 lg:flex-row lg:items-center lg:gap-6" aria-hidden="true">
                <div class="flex items-center gap-4 lg:w-72">
                    <div class="h-12 w-12 shrink-0 rounded-lg bg-neutral-secondary-medium"></div>
                    <div class="flex flex-col gap-2">
                        <div class="h-5 w-40 rounded bg-neutral-secondary-medium"></div>
                        <div class="h-4 w-24 rounded-full bg-neutral-secondary-medium"></div>
                    </div>
                </div>
                <div class="grid flex-1 grid-cols-2 gap-4 sm:grid-cols-4">
                    <div class="h-9 w-24 rounded bg-neutral-secondary-medium mx-auto"></div>
                    <div class="h-9 w-24 rounded bg-neutral-secondary-medium mx-auto"></div>
                    <div class="h-9 w-24 rounded bg-neutral-secondary-medium mx-auto"></div>
                    <div class="h-9 w-24 rounded bg-neutral-secondary-medium mx-auto"></div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Error de la consulta, con reintento. --}}
    <div x-show="estado === 'error' && ! panelDivision" x-cloak>
        <x-ui.alert type="danger" title="No se pudieron cargar los examenes">
            Ocurrio un problema al consultar el listado.
            <div class="mt-2">
                <x-ui.button variant="secondary" @click="cargar()">Reintentar</x-ui.button>
            </div>
        </x-ui.alert>
    </div>

    {{-- Listado: las tarjetas salen del JSON del endpoint, no de Blade. --}}
    <ul class="flex flex-col gap-4" x-show="estado === 'listo' && ! panelDivision" x-cloak>
        <template x-for="examen in examenes" :key="examen.id">
            <li class="flex cursor-pointer flex-col gap-4 border bg-neutral-primary-soft rounded-base shadow-xs px-5 py-6 sm:px-8 lg:flex-row lg:items-center lg:gap-6"
                role="button" tabindex="0" data-seleccionable
                :aria-pressed="seleccionado === examen.id ? 'true' : 'false'"
                :class="seleccionado === examen.id ? 'border-brand ring-2 ring-brand' : 'border-default hover:border-brand-subtle'"
                @click="seleccionar(examen.id)"
                @keydown.enter.prevent="seleccionar(examen.id)"
                @keydown.space.prevent="seleccionar(examen.id)">
                {{-- Ícono, tipo y estado. --}}
                <div class="flex items-center gap-4 lg:w-72">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-lg bg-brand-softer text-fg-brand-strong">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-6 w-6" aria-hidden="true"><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/><path d="M10 9H8"/><path d="M16 13H8"/><path d="M16 17H8"/></svg>
                    </div>
                    <div class="flex min-w-0 flex-col items-start">
                        <h3 class="text-xl font-bold text-heading" x-text="examen.tipo || 'Sin tipo'"></h3>

                        <x-ui.badge type="gray" class="mt-1" x-show="examen.estado === 'Programado'">
                            <span x-text="examen.estado"></span>
                        </x-ui.badge>
                        <x-ui.badge type="brand" class="mt-1 gap-1" x-show="examen.estado === 'Finalizado'">
                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-3 w-3 shrink-0" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
                            <span x-text="examen.estado"></span>
                        </x-ui.badge>
                    </div>
                </div>

                {{-- Datos de la tarjeta. --}}
                <div class="grid flex-1 grid-cols-2 gap-4 sm:grid-cols-4">
                    <div class="flex flex-col items-center gap-1.5 text-center">
                        <span class="text-xs font-semibold uppercase text-muted">Fecha:</span>
                        <time class="text-sm font-mono text-body" :datetime="examen.fecha" x-text="fechaDe(examen)"></time>
                    </div>

                    <div class="flex flex-col items-center gap-1.5 text-center">
                        <span class="text-xs font-semibold uppercase text-muted">Horario:</span>
                        <span class="text-sm font-mono text-body" x-text="horario(examen)"></span>
                    </div>

                    <div class="flex flex-col items-center gap-1.5 text-center">
                        <span class="text-xs font-semibold uppercase text-muted">Ambientes:</span>
                        <span class="text-sm font-mono text-body" x-text="ambientesDe(examen)"></span>
                    </div>

                    <div class="flex flex-col items-center gap-1.5 text-center">
                        <span class="text-xs font-semibold uppercase text-muted">Ingresos:</span>
                        <span class="text-sm font-bold text-heading" x-text="examen.ingresados"></span>
                    </div>
                </div>
            </li>
        </template>

        <li x-show="examenes.length === 0"
            class="flex flex-col items-center gap-2 p-8 text-center bg-neutral-primary-soft border border-default rounded-base shadow-xs">
            <svg class="w-6 h-6 text-fg-disabled" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-width="2" d="M4 10h16m-8-3V4M7 7V4m10 3V4M5 20h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2Zm3-7h.01v.01H8V13Zm4 0h.01v.01H12V13Zm4 0h.01v.01H16V13Zm-8 4h.01v.01H8V17Zm4 0h.01v.01H12V17Zm4 0h.01v.01H16V17Z"/></svg>
            <p class="text-sm text-body">Este curso aun no tiene examenes registrados.</p>
        </li>
    </ul>
</div>
