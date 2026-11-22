{{--
    @file    asignar-auxiliar.blade.php
    @author  Valery D. Ortuno P <valerydariana98@gmail.com>
    @created 2026-11-22

    @description
    Vista de "Asignar auxiliar" (HU 24). Un buscador (nombre o codigo SIS, en
    el navegador) y una tarjeta por auxiliar con sus asignaciones dentro de
    los cursos del docente: cursos (ve Ingreso y Monitor) y examenes sueltos
    (con las paginas que ve). Cada asignacion tiene "Quitar" y cada auxiliar
    el boton "Asignar", que abre el modal:

     - A un curso: selector con los cursos en curso del docente.
     - A un examen: selector con los examenes futuros de sus cursos y las
       casillas Ingreso / Monitor (al menos una).

    La eleccion curso / examen va con tarjetas de borde azul (sin radio). El
    layout lo pone el componente con extends('layouts.app').

    @changelog
    - 2026-11-22  [Valery D. Ortuno P]  feat: creacion inicial (HU 24).

    @see  App\Livewire\Auxiliares\AsignarAuxiliar
--}}

@section('title', 'Asignar auxiliar')

@php
    $datos = $this->datos;
    $auxiliares = $datos['auxiliares'];
    $cursosDocente = $datos['cursos'];
    $examenesDocente = $datos['examenes'];
@endphp

<div class="space-y-4 px-4 pt-[2vh] pb-8 sm:px-6 lg:px-[2vh]"
     x-data="{
         busqueda: '',
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
         // Modal Quitar.
         modalQuitar: false,
         quitar: null,
         errorQuitar: '',
         quitando: false,
         coincide(texto) {
             const termino = this.busqueda.trim().toLowerCase();

             return termino === '' || texto.indexOf(termino) !== -1;
         },
         abrirAsignar(auxiliar) {
             this.auxiliar = auxiliar;
             this.destino = @js($cursosDocente !== [] ? 'curso' : 'examen');
             this.idCurso = '';
             this.idExamen = '';
             this.verIngreso = true;
             this.verMonitor = true;
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
                     ? await $wire.asignarCurso(this.auxiliar.id, Number(this.idCurso))
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
         abrirQuitar(datos) {
             this.quitar = datos;
             this.errorQuitar = '';
             this.modalQuitar = true;
         },
         async confirmarQuitar() {
             if (this.quitando) {
                 return;
             }
             this.quitando = true;
             this.errorQuitar = '';
             try {
                 const error = this.quitar.tipo === 'curso'
                     ? await $wire.quitarCurso(this.quitar.auxiliar, this.quitar.id)
                     : await $wire.quitarExamen(this.quitar.auxiliar, this.quitar.id);
                 if (error) {
                     this.errorQuitar = error;
                 } else {
                     this.modalQuitar = false;
                 }
             } catch (e) {
                 this.errorQuitar = 'No se pudo conectar con el servidor.';
             } finally {
                 this.quitando = false;
             }
         },
     }">
    @if ($mensaje !== '')
        <div class="flex items-start justify-between gap-3 rounded-base border border-success-subtle bg-success-soft p-4 text-sm text-fg-success-strong" role="status">
            <p>{{ $mensaje }}</p>
            <button type="button" wire:click="cerrarMensaje" class="shrink-0 font-medium hover:underline">Cerrar</button>
        </div>
    @endif

    <div class="bg-neutral-primary-soft border border-default rounded-base shadow-xs p-3">
        <x-ui.search-input name="busqueda-asignar-auxiliar" placeholder="Buscar auxiliar por nombre o codigo SIS"
            :show-button="false" pill x-model="busqueda" @keydown.enter.prevent />
    </div>

    @if ($auxiliares === [])
        <p class="rounded-base border border-default bg-neutral-primary-soft p-8 text-center text-sm text-body shadow-xs">
            No hay auxiliares registrados. Crea uno desde "Crear auxiliar".
        </p>
    @else
        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
            @foreach ($auxiliares as $auxiliar)
                @php
                    $busqueda = mb_strtolower($auxiliar['nombre'].' '.$auxiliar['sis']);
                    $sinAsignaciones = $auxiliar['cursos'] === [] && $auxiliar['examenes'] === [];
                @endphp
                <article wire:key="auxiliar-{{ $auxiliar['id'] }}"
                         x-show="coincide(@js($busqueda))"
                         class="flex flex-col gap-4 rounded-base border border-default bg-neutral-primary-soft p-4 shadow-xs sm:p-5">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="font-semibold text-heading wrap-break-word">{{ $auxiliar['nombre'] }}</p>
                            <p class="text-xs text-body">Codigo SIS: {{ $auxiliar['sis'] }}</p>
                        </div>
                        <x-ui.button type="button" class="shrink-0"
                                     @click="abrirAsignar({{ \Illuminate\Support\Js::from(['id' => $auxiliar['id'], 'nombre' => $auxiliar['nombre']]) }})">
                            Asignar
                        </x-ui.button>
                    </div>

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
                                    <button type="button" class="shrink-0 text-xs font-bold text-fg-danger-strong hover:underline"
                                            @click="abrirQuitar({{ \Illuminate\Support\Js::from(['tipo' => 'curso', 'auxiliar' => $auxiliar['id'], 'id' => $curso['id'], 'texto' => 'el curso '.$curso['nombre'], 'nombre' => $auxiliar['nombre']]) }})">
                                        Quitar
                                    </button>
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
                                    <button type="button" class="shrink-0 text-xs font-bold text-fg-danger-strong hover:underline"
                                            @click="abrirQuitar({{ \Illuminate\Support\Js::from(['tipo' => 'examen', 'auxiliar' => $auxiliar['id'], 'id' => $examen['id'], 'texto' => 'el examen '.$examen['etiqueta'], 'nombre' => $auxiliar['nombre']]) }})">
                                        Quitar
                                    </button>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </article>
            @endforeach
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

    {{-- Modal Quitar. --}}
    <div x-show="modalQuitar" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-black/50 p-4"
         role="dialog" aria-modal="true" aria-labelledby="titulo-quitar-asignacion"
         @click.self="modalQuitar = false" @keydown.escape.window="modalQuitar = false">
        <div class="w-full max-w-md rounded-xl border border-neutral-quaternary bg-neutral-primary-soft shadow-lg">
            <div class="border-b border-neutral-quaternary px-4 py-3">
                <h2 id="titulo-quitar-asignacion" class="text-[15px] font-semibold text-fg-brand">Quitar asignacion</h2>
            </div>
            <div class="space-y-2 p-4 text-sm text-body">
                <p>
                    <span class="font-semibold text-heading" x-text="quitar?.nombre"></span>
                    dejara de ver <span x-text="quitar?.texto"></span> en Monitor en vivo.
                </p>
                <p x-show="errorQuitar !== ''" x-text="errorQuitar" class="text-fg-danger-strong"></p>
            </div>
            <div class="flex flex-col gap-2 border-t border-neutral-quaternary px-4 py-3 sm:flex-row sm:justify-end">
                <button type="button" @click="modalQuitar = false"
                        class="inline-flex items-center justify-center rounded-base border border-default px-4 py-2 text-sm font-medium text-body hover:bg-neutral-secondary-medium">
                    Cancelar
                </button>
                <button type="button" @click="confirmarQuitar()" :disabled="quitando"
                        class="inline-flex items-center justify-center rounded-base bg-danger px-4 py-2 text-sm font-medium text-white hover:bg-danger-strong disabled:opacity-50">
                    <span x-text="quitando ? 'Quitando...' : 'Quitar'"></span>
                </button>
            </div>
        </div>
    </div>
</div>
