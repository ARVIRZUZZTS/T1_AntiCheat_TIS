{{--
    @file    desvincular-auxiliar.blade.php
    @author  Alisson D. Alvarado <alvaradoalissondalet@gmail.com>
    @created 2026-11-27
    @description Buscador de auxiliares y confirmación de desvinculación por curso o examen.
    @changelog
    - 2026-11-27  [Alisson D. Alvarado]  feat: buscar auxiliares y mostrar sus asignaciones.
    - 2026-11-27  [Alisson D. Alvarado]  fix: precisar los tipos de la confirmación.
--}}

@section('title', 'Desvincular auxiliar')

<div class="space-y-6 px-4 py-6 sm:px-6 lg:px-8">
    <div class="mx-auto max-w-3xl space-y-5">
        <div>
            <label for="buscar-auxiliar" class="mb-2 block text-sm font-medium text-heading">Buscar auxiliar</label>
            <input id="buscar-auxiliar" type="search" wire:model.live.debounce.300ms="busqueda"
                autocomplete="off" placeholder="Escribe el nombre o código SIS"
                class="w-full rounded-base border border-default bg-neutral-primary-soft p-2.5 text-sm text-heading">
            @if ($auxiliarSeleccionado === null && trim($busqueda) !== '')
                <div class="mt-2 rounded-base border border-default bg-neutral-primary-soft">
                    @forelse ($coincidencias as $coincidencia)
                        <button type="button" wire:key="opcion-{{ $coincidencia['id'] }}"
                            wire:click="seleccionar({{ $coincidencia['id'] }})"
                            class="block w-full p-3 text-left text-sm text-heading hover:bg-neutral-secondary-medium">
                            {{ $coincidencia['nombre'] }} @if ($coincidencia['sis'] !== '') · {{ $coincidencia['sis'] }} @endif
                        </button>
                    @empty
                        <p class="p-3 text-sm text-body">No se encontraron auxiliares.</p>
                    @endforelse
                </div>
            @endif
        </div>

        @if ($mensaje !== '')
            <p role="status" class="rounded-base border border-default bg-neutral-primary-soft p-4 text-sm text-body">
                {{ $mensaje }}
            </p>
        @endif

        @if ($auxiliar)
            <h2 class="text-lg font-semibold text-heading">Asignaciones de {{ $auxiliar['nombre'] }}</h2>
            <h3 class="font-semibold text-heading">Cursos</h3>
            @forelse ($auxiliar['cursos'] as $curso)
                <div wire:key="curso-{{ $curso['id'] }}"
                    class="flex flex-wrap items-center justify-between gap-3 rounded-base border border-default bg-neutral-primary-soft p-4">
                    <span class="text-sm text-heading">{{ $curso['nombre'] }}</span>
                    <x-ui.button type="button" wire:click="confirmar('curso', {{ $curso['id'] }})">Desvincular</x-ui.button>
                </div>
            @empty
                <p class="text-sm text-body">No tiene cursos activos asignados.</p>
            @endforelse

            <h3 class="font-semibold text-heading">Exámenes individuales</h3>
            @forelse ($auxiliar['examenes'] as $examen)
                <div wire:key="examen-{{ $examen['id'] }}"
                    class="flex flex-wrap items-center justify-between gap-3 rounded-base border border-default bg-neutral-primary-soft p-4">
                    <span class="text-sm text-heading">{{ $examen['nombre'] }}</span>
                    <x-ui.button type="button" wire:click="confirmar('examen', {{ $examen['id'] }})">Desvincular</x-ui.button>
                </div>
            @empty
                <p class="text-sm text-body">No tiene exámenes individuales asignados.</p>
            @endforelse
        @endif
    </div>

    {{-- La confirmación solo usa datos de asignaciones y auxiliares válidos. --}}
    @if (is_array($pendiente) && is_array($auxiliar))
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
            role="dialog" aria-modal="true" aria-labelledby="titulo-desvincular">
            <div class="w-full max-w-md space-y-4 rounded-base bg-neutral-primary-soft p-6 shadow-lg">
                <h2 id="titulo-desvincular" class="text-lg font-semibold text-heading">Desvincular auxiliar</h2>
                <p class="text-sm text-body">
                    ¿Quieres desvincular a {{ $auxiliar['nombre'] }} de {{ $pendiente['nombre'] }}?
                    @if ($tipoPendiente === 'curso')
                        También se desvinculará de los exámenes individuales relacionados con el curso.
                    @endif
                </p>
                <div class="flex justify-end gap-3">
                    <x-ui.button-cancelar wire:click="cancelar">Cancelar</x-ui.button-cancelar>
                    <x-ui.button type="button" wire:click="desvincular" wire:loading.attr="disabled">Aceptar</x-ui.button>
                </div>
            </div>
        </div>
    @endif
</div>
