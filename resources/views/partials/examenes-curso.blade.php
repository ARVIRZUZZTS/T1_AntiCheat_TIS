{{--
    @file    examenes-curso.blade.php
    @author  Valery D. Ortuno P. <valerydariana98@gmail.com>
    @created 2026-10-05
    @updated 2026-10-10
    @updated 2026-10-24
    @updated 2026-10-25

    @description
    Exámenes de la materia elegida en la página única Exámenes. Arriba va el
    selector de materia; mientras no haya una seleccionada no se muestran las
    cuatro pestañas.

    El parcial muestra cuatro pestañas: "Ver Lista de Examenes", "Crear Examen",
    "Invitar Docente a Examen" y "Dividir Examen". Al cambiar de pestaña solo
    cambia el contenido de abajo; no se navega a otra página.

    La pestaña de lista es de solo lectura: las tarjetas se consultan con fetch
    al endpoint GET /api/cursos/{idCurso}/examenes (CursoExamenController) y no
    se seleccionan. Invitar y Dividir piden el examen en un selector. Crear
    Examen incrusta el mismo formulario de pages/examen-crear; si la validación
    falla, la página vuelve a abrir esa pestaña porque StoreExamenRequest
    redirige con errores y el formulario manda el curso elegido.

    La división usa los ambientes que ya devuelve el endpoint. Si el examen
    tiene una sola aula, en lugar de los controles se muestra un texto simple
    avisando que no se puede dividir.

    El listado no llega con la página: el parcial declara un x-data de Alpine
    que consulta el endpoint cuando se abre. Los estados de carga, error y
    lista vacía se mantienen.

    @changelog
    - 2026-10-05  [Alex Candia]  feat: creación inicial del parcial de exámenes
      de una materia.
    - 2026-10-09  [Diego Tejerina]  feat: lista de exámenes desde su API,
      tarjetas horizontales y estados de carga, error y vacío.
    - 2026-10-10  [OchoaCesar]  feat: se muestran tipo, estado, fecha, horario,
      ambientes e ingresados; sin "Ver Detalles" ni inscritos.
    - 2026-10-24  [Valery D. Ortuno P]  refactor: el parcial pasa de
      materia-examenes.blade.php a examenes-curso.blade.php y lo incrusta la
      página de exámenes del curso. El botón "Crear examen" y el modal de alta
      salen (el alta es una página propia desde la sección Exámenes); se suman
      "Invitar docente" y "Dividir examen", que se activan al seleccionar una
      tarjeta; un clic fuera de las tarjetas suelta la selección. Los textos
      visibles van sin acentos.
    - 2026-10-25  [Alex Candia]  feat: las acciones pasan a cuatro pestañas en
      la misma página: Ver Lista de Examenes (solo lectura), Crear Examen
      (formulario incrustado), Invitar Docente a Examen y Dividir Examen. El
      examen de invitación/división se elige en un selector. La división con una
      sola aula muestra solo un texto simple y el botón "Dividir" todavía no
      ejecuta la división.
    - 2026-10-25  [Alex Candia]  refactor: el parcial recibe la lista de materias
      y el curso inicial; agrega el selector arriba y oculta las cuatro pestañas
      mientras no haya materia. El fetch, el formulario y las pestañas se
      actualizan sin salir de la página.

    @see  App\Http\Controllers\Api\CursoExamenController
    @see  App\Http\Controllers\ExamenController
    @see  resources/views/pages/examenes-curso.blade.php
    @see  resources/views/components/ui/form-crear-examen.blade.php
--}}

