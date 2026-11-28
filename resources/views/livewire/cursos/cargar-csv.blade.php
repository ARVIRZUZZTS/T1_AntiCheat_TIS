{{--
    @file    cargar-csv.blade.php
    @author  Equipo T1
    @created 2026-11-21
    @updated 2026-11-24

    @description
    Vista de la subopción «Cargar CSV» (HU 25): selector de curso (solo cursos
    activos gestionables), zona de carga con arrastrar y soltar visible solo con
    un curso elegido, y modal de confirmación de adición cuando el curso ya
    tiene estudiantes (informa cuántos nuevos se añadirán y cuántos repetidos
    se ignorarán; si no hay nuevos, avisa que no habrá cambios). La lógica
    vive en App\Livewire\Cursos\CargarCsv.
--}}

<div class="px-4 py-4 sm:px-6 sm:py-6 lg:px-8">
    <div class="mx-auto max-w-2xl space-y-6">
        {{-- Paso 1: selector de curso. --}}
        <div class="rounded-base border border-default bg-neutral-primary-soft p-4 shadow-xs sm:p-6">
            @if (empty($cursos))
                <p class="text-sm text-body">No tienes cursos activos para cargar estudiantes.</p>
            @else
                <x-ui.select
                    label="Curso"
                    name="cursoId"
                    wire:model.live="cursoId"
                    :options="$cursos"
                    :selected="$cursoId"
                    placeholder="Selecciona un curso"
                    :error="$errors->first('cursoId')"
                />
            @endif
        </div>

        @if ($mensajeExito !== '')
            <x-ui.alert type="success" title="Carga completada" class="mb-0!">
                {{ $mensajeExito }}
            </x-ui.alert>
        @endif

        {{-- Paso 2: zona de carga (solo con un curso elegido). --}}
        @if ($cursoId && ! empty($cursos))
            <form wire:submit="cargar" enctype="multipart/form-data"
                  class="rounded-base border border-default bg-neutral-primary-soft p-4 shadow-xs sm:p-6">
                <h2 class="mb-1 text-base font-semibold text-heading">Archivo de estudiantes</h2>
                <p class="mb-4 text-sm text-body">
                    Formato esperado: <code class="text-xs">#, Estudiante, Apellidos, Nombres, Carrera…</code>
                    («Estudiante» es el código SIS de 9 dígitos). Se admiten <strong>.csv</strong>, <strong>.xlsx</strong> y <strong>.xls</strong>.
                </p>

                <div
                    x-data="{ arrastrando: false }"
                    x-on:dragover.prevent="arrastrando = true"
                    x-on:dragleave.prevent="arrastrando = false"
                    x-on:drop.prevent="arrastrando = false; $refs.archivo.files = $event.dataTransfer.files; $refs.archivo.dispatchEvent(new Event('change'))"
                    x-on:click="$refs.archivo.click()"
                    role="button"
                    tabindex="0"
                    x-on:keydown.enter.prevent="$refs.archivo.click()"
                    x-on:keydown.space.prevent="$refs.archivo.click()"
                    :class="arrastrando ? 'border-brand bg-brand-softer' : 'border-default-medium'"
                    class="flex cursor-pointer flex-col items-center justify-center gap-2 rounded-base border-2 border-dashed px-4 py-8 text-center transition-colors focus:outline-none focus:ring-2 focus:ring-brand-medium"
                >
                    <svg class="size-8 text-body-subtle" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5" />
                    </svg>
                    <p class="text-sm text-body">
                        Arrastra el archivo aquí o <span class="font-medium text-fg-brand">haz clic para seleccionarlo</span>.
                    </p>

                    <input id="archivo" type="file" x-ref="archivo" wire:model="archivo"
                           accept=".xlsx,.xls,.csv" class="sr-only">
                </div>

                <div wire:loading wire:target="archivo" class="mt-2 text-sm text-body">Subiendo archivo…</div>

                @if ($archivo)
                    <p class="mt-3 flex items-center gap-2 text-sm text-heading">
                        <svg class="size-4 text-fg-success-strong" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        {{ $archivo->getClientOriginalName() }}
                    </p>
                @endif

                @error('archivo')
                    <p class="mt-2 text-sm text-fg-danger-strong">{{ $message }}</p>
                @enderror

                @if ($mensajeError !== '')
                    <p role="alert" class="mt-2 text-sm text-fg-danger-strong">{{ $mensajeError }}</p>
                @endif

                <div class="mt-5 flex flex-col gap-3 sm:flex-row">
                    <x-ui.button type="submit" class="w-full justify-center sm:w-auto"
                                 wire:loading.attr="disabled" wire:target="cargar,archivo">
                        <span wire:loading.remove wire:target="cargar">Cargar</span>
                        <span wire:loading wire:target="cargar">Cargando…</span>
                    </x-ui.button>
                    <x-ui.button type="button" variant="secondary" class="w-full justify-center sm:w-auto"
                                 wire:click="cancelar">Cancelar</x-ui.button>
                </div>
            </form>
        @endif
    </div>

    {{-- Modal de confirmación de adición de estudiantes nuevos. --}}
    @if ($mostrarModalAgregados)
        <div
            tabindex="-1" role="dialog" aria-modal="true" aria-labelledby="titulo-agregados"
            wire:click.self="cancelarAgregacion"
            wire:keydown.escape.window="cancelarAgregacion"
            class="fixed inset-0 z-50 flex h-full max-h-full w-full items-center justify-center overflow-y-auto overflow-x-hidden bg-overlay-modal/50"
        >
            <div class="relative w-full max-w-md p-4">
                <div class="relative max-h-[calc(100vh-2rem)] overflow-y-auto rounded-base border border-default bg-neutral-primary-soft p-4 shadow-sm md:p-6">
                    <div class="flex items-center justify-between border-b border-default pb-4 md:pb-5">
                        <h3 id="titulo-agregados" class="text-lg font-medium text-heading">Añadir estudiantes</h3>
                        <button type="button" wire:click="cancelarAgregacion"
                                class="inline-flex h-11 w-11 items-center justify-center rounded-base bg-transparent text-body hover:bg-neutral-tertiary hover:text-heading">
                            <svg class="h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 17.94 6M18 18 6.06 6"/></svg>
                            <span class="sr-only">Cerrar modal</span>
                        </button>
                    </div>

                    @if ($nuevosConteo > 0)
                        <div class="space-y-3 py-4">
                            <p class="text-sm text-body">
                                Este curso ya tiene estudiantes registrados. El archivo trae
                                <strong class="text-heading">{{ $nuevosConteo }}</strong> estudiante(s) nuevo(s)
                                que se añadirán; los
                                <strong class="text-heading">{{ $repetidosConteo }}</strong> repetido(s)
                                se ignorarán.
                            </p>
                            <p class="text-sm text-body">¿Deseas continuar?</p>
                        </div>

                        <div class="flex flex-col gap-3 border-t border-default pt-4 sm:flex-row sm:items-center">
                            <x-ui.button type="button"
                                         class="w-full min-h-11 justify-center sm:w-auto"
                                         wire:click="confirmarAgregacion"
                                         wire:loading.attr="disabled" wire:target="confirmarAgregacion">
                                <span wire:loading.remove wire:target="confirmarAgregacion">Añadir {{ $nuevosConteo }} estudiante(s)</span>
                                <span wire:loading wire:target="confirmarAgregacion">Añadiendo…</span>
                            </x-ui.button>
                            <x-ui.button-cancelar type="button"
                                         class="w-full min-h-11 justify-center sm:w-auto"
                                         wire:click="cancelarAgregacion"
                                         wire:loading.attr="disabled" wire:target="confirmarAgregacion">
                                Cancelar
                            </x-ui.button-cancelar>
                        </div>
                    @else
                        <div class="space-y-3 py-4">
                            <p class="text-sm text-body">
                                El archivo no trae estudiantes nuevos: todos ya están en el curso.
                                <strong class="text-heading">No se realizará ningún cambio.</strong>
                            </p>
                        </div>

                        <div class="flex flex-col gap-3 border-t border-default pt-4 sm:flex-row sm:items-center">
                            <x-ui.button type="button" variant="secondary"
                                         class="w-full min-h-11 justify-center sm:w-auto"
                                         wire:click="cancelarAgregacion">
                                Cerrar
                            </x-ui.button>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>
