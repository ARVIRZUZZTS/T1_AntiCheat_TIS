{{--
    @file    lista-examenes.blade.php
    @author  Diego Tejerina <josediegotejerinamolina@gmail.com>
    @created 2026-11-15

    @description
    Vista de "Ver lista de exámenes": buscador dinámico + filtros rápidos
    (Propios / Invitados y Próximos / Finalizados) y la lista de exámenes del
    docente. Sin selector de materia: muestra todos juntos. Cada examen enlaza a
    Editar (si es propio y futuro) reutilizando la subopción Editar examen.

    @changelog
    - 2026-11-15  [Diego Tejerina]  feat: creación de la lista plana con filtros.

    @see  app/Livewire/Examenes/ListaExamenes.php
--}}

@section('title', 'Exámenes')

@php $examenes = $this->examenes(); @endphp

<div class="px-4 py-4 sm:px-6 sm:py-6 lg:px-8">
    {{-- Buscador + filtros rápidos. --}}
    <div class="bg-neutral-primary-soft border border-default rounded-base shadow-xs p-3 flex flex-col gap-3 lg:flex-row lg:items-center">
        <div class="flex-1 min-w-0">
            <x-ui.search-input name="busqueda-examen" placeholder="Buscar por nombre, tipo o materia"
                :show-button="false" pill wire:model.live.debounce.300ms="busqueda" />
        </div>

        <div class="flex gap-2 overflow-x-auto lg:shrink-0" role="group" aria-label="Filtro de propiedad">
            @foreach (['todos' => 'Todos', 'propio' => 'Propios', 'invitado' => 'Invitados'] as $valor => $etiqueta)
                <x-ui.button type="button" variant="{{ $propiedad === $valor ? 'default' : 'secondary' }}"
                             wire:click="filtrarPropiedad('{{ $valor }}')">
                    {{ $etiqueta }}
                </x-ui.button>
            @endforeach
        </div>

        <div class="flex gap-2 overflow-x-auto lg:shrink-0" role="group" aria-label="Filtro por fecha">
            @foreach (['todos' => 'Todas', 'proximos' => 'Próximos', 'finalizados' => 'Finalizados'] as $valor => $etiqueta)
                <x-ui.button type="button" variant="{{ $fecha === $valor ? 'default' : 'secondary' }}"
                             wire:click="filtrarFecha('{{ $valor }}')">
                    {{ $etiqueta }}
                </x-ui.button>
            @endforeach
        </div>
    </div>

    {{-- Lista de exámenes. --}}
    <div class="mt-4 space-y-3">
        @forelse ($examenes as $examen)
            <div class="rounded-base bg-neutral-primary-soft shadow-xs border border-default p-4 sm:p-5 flex flex-col gap-3 lg:flex-row lg:items-center lg:gap-6">
                <div class="flex items-center gap-3 min-w-0 lg:w-96 lg:shrink-0">
                    <span class="shrink-0 flex items-center justify-center w-10 h-10 sm:w-12 sm:h-12 rounded-base bg-brand-softer text-fg-brand">
                        <svg class="w-5 h-5 sm:w-6 sm:h-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m-6 4h6m-6 4h4M6 3h12a1 1 0 0 1 1 1v16a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1Z"/></svg>
                    </span>
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-x-3 gap-y-1.5">
                            <p class="font-semibold text-heading break-words">{{ $examen['nombre'] }}</p>
                            <x-ui.badge :type="$examen['es_propio'] ? 'status-habilitado' : 'gray'">
                                {{ $examen['es_propio'] ? 'Propio' : 'Invitado' }}
                            </x-ui.badge>
                            @if ($examen['finalizado'])
                                <x-ui.badge type="gray">Finalizado</x-ui.badge>
                            @endif
                        </div>
                        <p class="mt-1 text-xs text-body">{{ $examen['materias'] ?: 'Sin materia' }}</p>
                    </div>
                </div>

                <div class="w-full border-t border-default pt-3 lg:w-auto lg:flex-1 lg:border-t-0 lg:border-l lg:pl-6 lg:pt-0">
                    <div class="grid grid-cols-2 gap-2 sm:grid-cols-4 text-sm text-body">
                        <div><span class="block text-[11px] text-muted">Tipo</span>{{ $examen['tipo'] }}</div>
                        <div><span class="block text-[11px] text-muted">Fecha</span>{{ \Illuminate\Support\Carbon::parse($examen['fecha'])->format('d/m/Y') }}</div>
                        <div><span class="block text-[11px] text-muted">Hora</span>{{ $examen['hora_inicio'] }}</div>
                        <div><span class="block text-[11px] text-muted">Aulas</span>{{ $examen['aulas'] }}</div>
                    </div>
                </div>

                @if ($examen['es_propio'] && ! $examen['finalizado'])
                    <div class="lg:shrink-0">
                        <x-ui.button variant="secondary" :href="route('examenes.editar')">Editar</x-ui.button>
                    </div>
                @endif
            </div>
        @empty
            <p class="rounded-base border border-default bg-neutral-primary-soft p-8 text-center text-sm text-body shadow-xs">
                No hay exámenes que coincidan.
            </p>
        @endforelse
    </div>
</div>
