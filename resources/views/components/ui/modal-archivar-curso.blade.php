{{--
    @file    modal-archivar-curso.blade.php
    @author  Equipo T1
    @created 2026-11-21
    @updated 2026-11-22

    @description
    Modal de confirmación de la pestaña «Archivar» de la página única
    Archivar/Restaurar cursos. Muestra el nombre del curso elegido y los
    botones Archivar / Cancelar. Cierra sin cambios con Cancelar, la X, Esc o
    un clic fuera. El componente se usa dentro de
    `livewire.cursos.archivar-restaurar-curso`, que aporta las acciones
    `archivar()` y `cerrarModal()`.
--}}

@props([
    'abierto' => false,
    'nombre' => '',
    'archivando' => false,
    'error' => '',
])

@if ($abierto)
    <div
        {{ $attributes->merge([
            'tabindex' => '-1',
            'role' => 'dialog',
            'aria-modal' => 'true',
            'aria-labelledby' => 'titulo-archivar-curso',
            'aria-describedby' => 'archivar-curso-nombre',
            'wire:click.self' => 'cerrarModal',
            'wire:keydown.escape.window' => 'cerrarModal',
            'class' => 'overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 flex justify-center items-center w-full md:inset-0 h-full max-h-full bg-overlay-modal/50',
        ]) }}
    >
        <div class="relative p-4 w-full max-w-md max-h-full">
            <div class="relative max-h-[calc(100vh-2rem)] overflow-y-auto bg-neutral-primary-soft border border-default rounded-base shadow-sm p-4 md:p-6">
                <div class="flex items-center justify-between border-b border-default pb-4 md:pb-5">
                    <h3 id="titulo-archivar-curso" class="text-lg font-medium text-heading">
                        Archivar curso
                    </h3>

                    <button
                        type="button"
                        wire:click="cerrarModal"
                        wire:loading.attr="disabled"
                        wire:target="archivar"
                        class="text-body bg-transparent hover:bg-neutral-tertiary hover:text-heading rounded-base text-sm w-11 h-11 ms-auto inline-flex justify-center items-center cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <svg class="w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 17.94 6M18 18 6.06 6"/></svg>
                        <span class="sr-only">Cerrar modal</span>
                    </button>
                </div>

                <div class="space-y-4 py-4">
                    <p class="text-sm text-body">¿Seguro que quieres archivar este curso?</p>
                    <p id="archivar-curso-nombre" class="text-sm font-semibold text-heading break-words">{{ $nombre }}</p>
                    <p class="text-sm text-body">El curso quedará en solo lectura y no aparecerá entre los cursos activos.</p>

                    @if ($error !== '')
                        <p role="alert" class="text-sm font-medium text-fg-danger-strong">{{ $error }}</p>
                    @endif
                </div>

                <div class="flex flex-col gap-3 border-t border-default pt-4 sm:flex-row sm:items-center">
                    <x-ui.button
                        id="archivar-curso-confirmar"
                        variant="default"
                        class="w-full min-h-11 justify-center disabled:opacity-50 disabled:cursor-not-allowed sm:w-auto"
                        wire:click="archivar"
                        wire:loading.attr="disabled"
                        wire:target="archivar"
                    >
                        {{ $archivando ? 'Archivando…' : 'Archivar' }}
                    </x-ui.button>

                    <x-ui.button-cancelar
                        class="w-full min-h-11 justify-center disabled:opacity-50 disabled:cursor-not-allowed sm:w-auto"
                        id="archivar-curso-cancelar"
                        autofocus
                        wire:click="cerrarModal"
                        wire:loading.attr="disabled"
                        wire:target="archivar"
                    >
                        Cancelar
                    </x-ui.button-cancelar>
                </div>
            </div>
        </div>
    </div>
@endif