@php
    $curso = $curso ?? null;
    $cursos = $cursos ?? ($curso !== null ? collect([$curso]) : collect());
    $cursoInicial = $cursoInicial ?? $curso;
    $puedeCrearExamen = $puedeCrearExamen ?? true;
    $ambientes = $ambientes ?? [];
    $materiales = $materiales ?? [];
    $normas = $normas ?? [];

    // Mismas clases de pestañas que usa el detalle del curso.
    $tabClases = 'shrink-0 whitespace-nowrap inline-flex items-center justify-center box-border border focus:ring-4 shadow-xs font-medium leading-5 rounded-base focus:outline-none px-4 py-2.5 text-sm';
    $tabActivo = 'text-white bg-brand border-transparent hover:bg-brand-strong focus:ring-brand-medium';
    $tabInactivo = 'text-body bg-neutral-secondary-medium border-default-medium hover:bg-neutral-tertiary-medium hover:text-heading focus:ring-neutral-tertiary';

    $opcionesDivision = [
        'carrera' => 'Por carrera',
        'apellido' => 'Por orden alfabetico',
    ];

    $opcionesCursos = $cursos->pluck('nombre_curso', 'id_curso')->all();

    // Si el alta falló, se vuelve a abrir la pestaña del formulario.
    $tabInicial = $tabInicial ?? (($errors->any() && $cursoInicial !== null && $puedeCrearExamen) ? 'crear' : 'lista');
@endphp

