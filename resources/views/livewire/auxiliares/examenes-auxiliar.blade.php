{{--
    @file    examenes-auxiliar.blade.php
    @author  Valery D. Ortuno P <valerydariana98@gmail.com>
    @created 2026-11-22

    @description
    Vista de "Ver examenes" del auxiliar (HU 24): una tarjeta por examen
    asignado con tipo y cursos, estado, fecha, horario, ambientes, que
    paginas de Monitor en vivo puede ver y por que (por su curso o asignado al
    examen). Filtros Proximos / Finalizados / Todos en el navegador. Es solo de
    consulta. El layout lo pone el componente con extends('layouts.app').

    @changelog
    - 2026-11-22  [Valery D. Ortuno P]  feat: creacion inicial (HU 24).
    - 2026-11-24  [Alisson D. Alvarado <alvaradoalissondalet@gmail.com>]  style:
      adaptar filtros, tarjetas y detalle al formato de examenes del docente.
    - 2026-11-24  [Alisson D. Alvarado <alvaradoalissondalet@gmail.com>]  style:
      igualar los contenedores y espacios a la vista del docente.

    @see  App\Livewire\Auxiliares\ExamenesAuxiliar
--}}

@section('title', 'Examenes')

@php
    use App\Services\Auxiliar\ListarExamenesAuxiliarService as Lista;

    $examenes = $this->examenes;
    $pillBase = 'shrink-0 whitespace-nowrap inline-flex items-center justify-center box-border border focus:ring-4 shadow-xs font-medium leading-5 rounded-full focus:outline-none px-4 py-2.5 text-sm';
    $pillActiva = 'text-white bg-brand border-transparent hover:bg-brand-strong focus:ring-brand-medium';
    $pillInactiva = 'text-body bg-neutral-primary-soft border-default hover:bg-neutral-secondary-medium hover:text-heading focus:ring-neutral-tertiary-soft';
@endphp

