{{--
    @file    modal-deshabilitar.blade.php
    @author  Alisson D. Alvarado <alvaradoalissondalet@gmail.com>
    @created 2026-09-26
    @updated 2026-09-26

    @description
    Componente UI Blade reutilizable para el modal de deshabilitación de un
    estudiante: título, texto informativo, campo de motivo obligatorio con
    máximo de 150 caracteres y los botones Deshabilitar (rojo) / Cancelar
    (azul).
    @changelog
    - 2026-09-26  [Alisson D. Alvarado]  feat: creación inicial del componente UI.

    @see  resources/views/pages/materia-estudiantes.blade.php
    @see  resources/views/livewire/examenes/estudiantes-curso.blade.php
--}}

@props([
    'show' => false,
    'nombre' => '',
    'sis' => '',
    'motivo' => '',
    'error' => '',
    'wireModel' => null,
    'wireConfirm' => null,
    'wireClose' => null,
])

@php
    // Cada contexto (Livewire o Alpine) aporta su propio manejador de clic. El
    // `@if` va por FUERA del tag de componente: dentro del tag Blade no lo
    // compila y deja un archivo roto en storage/framework/views.
    $esLivewire = $wireModel !== null;
@endphp

@if ($show)
    <div tabindex="-1" aria-hidden="true" class="overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 flex justify-center items-center w-full md:inset-0 h-full max-h-full bg-overlay-modal/50">
        <div class="relative p-4 w-full max-w-md max-h-full">
            <div class="relative bg-neutral-primary-soft border border-default rounded-base shadow-sm p-4 md:p-6">
                <!-- Header -->
                <div class="flex items-center justify-between border-b border-default pb-4 md:pb-5">
                    <h3 class="text-lg font-medium text-heading">
                        Deshabilitar estudiante
                    </h3>
                    <button
                        type="button"
                        @if ($esLivewire) wire:click="{{ $wireClose }}" @else @click="cerrarModalDeshabilitar()" @endif
                        class="text-body bg-transparent hover:bg-neutral-tertiary hover:text-heading rounded-base text-sm w-9 h-9 ms-auto inline-flex justify-center items-center cursor-pointer"
                    >
                        <svg class="w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 17.94 6M18 18 6.06 6"/></svg>
                        <span class="sr-only">Cerrar modal</span>
                    </button>
                </div>

                <!-- Body -->
                <div class="space-y-4 py-4">
                    <p class="text-sm text-body">
                        El estudiante quedará deshabilitado para rendir los próximos exámenes.
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
                        <p x-show="nombre !== '' || sis !== ''" class="text-sm font-semibold text-heading">
                            Estudiante: <span x-text="nombre"></span> (SIS: <span x-text="sis"></span>)
                        </p>
                    @endif

                    <div>
                        <label for="motivoDeshabilitacion" class="block mb-2 text-sm font-medium text-heading">
                            Motivo <span class="text-fg-danger">*</span>
                        </label>
                        <textarea
                            id="motivoDeshabilitacion"
                            @if ($esLivewire) wire:model.defer="{{ $wireModel }}" @else x-model="motivo" @endif
                            rows="3"
                            maxlength="150"
                            placeholder="Ingrese el motivo (máximo 150 caracteres)..."
                            class="bg-neutral-secondary-medium border border-default-medium text-heading text-sm rounded-base focus:ring-brand focus:border-brand block w-full p-3 shadow-xs placeholder:text-body"
                        >{{ $motivo }}</textarea>
                        <div class="flex justify-between items-center mt-1">
                            <span class="text-xs text-muted">Máximo 150 caracteres.</span>
                        </div>
                        {{-- En Livewire el error llega por prop; en Alpine se lee
                             del scope padre (`errorMotivo`) para que la validación
                             se muestre sin round-trip. --}}
                        @if ($esLivewire)
                            @if ($error !== '')
                                <p class="text-xs text-fg-danger mt-1 font-medium">{{ $error }}</p>
                            @endif
                        @else
                            <p x-show="errorMotivo" x-text="errorMotivo" class="text-xs text-fg-danger mt-1 font-medium"></p>
                        @endif
                    </div>
                </div>

                <!-- Modal footer -->
                <div class="flex items-center space-x-3 border-t border-default pt-4">
                    @if ($esLivewire)
                        <x-ui.button variant="danger" wire:click="{{ $wireConfirm }}">Deshabilitar</x-ui.button>
                        <x-ui.button variant="default" wire:click="{{ $wireClose }}">Cancelar</x-ui.button>
                    @else
                        <x-ui.button variant="danger" @click="confirmarDeshabilitar()">Deshabilitar</x-ui.button>
                        <x-ui.button variant="default" @click="cerrarModalDeshabilitar()">Cancelar</x-ui.button>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endif
