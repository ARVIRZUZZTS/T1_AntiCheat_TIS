{{--
    @file    asignar-auxiliar.blade.php
    @author  Valery D. Ortuno P <valerydariana98@gmail.com>
    @created 2026-11-22
    @updated 2026-11-24
    @updated 2026-11-28

    @description
    Vista de "Asignar auxiliar" (HU 24). Un buscador (nombre, codigo SIS o
    curso, en el navegador) y una fila por auxiliar con su proximo examen y sus
    asignaciones dentro de los cursos del docente: cursos (ve Ingreso y
    Monitor) y examenes sueltos (con las paginas que ve). Las filas llegan
    ordenadas por el examen mas proximo; sin asignaciones, al final. Cada
    auxiliar tiene el boton "Asignar" (las asignaciones se quitan en
    "Desvincular auxiliar"), que abre el
    modal:

     - A un curso: selector con los cursos en curso del docente.
     - A un examen: selector con los examenes futuros de sus cursos y las
       casillas Ingreso / Monitor (al menos una).

    La eleccion curso / examen va con tarjetas de borde azul (sin radio). El
    layout lo pone el componente con extends('layouts.app').

    @changelog
    - 2026-11-22  [Valery D. Ortuno P]  feat: creacion inicial (HU 24).
    - 2026-11-24  [Valery D. Ortuno P]  style: una fila por auxiliar (antes dos
      columnas de tarjetas) con su proximo examen; el buscador tambien busca
      por curso y avisa "No se encontraron coincidencias". Orden "Examen
      proximo" (por defecto) o "A-Z". Paginacion de 8 por pagina en el
      navegador. No se muestran cursos archivados ni examenes pasados.
    - 2026-11-28  [Valery D. Ortuno P]  style/feat: en todos los tamanos, nombre y
      usuario arriba con "Asignar" a la derecha y las asignaciones debajo; sale
      "Proximo examen". Al asignar a un curso: "Podra ver Ingreso y Monitor..." y
      (solo el docente) los permisos "Puede crear auxiliares" y "Puede asignar
      auxiliares". Sale "Quitar" de las asignaciones y su modal: eso se hace
      en "Desvincular auxiliar".

    @see  App\Livewire\Auxiliares\AsignarAuxiliar
--}}

@section('title', 'Asignar auxiliar')

@php
    $datos = $this->datos;
    $auxiliares = $datos['auxiliares'];
    $cursosDocente = $datos['cursos'];
    $examenesDocente = $datos['examenes'];
    // Posicion de cada auxiliar en orden alfabetico (la lista ya llega
    // ordenada por el examen mas proximo).
    $posicionAlfabetica = collect($auxiliares)
        ->sortBy(fn ($auxiliar) => mb_strtolower($auxiliar['nombre']))
        ->keys()
        ->flip()
        ->all();

    $pillBase = 'shrink-0 whitespace-nowrap inline-flex items-center justify-center box-border border focus:ring-4 shadow-xs font-medium leading-5 rounded-full focus:outline-none px-4 py-2.5 text-sm';
    $pillActiva = 'text-white bg-brand border-transparent hover:bg-brand-strong focus:ring-brand-medium';
    $pillInactiva = 'text-body bg-neutral-primary-soft border-default hover:bg-neutral-secondary-medium hover:text-heading focus:ring-neutral-tertiary-soft';
@endphp