<div class="px-4 py-4 sm:px-6 sm:py-6 lg:px-8"
     x-data="{
         filtro: 'todos',
         curso: '',
         detalle: null,
         examenes: @js($examenes),
         visible(examen) {
             const finalizado = examen.estado === @js(Lista::FINALIZADO);
             return (this.curso === '' || examen.nombres_cursos.includes(this.curso))
                 && (this.filtro === 'todos' || (this.filtro === 'finalizados') === finalizado);
         },
         get filtrados() { return this.examenes.filter((examen) => this.visible(examen)); },
         get delCurso() { return this.examenes.filter((examen) => this.curso === '' || examen.nombres_cursos.includes(this.curso)); },
         get finalizados() { return this.delCurso.filter((examen) => examen.estado === @js(Lista::FINALIZADO)).length; },
     }">
    {{-- La estructura interna coincide con el listado del docente. --}}
    <div class="flex flex-col gap-4">
        <div class="mt-4 bg-neutral-primary-soft border border-default rounded-base shadow-xs p-3 flex flex-col gap-3 sm:flex-row sm:items-center">
            <div class="min-w-0 sm:w-72 lg:w-96">
                <label for="auxiliar-examen-curso" class="block mb-2.5 text-sm font-medium text-heading sm:sr-only">Curso</label>
                <select id="auxiliar-examen-curso" x-model="curso"
                        class="block w-full px-3 py-2.5 border text-sm rounded-base shadow-xs bg-neutral-secondary-medium border-default-medium text-heading focus:ring-brand focus:border-brand">
                    <option value="">Todos los cursos</option>
                    @foreach (collect($examenes)->pluck('nombres_cursos')->flatten()->filter()->unique()->sort() as $nombreCurso)
                        <option value="{{ $nombreCurso }}">{{ $nombreCurso }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex gap-2 overflow-x-auto sm:ml-auto sm:shrink-0" role="group" aria-label="Filtro de examenes">
            <button type="button" class="{{ $pillBase }}" :aria-pressed="filtro === 'todos'" :class="filtro === 'todos' ? '{{ $pillActiva }}' : '{{ $pillInactiva }}'" @click="filtro = 'todos'">
                Todos <span class="ml-1.5" x-text="delCurso.length"></span>
            </button>
            <button type="button" class="{{ $pillBase }}" :aria-pressed="filtro === 'programados'" :class="filtro === 'programados' ? '{{ $pillActiva }}' : '{{ $pillInactiva }}'" @click="filtro = 'programados'">
                Programados <span class="ml-1.5" x-text="delCurso.length - finalizados"></span>
            </button>
            <button type="button" class="{{ $pillBase }}" :aria-pressed="filtro === 'finalizados'" :class="filtro === 'finalizados' ? '{{ $pillActiva }}' : '{{ $pillInactiva }}'" @click="filtro = 'finalizados'">
                Finalizados <span class="ml-1.5" x-text="finalizados"></span>
            </button>
            </div>
        </div>

        <ul class="mt-4 flex flex-col gap-3">
            @foreach ($examenes as $examen)
                <li wire:key="examen-auxiliar-{{ $examen['id'] }}" x-show="visible(@js($examen))" x-cloak
                    class="rounded-base bg-neutral-primary-soft shadow-xs border border-default p-4 sm:p-5 grid grid-cols-1 gap-4 lg:grid-cols-[minmax(0,1.6fr)_minmax(0,1fr)_minmax(0,1fr)] lg:items-center lg:gap-0 lg:divide-x lg:divide-default">
                    <div class="min-w-0 lg:pr-6">
                        <div class="flex flex-wrap items-center gap-x-3 gap-y-1.5">
                            <h3 class="text-lg font-semibold text-heading wrap-break-word">{{ $examen['tipo'] }}</h3>
                            <x-ui.badge :type="match ($examen['estado']) { Lista::EN_CURSO => 'warning', Lista::FINALIZADO => 'brand', default => 'gray' }">
                                {{ $examen['estado'] }}
                            </x-ui.badge>
                        </div>
                        <p class="mt-1 text-xs text-body wrap-break-word">Curso: {{ $examen['cursos'] ?: 'Sin curso' }}</p>
                    </div>
                    <div class="border-t border-default pt-4 lg:border-t-0 lg:pt-0 lg:px-6">
                        <p class="text-xs font-semibold uppercase text-muted">Fecha:</p>
                        <p class="mt-1 text-sm font-medium text-heading">{{ $examen['fecha'] }}</p>
                        <p class="text-sm text-body">{{ $examen['horario'] }}</p>
                    </div>
                    <div class="border-t border-default pt-4 lg:border-t-0 lg:pt-0 lg:pl-6 flex justify-end">
                        <button type="button" @click="detalle = @js($examen)"
                                class="cursor-pointer whitespace-nowrap border-0 bg-transparent p-0 text-xs font-bold text-fg-brand-strong hover:underline">
                            Ver detalle ›
                        </button>
                    </div>
                </li>
            @endforeach
            <li x-show="filtrados.length === 0" x-cloak
                class="flex flex-col items-center gap-2 p-8 text-center bg-neutral-primary-soft border border-default rounded-base shadow-xs">
                <p class="text-sm text-body">{{ $examenes === [] ? 'No tienes examenes asignados.' : 'No se encontraron examenes' }}</p>
            </li>
        </ul>
        {{-- El detalle usa los datos ya autorizados para el auxiliar. --}}
        <div x-show="detalle !== null" x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-black/50 p-4"
             role="dialog" aria-modal="true" aria-labelledby="titulo-detalle-auxiliar"
             @click.self="detalle = null" @keydown.escape.window="detalle = null">
            <div class="w-full max-w-lg rounded-xl border border-neutral-quaternary bg-neutral-primary-soft shadow-lg">
                <div class="flex items-center gap-2 border-b border-neutral-quaternary px-4 py-3">
                    <h2 id="titulo-detalle-auxiliar" class="min-w-0 flex-1 text-[15px] font-semibold text-fg-brand">Detalles del examen</h2>
                    <button type="button" @click="detalle = null" aria-label="Cerrar detalles del examen"
                            class="inline-flex h-9 w-9 items-center justify-center rounded-full text-body hover:bg-neutral-secondary-medium">✕</button>
                </div>
                <div class="max-h-[65vh] space-y-4 overflow-y-auto p-4">
                    <div class="rounded-xl border border-brand-subtle bg-brand-softer p-4">
                        <p class="font-semibold text-fg-brand-strong wrap-break-word" x-text="detalle ? detalle.tipo + ' - ' + detalle.cursos : ''"></p>
                    </div>
                    <dl class="grid grid-cols-2 gap-4 text-sm">
                        <template x-for="campo in [{ etiqueta: 'Estado', clave: 'estado' }, { etiqueta: 'Fecha', clave: 'fecha' }, { etiqueta: 'Horario', clave: 'horario' }, { etiqueta: 'Ambientes', clave: 'ambientes' }, { etiqueta: 'Acceso', clave: 'acceso' }, { etiqueta: 'Asignacion', clave: 'origen' }]" :key="campo.clave">
                            <div><dt class="text-[10px] font-medium uppercase tracking-wider text-body" x-text="campo.etiqueta"></dt>
                                <dd class="mt-1 font-medium text-heading wrap-break-word" x-text="detalle?.[campo.clave]"></dd></div>
                        </template>
                    </dl>
                </div>
                <div class="flex justify-end border-t border-neutral-quaternary px-4 py-3">
                    <button type="button" @click="detalle = null" class="inline-flex items-center justify-center rounded-base bg-brand px-4 py-2 text-sm font-medium text-white shadow-xs hover:bg-brand-strong">Aceptar</button>
                </div>
            </div>
        </div>
    </div>
</div>
