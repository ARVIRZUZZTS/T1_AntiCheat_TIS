{{--
    @file    modal-habilitar.blade.php
    @author  Diego Tejerina <josediegotejerinamolina@gmail.com>
    @created 2026-09-26
    @updated 2026-09-26

    @description
    Componente UI Blade reutilizable para el modal de confirmación de
    habilitación de un estudiante (issue #25): título, texto informativo y
    los botones Habilitar / Cancelar (ambos azules, sin campo de motivo — a
    diferencia de x-ui.modal-deshabilitar, que sí lo requiere). Sigue el
    mismo patrón dual Livewire/Alpine que ese componente, y además cierra
    con clic fuera del modal y con Esc (criterio 3 de la issue #25).

    @changelog
    - 2026-09-26  [Diego Tejerina]  feat: creación inicial del componente UI.

    @see  resources/views/pages/materia-estudiantes.blade.php
    @see  resources/views/livewire/examenes/estudiantes-curso.blade.php
    @see  resources/views/components/ui/modal-deshabilitar.blade.php
--}}

@props([
    'show' => false,
    'nombre' => '',
    'sis' => '',
    'error' => '',
    'wireConfirm' => null,
    'wireClose' => null,
])

@php
    // Igual que en modal-deshabilitar: el `@if` va por FUERA del tag de
    // componente, no adentro, porque dentro no compila y deja un archivo
    // roto en storage/framework/views.
    $esLivewire = $wireConfirm !== null;
@endphp

@if ($show)
    <div
        tabindex="-1"
        aria-hidden="true"
        @if ($esLivewire)
            wire:click.self="{{ $wireClose }}"
            wire:keydown.escape.window="{{ $wireClose }}"
        @else
            @click.self="cerrarModalHabilitar()"
            @keydown.escape.window="cerrarModalHabilitar()"
        @endif
        class="overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 flex justify-center items-center w-full md:inset-0 h-full max-h-full bg-overlay-modal/50"
    >
        <div class="relative p-4 w-full max-w-md max-h-full">
            <div class="relative bg-neutral-primary-soft border border-default rounded-base shadow-sm p-4 md:p-6">
                <!-- Header -->
                <div class="flex items-center justify-between border-b border-default pb-4 md:pb-5">
                    <h3 class="text-lg font-medium text-heading">
                        Habilitar Estudiante
                    </h3>
                    <button
                        type="button"
                        @if ($esLivewire) wire:click="{{ $wireClose }}" @else @click="cerrarModalHabilitar()" @endif
                        class="text-body bg-transparent hover:bg-neutral-tertiary hover:text-heading rounded-base text-sm w-9 h-9 ms-auto inline-flex justify-center items-center cursor-pointer"
                    >
                        <svg class="w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 17.94 6M18 18 6.06 6"/></svg>
                        <span class="sr-only">Cerrar modal</span>
                    </button>
                </div>

                <!-- Body -->
                <div class="space-y-4 py-4">
                    <p class="text-sm text-body">
                        El estudiante estará habilitado para rendir exámenes del curso.
                    </p>

                    @if ($esLivewire)
                        @if ($nombre !== '')
                            <p class="text-sm font-semibold text-heading">
                                Estudiante: {{ $nombre }} (SIS: {{ $sis }})
                            </p>
                        @endif
                    @else
                        {{-- En Alpine el nombre y el SIS llegan por el scope
                             padre, asi que se leen de forma reactiva. --}}
                        <p x-show="nombreHabilitar !== '' || sisHabilitar !== ''" class="text-sm font-semibold text-heading">
                            Estudiante: <span x-text="nombreHabilitar"></span> (SIS: <span x-text="sisHabilitar"></span>)
                        </p>
                    @endif

                    @if ($esLivewire)
                        @if ($error !== '')
                            <p class="text-xs text-fg-danger font-medium">{{ $error }}</p>
                        @endif
                    @else
                        <p x-show="errorHabilitar" x-text="errorHabilitar" class="text-xs text-fg-danger font-medium"></p>
                    @endif
                </div>

                <!-- Modal footer -->
                <div class="flex items-center space-x-3 border-t border-default pt-4">
                    @if ($esLivewire)
                        <x-ui.button
                            variant="default"
                            wire:click="{{ $wireConfirm }}"
                            wire:loading.attr="disabled"
                            wire:target="{{ $wireConfirm }}"
                        >
                            <span wire:loading.remove wire:target="{{ $wireConfirm }}">Habilitar</span>
                            <span wire:loading wire:target="{{ $wireConfirm }}">Habilitando…</span>
                        </x-ui.button>
                        <x-ui.button
                            variant="default"
                            wire:click="{{ $wireClose }}"
                            wire:loading.attr="disabled"
                            wire:target="{{ $wireConfirm }}"
                        >Cancelar</x-ui.button>
                    @else
                        <x-ui.button variant="default" @click="confirmarHabilitar()">Habilitar</x-ui.button>
                        <x-ui.button variant="default" @click="cerrarModalHabilitar()">Cancelar</x-ui.button>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endif
