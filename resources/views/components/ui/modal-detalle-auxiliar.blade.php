{{--
    @file    modal-detalle-auxiliar.blade.php
    @author  OchoaCesar
    @created 2026-11-24
    @updated 2026-11-24

    @description
    Modal de detalle de un auxiliar de la lista «Auxiliares»: muestra todos
    los cursos a los que el auxiliar pertenece (asignaciones activas en los
    cursos del docente). Cierra con la X, Esc o un clic fuera. El componente
    se usa dentro de `livewire.auxiliares.gestion-auxiliares`, que aporta los
    cursos y la acción `cerrarDetalle()`.

    @changelog
    - 2026-11-24  [OchoaCesar]  feat: creación inicial del componente.
    - 2026-11-24  [OchoaCesar]  style: el modal muestra solo la lista de
      cursos (el nombre y el código SIS ya están en la tabla).
--}}

@props([
    'abierto' => false,
    'cursos' => [],
])

@if ($abierto)
    <div
        {{ $attributes->merge([
            'tabindex' => '-1',
            'role' => 'dialog',
            'aria-modal' => 'true',
            'aria-labelledby' => 'titulo-detalle-auxiliar',
            'wire:click.self' => 'cerrarDetalle',
            'wire:keydown.escape.window' => 'cerrarDetalle',
            'class' => 'overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 flex justify-center items-center w-full md:inset-0 h-full max-h-full bg-overlay-modal/50',
        ]) }}
    >
        <div class="relative p-4 w-full max-w-md max-h-full">
            <div class="relative max-h-[calc(100vh-2rem)] overflow-y-auto bg-neutral-primary-soft border border-default rounded-base shadow-sm p-4 md:p-6">
                <div class="flex items-center justify-between border-b border-default pb-4 md:pb-5">
                    <h3 id="titulo-detalle-auxiliar" class="text-lg font-medium text-heading">
                        Detalle del auxiliar
                    </h3>

                    <button
                        type="button"
                        wire:click="cerrarDetalle"
                        class="text-body bg-transparent hover:bg-neutral-tertiary hover:text-heading rounded-base text-sm w-11 h-11 ms-auto inline-flex justify-center items-center cursor-pointer"
                    >
                        <svg class="w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 17.94 6M18 18 6.06 6"/></svg>
                        <span class="sr-only">Cerrar modal</span>
                    </button>
                </div>

                <div class="space-y-4 py-4">
                    <p class="text-sm font-bold text-heading">Cursos asignados</p>

                    @if (count($cursos) > 0)
                        <ul class="space-y-2">
                            @foreach ($cursos as $curso)
                                <li class="rounded-base border border-default bg-neutral-secondary-soft px-3 py-2 text-sm text-body">
                                    {{ $curso }}
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-sm text-body-subtle">Este auxiliar no tiene cursos asignados.</p>
                    @endif
                </div>

                <div class="flex justify-end border-t border-default pt-4">
                    <x-ui.button-cancelar
                        class="w-full min-h-11 justify-center sm:w-auto"
                        wire:click="cerrarDetalle"
                    >
                        Cerrar
                    </x-ui.button-cancelar>
                </div>
            </div>
        </div>
    </div>
@endif