<div class="flex flex-col gap-4"
     x-data="{
         cursos: @js($opcionesCursos),
         cursoSeleccionado: @js((string) ($cursoInicial?->id_curso ?? '')),
         cursoNombre: @js($cursoInicial?->nombre_curso ?? ''),
         urlExamenes: @js(route('api.cursos.examenes.listar', ['idCurso' => '__CURSO__'])),
         examenes: [],
         estado: 'listo',
         peticion: 0,
         tab: @js($tabInicial),
         // Examen elegido en las pestañas Invitar y Dividir.
         seleccionado: '',
         criterioDivision: '',
         get hayCurso() {
             return this.cursoSeleccionado !== '';
         },
         get examenSeleccionado() {
             return this.examenes.find((examen) => String(examen.id) === String(this.seleccionado)) || null;
         },
         get aulasDelExamen() {
             return this.examenSeleccionado?.ambientes?.length ?? 0;
         },
         get puedeDividir() {
             return this.aulasDelExamen > 1;
         },
         cambiarCurso() {
             this.tab = 'lista';
             this.cursoNombre = this.cursos[this.cursoSeleccionado] ?? '';
             this.seleccionado = '';
             this.criterioDivision = '';
             this.actualizarUrl();
             this.avisarFormulario();
             this.cargar();
         },
         actualizarUrl() {
             const url = new URL(window.location.href);

             if (this.hayCurso) {
                 url.searchParams.set('curso', this.cursoSeleccionado);
             } else {
                 url.searchParams.delete('curso');
             }

             window.history.replaceState({}, '', url);
         },
         avisarFormulario() {
             window.dispatchEvent(new CustomEvent('curso-seleccionado', {
                 detail: { id: this.cursoSeleccionado, nombre: this.cursoNombre },
             }));
         },
         async cargar() {
             const peticion = ++this.peticion;

             this.seleccionado = '';
             this.criterioDivision = '';

             if (! this.hayCurso) {
                 this.examenes = [];
                 this.estado = 'listo';

                 return;
             }

             this.estado = 'cargando';
             this.limpiarSeleccion();
             try {
                 const url = this.urlExamenes.replace('__CURSO__', this.cursoSeleccionado);
                 const respuesta = await fetch(url);
                 if (!respuesta.ok) {
                     throw new Error('HTTP ' + respuesta.status);
                 }
                 const cuerpo = await respuesta.json();
                 if (peticion !== this.peticion) {
                     return;
                 }
                 this.examenes = cuerpo.datos ?? [];
                 this.estado = 'listo';
             } catch (error) {
                 if (peticion === this.peticion) {
                     this.estado = 'error';
                 }
             }
         },
         init() {
             this.cargar();
             this.$nextTick(() => this.avisarFormulario());
         },
         etiquetaExamen(examen) {
             return [examen.tipo || 'Sin tipo', this.fechaDe(examen), this.horario(examen)].join(' - ');
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
     }">
    {{-- Aviso de éxito del alta: el formulario de crear examen vuelve acá. --}}
    @if (session('mensaje'))
        <x-ui.modal-aviso :mensaje="session('mensaje')" />
    @endif

    <div class="max-w-md px-3">
        <x-ui.select
            label="Materia"
            name="curso"
            x-model="cursoSeleccionado"
            @change="cambiarCurso()"
            :options="$opcionesCursos"
            :selected="$cursoInicial?->id_curso"
            placeholder="Selecciona una materia"
        />
    </div>

    <p class="px-3 text-sm text-body" x-show="! hayCurso">
        Selecciona una materia para ver sus examenes.
    </p>

    {{-- Las cuatro pestañas y su contenido solo aparecen con materia elegida. --}}
    <div x-show="hayCurso" x-cloak class="flex flex-col gap-4">
    <header class="flex flex-col gap-3">
        <h2 class="px-3 text-2xl font-bold text-heading">Examenes</h2>

        <nav class="flex min-w-0 gap-2 overflow-x-auto px-3" aria-label="Opciones de examenes">
            <button type="button" class="{{ $tabClases }}"
                    :class="tab === 'lista' ? '{{ $tabActivo }}' : '{{ $tabInactivo }}'"
                    @click="tab = 'lista'">
                Ver Lista de Examenes
            </button>

            @if ($puedeCrearExamen)
                <button type="button" class="{{ $tabClases }}"
                        :class="tab === 'crear' ? '{{ $tabActivo }}' : '{{ $tabInactivo }}'"
                        @click="tab = 'crear'">
                    Crear Examen
                </button>
            @else
                <button type="button" class="{{ $tabClases }} {{ $tabInactivo }} opacity-50 cursor-not-allowed"
                        title="El curso ya finalizo" disabled>
                    Crear Examen
                </button>
            @endif

            <button type="button" class="{{ $tabClases }}"
                    :class="tab === 'invitar' ? '{{ $tabActivo }}' : '{{ $tabInactivo }}'"
                    @click="tab = 'invitar'">
                Invitar Docente a Examen
            </button>

            <button type="button" class="{{ $tabClases }}"
                    :class="tab === 'dividir' ? '{{ $tabActivo }}' : '{{ $tabInactivo }}'"
                    @click="tab = 'dividir'">
                Dividir Examen
            </button>
        </nav>
    </header>

    {{-- Ver Lista de Examenes: solo lectura, sin selección ni acciones. --}}
    <section x-show="tab === 'lista'" x-cloak class="flex flex-col gap-4">
        <div class="flex flex-col gap-4" x-show="estado === 'cargando'">
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

        <div x-show="estado === 'error'" x-cloak>
            <x-ui.alert type="danger" title="No se pudieron cargar los examenes">
                Ocurrio un problema al consultar el listado.
                <div class="mt-2">
                    <x-ui.button variant="secondary" @click="cargar()">Reintentar</x-ui.button>
                </div>
            </x-ui.alert>
        </div>

        <ul class="flex flex-col gap-4" x-show="estado === 'listo'" x-cloak>
            <template x-for="examen in examenes" :key="examen.id">
                <li class="flex flex-col gap-4 border border-default bg-neutral-primary-soft rounded-base shadow-xs px-5 py-6 sm:px-8 lg:flex-row lg:items-center lg:gap-6">
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
    </section>

    {{-- Crear Examen: el mismo formulario del alta, incrustado en la pestaña. --}}
    @if ($puedeCrearExamen)
        <section x-show="tab === 'crear'" x-cloak>
            <div class="mx-auto w-full max-w-2xl rounded-base border border-default bg-neutral-primary-soft p-4 shadow-sm md:p-6">
                <x-ui.form-crear-examen
                    :nombre-curso="$cursoInicial?->nombre_curso ?? ''"
                    :ambientes="$ambientes"
                    :materiales="$materiales"
                    :normas="$normas"
                    :curso-id="$cursoInicial?->id_curso"
                    :accion-url="route('cursos.examenes.store', ['curso' => '__CURSO__'])"
                    :cancelar-url="route('examenes')"
                />
            </div>
        </section>
    @endif

    {{-- Invitar Docente a Examen: se elige el examen; la invitación todavía no
         tiene flujo de guardado. --}}
    <section x-show="tab === 'invitar'" x-cloak class="flex flex-col gap-4">
        <p class="px-3 text-sm text-body" x-show="estado === 'cargando'">Cargando examenes...</p>

        <div x-show="estado === 'error'" x-cloak>
            <x-ui.alert type="danger" title="No se pudieron cargar los examenes">
                Ocurrio un problema al consultar el listado.
                <div class="mt-2">
                    <x-ui.button variant="secondary" @click="cargar()">Reintentar</x-ui.button>
                </div>
            </x-ui.alert>
        </div>

        <p class="px-3 text-sm text-body" x-show="estado === 'listo' && examenes.length === 0">
            Este curso aun no tiene examenes registrados.
        </p>

        <div x-show="estado === 'listo' && examenes.length > 0"
             class="flex flex-col gap-3 sm:flex-row sm:items-end">
            <div class="min-w-0 flex-1 sm:max-w-md">
                <label for="examen-invitar" class="block mb-2.5 text-sm font-medium text-heading">Examen</label>
                <select id="examen-invitar" x-model="seleccionado" @change="criterioDivision = ''"
                        class="block w-full px-3 py-2.5 border text-sm rounded-base shadow-xs bg-neutral-secondary-medium border-default-medium text-heading focus:ring-brand focus:border-brand">
                    <option value="" disabled>Selecciona un examen</option>
                    <template x-for="examen in examenes" :key="'invitar-' + examen.id">
                        <option :value="examen.id" x-text="etiquetaExamen(examen)"></option>
                    </template>
                </select>
            </div>

            <x-ui.button variant="default"
                         x-bind:disabled="seleccionado === ''"
                         x-bind:class="seleccionado === '' ? 'opacity-50 cursor-not-allowed' : ''">
                Invitar docente
            </x-ui.button>
        </div>
    </section>

    {{-- Dividir Examen: selector de examen y, si tiene dos o más aulas, el
         criterio de división. Con una sola aula solo se muestra el aviso. --}}
    <section x-show="tab === 'dividir'" x-cloak class="flex flex-col gap-4">
        <p class="px-3 text-sm text-body" x-show="estado === 'cargando'">Cargando examenes...</p>

        <div x-show="estado === 'error'" x-cloak>
            <x-ui.alert type="danger" title="No se pudieron cargar los examenes">
                Ocurrio un problema al consultar el listado.
                <div class="mt-2">
                    <x-ui.button variant="secondary" @click="cargar()">Reintentar</x-ui.button>
                </div>
            </x-ui.alert>
        </div>

        <p class="px-3 text-sm text-body" x-show="estado === 'listo' && examenes.length === 0">
            Este curso aun no tiene examenes registrados.
        </p>

        <div x-show="estado === 'listo' && examenes.length > 0" class="flex flex-col gap-4">
            <div class="min-w-0 max-w-md">
                <label for="examen-dividir" class="block mb-2.5 text-sm font-medium text-heading">Examen</label>
                <select id="examen-dividir" x-model="seleccionado" @change="criterioDivision = ''"
                        class="block w-full px-3 py-2.5 border text-sm rounded-base shadow-xs bg-neutral-secondary-medium border-default-medium text-heading focus:ring-brand focus:border-brand">
                    <option value="" disabled>Selecciona un examen</option>
                    <template x-for="examen in examenes" :key="'dividir-' + examen.id">
                        <option :value="examen.id" x-text="etiquetaExamen(examen)"></option>
                    </template>
                </select>
            </div>

            <template x-if="examenSeleccionado && ! puedeDividir">
                <p class="px-3 text-sm text-body">
                    No se puede dividir el examen, solo se cuenta con un aula
                </p>
            </template>

            <template x-if="puedeDividir">
                <div class="flex flex-col gap-4">
                    <h3 class="px-3 text-xl font-bold text-heading" x-text="examenSeleccionado?.tipo || 'Sin tipo'"></h3>

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

                        <x-ui.button
                            variant="default"
                            x-bind:disabled="criterioDivision === ''"
                            x-bind:class="criterioDivision === '' ? 'opacity-50 cursor-not-allowed' : ''"
                        >
                            Dividir
                        </x-ui.button>
                    </div>
                </div>
            </template>
        </div>
    </section>
    </div>
</div>
