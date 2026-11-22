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

    @see  App\Livewire\Auxiliares\ExamenesAuxiliar
--}}

@section('title', 'Examenes')

@php
    use App\Services\Auxiliar\ListarExamenesAuxiliarService as Lista;

    $examenes = $this->examenes;
    $totalFinalizados = collect($examenes)->where('estado', Lista::FINALIZADO)->count();
    $totalProximos = count($examenes) - $totalFinalizados;

    $pillBase = 'shrink-0 whitespace-nowrap inline-flex items-center justify-center box-border border focus:ring-4 shadow-xs font-medium leading-5 rounded-full focus:outline-none px-4 py-2.5 text-sm';
    $pillActiva = 'text-white bg-brand border-transparent hover:bg-brand-strong focus:ring-brand-medium';
    $pillInactiva = 'text-body bg-neutral-primary-soft border-default hover:bg-neutral-secondary-medium hover:text-heading focus:ring-neutral-tertiary-soft';
@endphp

<div class="space-y-4 px-4 pt-[2vh] pb-8 sm:px-6 lg:px-[2vh]"
     x-data="{
         filtro: 'proximos',
         visible(finalizado) {
             return this.filtro === 'todos' || (this.filtro === 'finalizados') === finalizado;
         },
     }">
    @if ($examenes === [])
        <p class="rounded-base border border-default bg-neutral-primary-soft p-8 text-center text-sm text-body shadow-xs">
            Todavia no tienes examenes asignados.
        </p>
    @else
        <div class="flex gap-2 overflow-x-auto" role="group" aria-label="Filtro de examenes">
            <button type="button" class="{{ $pillBase }}" :aria-pressed="filtro === 'proximos'" :class="filtro === 'proximos' ? '{{ $pillActiva }}' : '{{ $pillInactiva }}'" @click="filtro = 'proximos'">
                Proximos {{ $totalProximos }}
            </button>
            <button type="button" class="{{ $pillBase }}" :aria-pressed="filtro === 'finalizados'" :class="filtro === 'finalizados' ? '{{ $pillActiva }}' : '{{ $pillInactiva }}'" @click="filtro = 'finalizados'">
                Finalizados {{ $totalFinalizados }}
            </button>
            <button type="button" class="{{ $pillBase }}" :aria-pressed="filtro === 'todos'" :class="filtro === 'todos' ? '{{ $pillActiva }}' : '{{ $pillInactiva }}'" @click="filtro = 'todos'">
                Todos {{ count($examenes) }}
            </button>
        </div>

        <ul class="flex flex-col gap-4">
            @foreach ($examenes as $examen)
                @php $finalizado = $examen['estado'] === Lista::FINALIZADO; @endphp
                <li wire:key="examen-auxiliar-{{ $examen['id'] }}" x-show="visible(@js($finalizado))"
                    class="flex flex-col gap-4 rounded-base border border-default bg-neutral-primary-soft px-5 py-6 shadow-xs sm:px-8 lg:flex-row lg:items-center lg:gap-6">
                    <div class="flex min-w-0 flex-col items-start gap-1 lg:w-80 lg:shrink-0">
                        <h3 class="text-xl font-bold text-heading wrap-break-word">{{ $examen['tipo'] }} - {{ $examen['cursos'] }}</h3>
                        <div class="flex flex-wrap gap-2">
                            <x-ui.badge :type="match ($examen['estado']) { Lista::EN_CURSO => 'warning', Lista::FINALIZADO => 'brand', default => 'gray' }">
                                {{ $examen['estado'] }}
                            </x-ui.badge>
                            <span class="inline-flex items-center rounded-full border border-brand-subtle bg-brand-softer px-1.5 py-0.5 text-xs font-medium text-fg-brand-strong">
                                {{ $examen['acceso'] }}
                            </span>
                        </div>
                        <p class="text-xs text-body">{{ $examen['origen'] }}</p>
                    </div>

                    <div class="grid flex-1 grid-cols-1 gap-4 sm:grid-cols-3">
                        <div class="flex flex-col gap-1.5 sm:items-center sm:text-center">
                            <span class="text-xs font-semibold uppercase text-muted">Fecha:</span>
                            <span class="text-sm font-mono text-body">{{ $examen['fecha'] }}</span>
                        </div>
                        <div class="flex flex-col gap-1.5 sm:items-center sm:text-center">
                            <span class="text-xs font-semibold uppercase text-muted">Horario:</span>
                            <span class="text-sm font-mono text-body">{{ $examen['horario'] }}</span>
                        </div>
                        <div class="flex flex-col gap-1.5 sm:items-center sm:text-center">
                            <span class="text-xs font-semibold uppercase text-muted">Ambientes:</span>
                            <span class="text-sm font-mono text-body wrap-break-word">{{ $examen['ambientes'] }}</span>
                        </div>
                    </div>
                </li>
            @endforeach
        </ul>

        <p x-show="filtro === 'proximos' && {{ $totalProximos }} === 0" class="text-center text-sm text-body">No tienes examenes proximos.</p>
        <p x-show="filtro === 'finalizados' && {{ $totalFinalizados }} === 0" class="text-center text-sm text-body">No tienes examenes finalizados.</p>
    @endif
</div>