<div class="space-y-4 px-4 pt-[2vh] pb-8 sm:px-6 lg:px-[2vh]"
     x-data="{
         busqueda: '',
         // Orden: 'proximo' (por defecto) o 'alfabetico'.
         orden: 'proximo',
         // Modal Asignar.
         modalAsignar: false,
         auxiliar: null,
         destino: 'curso',
         idCurso: '',
         idExamen: '',
         verIngreso: true,
         verMonitor: true,
         error: '',
         guardando: false,
         // Una entrada por auxiliar: su posicion por examen proximo (i), su
         // posicion alfabetica (az) y el texto en el que se busca.
         items: @js(collect($auxiliares)->map(fn ($auxiliar, $indice) => [
             'i' => $indice,
             'az' => $posicionAlfabetica[$indice],
             'texto' => mb_strtolower(implode(' ', [
                 $auxiliar['nombre'], $auxiliar['sis'], $auxiliar['usuario'],
                 ...array_column($auxiliar['cursos'], 'nombre'),
                 ...array_column($auxiliar['examenes'], 'etiqueta'),
             ])),
         ])->values()),
         // Auxiliar que llega preseleccionado desde Asignar ahora.
         preseleccion: @js($auxiliarPreseleccionado),
         fichas: @js(collect($auxiliares)->map(fn ($auxiliar) => ['id' => $auxiliar['id'], 'nombre' => $auxiliar['nombre'], 'puedeCrear' => $auxiliar['puedeCrear'], 'puedeAsignar' => $auxiliar['puedeAsignar']])->values()),
         // Permisos de coordinador (solo los da un docente, al asignar a un curso).
         puedeCrear: false,
         puedeAsignar: false,
         // Paginacion en el navegador: buscar, ordenar y cambiar de pagina no
         // vuelven al servidor.
         pagina: 1,
         porPagina: 8,
         init() {
             this.$watch('busqueda', () => { this.pagina = 1; });
             this.$watch('orden', () => { this.pagina = 1; });
             // Asignar ahora: abre el modal del auxiliar recien creado.
             if (this.preseleccion) {
                 const ficha = this.fichas.find((f) => f.id === this.preseleccion);
                 if (ficha) {
                     this.abrirAsignar(ficha);
                 }
             }
         },
         coincide(texto) {
             const termino = this.busqueda.trim().toLowerCase();

             return termino === '' || texto.indexOf(termino) !== -1;
         },
         visibles() {
             const clave = this.orden === 'alfabetico' ? 'az' : 'i';

             return this.items
                 .filter((item) => this.coincide(item.texto))
                 .sort((a, b) => a[clave] - b[clave]);
         },
         hayCoincidencias() {
             return this.visibles().length > 0;
         },
         totalPaginas() {
             return Math.max(1, Math.ceil(this.visibles().length / this.porPagina));
         },
         enPagina(indice) {
             const posicion = this.visibles().findIndex((item) => item.i === indice);
             const desde = (this.pagina - 1) * this.porPagina;

             return posicion >= desde && posicion < desde + this.porPagina;
         },
         irPagina(numero) {
             this.pagina = Math.min(Math.max(1, numero), this.totalPaginas());
         },
         rango() {
             const total = this.visibles().length;
             const desde = (this.pagina - 1) * this.porPagina + 1;

             return 'Mostrando ' + desde + '-' + Math.min(total, desde + this.porPagina - 1) + ' de ' + total;
         },
         abrirAsignar(auxiliar) {
             this.auxiliar = auxiliar;
             this.destino = @js($cursosDocente !== [] ? 'curso' : 'examen');
             this.idCurso = '';
             this.idExamen = '';
             this.verIngreso = true;
             this.verMonitor = true;
             this.puedeCrear = !! auxiliar.puedeCrear;
             this.puedeAsignar = !! auxiliar.puedeAsignar;
             this.error = '';
             this.modalAsignar = true;
         },
         puedeGuardar() {
             if (this.destino === 'curso') {
                 return this.idCurso !== '';
             }

             return this.idExamen !== '' && (this.verIngreso || this.verMonitor);
         },
         async guardar() {
             if (this.guardando || ! this.puedeGuardar()) {
                 return;
             }
             this.guardando = true;
             this.error = '';
             try {
                 const error = this.destino === 'curso'
                     ? await $wire.asignarCurso(this.auxiliar.id, Number(this.idCurso), this.puedeCrear, this.puedeAsignar)
                     : await $wire.asignarExamen(this.auxiliar.id, Number(this.idExamen), this.verIngreso, this.verMonitor);
                 if (error) {
                     this.error = error;
                 } else {
                     this.modalAsignar = false;
                 }
             } catch (e) {
                 this.error = 'No se pudo conectar con el servidor.';
             } finally {
                 this.guardando = false;
             }
         },
     }">
    @if ($mensaje !== '')
        <div class="flex items-start justify-between gap-3 rounded-base border border-success-subtle bg-success-soft p-4 text-sm text-fg-success-strong" role="status">
            <p>{{ $mensaje }}</p>
            <button type="button" wire:click="cerrarMensaje" class="shrink-0 font-medium hover:underline">Cerrar</button>
        </div>
    @endif

    {{-- Barra blanca: buscador y orden. --}}
    <div class="bg-neutral-primary-soft border border-default rounded-base shadow-xs p-3 flex flex-col gap-3 sm:flex-row sm:items-center">
        <div class="flex-1 min-w-0">
            <x-ui.search-input name="busqueda-asignar-auxiliar" placeholder="Buscar por auxiliar, codigo SIS o curso"
                :show-button="false" pill x-model="busqueda" @keydown.enter.prevent />
        </div>
        <div class="flex gap-2 overflow-x-auto sm:shrink-0" role="group" aria-label="Ordenar">
            <button type="button" class="{{ $pillBase }}" :aria-pressed="orden === 'proximo'"
                    :class="orden === 'proximo' ? '{{ $pillActiva }}' : '{{ $pillInactiva }}'" @click="orden = 'proximo'">
                Examen proximo
            </button>
            <button type="button" class="{{ $pillBase }}" :aria-pressed="orden === 'alfabetico'"
                    :class="orden === 'alfabetico' ? '{{ $pillActiva }}' : '{{ $pillInactiva }}'" @click="orden = 'alfabetico'">
                A-Z
            </button>
        </div>
    </div>

    @if ($auxiliares === [])
        <p class="rounded-base border border-default bg-neutral-primary-soft p-8 text-center text-sm text-body shadow-xs">
            No hay auxiliares registrados. Crea uno desde "Crear auxiliar".
        </p>
    @else
        {{-- Una fila por auxiliar: nombre y usuario con "Asignar" a la derecha y
             debajo sus asignaciones. El orden se cambia con `order` (sin volver al
             servidor): la lista llega por examen mas proximo. --}}
        <div class="flex flex-col gap-3">
            @foreach ($auxiliares as $indice => $auxiliar)
                @php
                    $sinAsignaciones = $auxiliar['cursos'] === [] && $auxiliar['examenes'] === [];
                    $datosAsignar = \Illuminate\Support\Js::from(['id' => $auxiliar['id'], 'nombre' => $auxiliar['nombre'], 'puedeCrear' => $auxiliar['puedeCrear'], 'puedeAsignar' => $auxiliar['puedeAsignar']]);
                @endphp
                <article wire:key="auxiliar-{{ $auxiliar['id'] }}"
                         x-show="enPagina({{ $indice }})"
                         :style="'order: ' + (orden === 'alfabetico' ? {{ $posicionAlfabetica[$indice] }} : {{ $indice }})"
                         class="flex flex-col gap-4 rounded-base border border-default bg-neutral-primary-soft p-4 shadow-xs sm:p-5">
                    {{-- Nombre y usuario arriba; Asignar a la derecha, a la altura del nombre. --}}
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-lg font-semibold text-heading wrap-break-word">{{ $auxiliar['nombre'] }}</p>
                            <p class="text-xs text-body wrap-break-word">Usuario: {{ $auxiliar['usuario'] }}</p>
                            @if ($auxiliar['puedeCrear'] || $auxiliar['puedeAsignar'])
                                <p class="mt-1 text-xs font-medium text-fg-brand">
                                    {{ implode(' · ', array_filter([$auxiliar['puedeCrear'] ? 'Puede crear auxiliares' : null, $auxiliar['puedeAsignar'] ? 'Puede asignar auxiliares' : null])) }}
                                </p>
                            @endif
                        </div>
                        <x-ui.button type="button" class="shrink-0" @click="abrirAsignar({{ $datosAsignar }})">
                            Asignar
                        </x-ui.button>
                    </div>

                    {{-- Asignaciones dentro de los cursos del docente. --}}
                    <div class="min-w-0 border-t border-default pt-4">
                        @if ($sinAsignaciones)
                            <p class="text-sm text-body">Sin asignaciones en tus cursos.</p>
                        @else
                            <ul class="space-y-2 text-sm">
                                @foreach ($auxiliar['cursos'] as $curso)
                                    <li class="flex items-start justify-between gap-3 rounded-base border border-default px-3 py-2">
                                        <div class="min-w-0">
                                            <p class="font-medium text-heading wrap-break-word">Curso: {{ $curso['nombre'] }}</p>
                                            <p class="text-xs text-body">Ingreso y Monitor en todos sus examenes</p>
                                        </div>
                                    </li>
                                @endforeach
                                @foreach ($auxiliar['examenes'] as $examen)
                                    <li class="flex items-start justify-between gap-3 rounded-base border border-default px-3 py-2">
                                        <div class="min-w-0">
                                            <p class="font-medium text-heading wrap-break-word">Examen: {{ $examen['etiqueta'] }}</p>
                                            <p class="text-xs text-body">
                                                {{ $examen['ingreso'] && $examen['monitor'] ? 'Ingreso y Monitor' : ($examen['ingreso'] ? 'Solo Ingreso' : 'Solo Monitor') }}
                                            </p>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </article>
            @endforeach

            <p x-show="! hayCoincidencias()" x-cloak style="order: 99999"
               class="rounded-base border border-default bg-neutral-primary-soft p-8 text-center text-sm text-body shadow-xs">
                No se encontraron coincidencias
            </p>
        </div>

        {{-- Paginacion: 8 auxiliares por pagina. --}}
        <div x-show="totalPaginas() > 1" x-cloak class="flex flex-col items-center gap-2 sm:flex-row sm:justify-between">
            <p class="text-sm text-body" x-text="rango()"></p>
            <nav aria-label="Paginacion de auxiliares">
                <ul class="flex -space-x-px text-sm">
                    <li>
                        <button type="button" @click="irPagina(pagina - 1)" :disabled="pagina === 1"
                                class="flex h-9 w-9 items-center justify-center rounded-s-base border border-default-medium bg-neutral-secondary-medium font-medium text-body hover:bg-neutral-tertiary-medium hover:text-heading disabled:opacity-50">
                            <span class="sr-only">Anterior</span>
                            <svg class="h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m15 19-7-7 7-7"/></svg>
                        </button>
                    </li>
                    <template x-for="numero in totalPaginas()" :key="numero">
                        <li>
                            <button type="button" @click="irPagina(numero)" x-text="numero"
                                    :aria-current="numero === pagina ? 'page' : null"
                                    :class="numero === pagina ? 'text-white bg-brand border-brand' : 'text-body bg-neutral-secondary-medium border-default-medium hover:bg-neutral-tertiary-medium hover:text-heading'"
                                    class="flex h-9 w-9 items-center justify-center border font-medium"></button>
                        </li>
                    </template>
                    <li>
                        <button type="button" @click="irPagina(pagina + 1)" :disabled="pagina === totalPaginas()"
                                class="flex h-9 w-9 items-center justify-center rounded-e-base border border-default-medium bg-neutral-secondary-medium font-medium text-body hover:bg-neutral-tertiary-medium hover:text-heading disabled:opacity-50">
                            <span class="sr-only">Siguiente</span>
                            <svg class="h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m9 5 7 7-7 7"/></svg>
                        </button>
                    </li>
                </ul>
            </nav>
        </div>
    @endif

    {{-- Modal Asignar. --}}
    <div x-show="modalAsignar" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-black/50 p-4"
         role="dialog" aria-modal="true" aria-labelledby="titulo-asignar-auxiliar"
         @click.self="modalAsignar = false" @keydown.escape.window="modalAsignar = false">
        <div class="w-full max-w-lg rounded-xl border border-neutral-quaternary bg-neutral-primary-soft shadow-lg">
            <div class="flex items-center gap-2 border-b border-neutral-quaternary px-4 py-3">
                <h2 id="titulo-asignar-auxiliar" class="min-w-0 flex-1 text-[15px] font-semibold text-fg-brand">
                    Asignar a <span x-text="auxiliar?.nombre"></span>
                </h2>
                <button type="button" @click="modalAsignar = false" aria-label="Cerrar"
                        class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-body hover:bg-neutral-secondary-medium hover:text-heading focus:outline-none">
                    <svg class="h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 17.94 6M18 18 6.06 6"/></svg>
                </button>
            </div>

            <div class="max-h-[65vh] space-y-4 overflow-y-auto p-4 text-sm">
                {{-- Destino: tarjetas con borde azul al elegir, sin radio. --}}
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2" role="group" aria-label="Asignar a">
                    <button type="button" @click="destino = 'curso'; error = ''"
                            :aria-pressed="destino === 'curso' ? 'true' : 'false'"
                            :class="destino === 'curso' ? 'border-brand ring-2 ring-brand' : 'border-default'"
                            class="rounded-base border bg-neutral-primary-soft p-3 text-center">
                        <p class="font-semibold text-heading">A un curso</p>
                    </button>
                    <button type="button" @click="destino = 'examen'; error = ''"
                            :aria-pressed="destino === 'examen' ? 'true' : 'false'"
                            :class="destino === 'examen' ? 'border-brand ring-2 ring-brand' : 'border-default'"
                            class="rounded-base border bg-neutral-primary-soft p-3 text-center">
                        <p class="font-semibold text-heading">A un examen</p>
                    </button>
                </div>

                <div x-show="destino === 'curso'">
                    @if ($cursosDocente === [])
                        <p class="text-body">No tienes cursos en curso.</p>
                    @else
                        <label for="asignar-curso" class="mb-2 block font-medium text-heading">Curso</label>
                        <select id="asignar-curso" x-model="idCurso"
                                class="block w-full rounded-base border border-default-medium bg-neutral-secondary-medium px-3 py-2.5 text-heading shadow-xs focus:border-brand focus:ring-brand">
                            <option value="">Selecciona un curso</option>
                            @foreach ($cursosDocente as $curso)
                                <option value="{{ $curso['id'] }}">{{ $curso['nombre'] }}</option>
                            @endforeach
                        </select>
                        <p class="mt-2 text-body">Podra ver Ingreso y Monitor de todos los examenes de este curso.</p>
                        @if ($this->esDocente)
                            <fieldset class="mt-4">
                                <legend class="mb-2 font-medium text-heading">Permisos</legend>
                                <div class="flex flex-col gap-2">
                                    <label class="inline-flex items-center gap-2 text-heading">
                                        <input type="checkbox" x-model="puedeCrear" class="h-4 w-4 rounded-sm border-default-medium text-brand focus:ring-brand">
                                        Puede crear auxiliares
                                    </label>
                                    <label class="inline-flex items-center gap-2 text-heading">
                                        <input type="checkbox" x-model="puedeAsignar" class="h-4 w-4 rounded-sm border-default-medium text-brand focus:ring-brand">
                                        Puede asignar auxiliares
                                    </label>
                                </div>
                                <p class="mt-2 text-xs text-body">Solo los auxiliares que cree, en los cursos donde es auxiliar y en los examenes que tiene asignados.</p>
                            </fieldset>
                        @endif
                    @endif
                </div>

                <div x-show="destino === 'examen'" class="space-y-4">
                    @if ($examenesDocente === [])
                        <p class="text-body">No tienes examenes futuros en tus cursos.</p>
                    @else
                        <div>
                            <label for="asignar-examen" class="mb-2 block font-medium text-heading">Examen</label>
                            <select id="asignar-examen" x-model="idExamen"
                                    class="block w-full rounded-base border border-default-medium bg-neutral-secondary-medium px-3 py-2.5 text-heading shadow-xs focus:border-brand focus:ring-brand">
                                <option value="">Selecciona un examen</option>
                                @foreach ($examenesDocente as $examen)
                                    <option value="{{ $examen['id'] }}">{{ $examen['etiqueta'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <fieldset>
                            <legend class="mb-2 font-medium text-heading">Que puede ver</legend>
                            <div class="flex flex-col gap-2 sm:flex-row sm:gap-6">
                                <label class="inline-flex items-center gap-2 text-heading">
                                    <input type="checkbox" x-model="verIngreso" class="h-4 w-4 rounded-sm border-default-medium text-brand focus:ring-brand">
                                    Ingreso
                                </label>
                                <label class="inline-flex items-center gap-2 text-heading">
                                    <input type="checkbox" x-model="verMonitor" class="h-4 w-4 rounded-sm border-default-medium text-brand focus:ring-brand">
                                    Monitor
                                </label>
                            </div>
                            <p x-show="! verIngreso && ! verMonitor" class="mt-2 text-fg-danger-strong">Marca al menos Ingreso o Monitor.</p>
                        </fieldset>
                    @endif
                </div>

                <p x-show="error !== ''" x-text="error" class="text-fg-danger-strong"></p>
            </div>

            <div class="flex flex-col gap-2 border-t border-neutral-quaternary px-4 py-3 sm:flex-row sm:justify-end">
                <button type="button" @click="modalAsignar = false"
                        class="inline-flex items-center justify-center rounded-base border border-default px-4 py-2 text-sm font-medium text-body hover:bg-neutral-secondary-medium">
                    Cancelar
                </button>
                <button type="button" @click="guardar()" :disabled="guardando || ! puedeGuardar()"
                        class="inline-flex items-center justify-center rounded-base bg-brand px-4 py-2 text-sm font-medium text-white hover:bg-brand-strong disabled:cursor-not-allowed disabled:opacity-50">
                    <span x-text="guardando ? 'Asignando...' : 'Asignar'"></span>
                </button>
            </div>
        </div>
    </div>
</div>
