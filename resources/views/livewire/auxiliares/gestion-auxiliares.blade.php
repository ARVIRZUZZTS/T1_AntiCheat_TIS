{{--
    @file    gestion-auxiliares.blade.php
    @author  David E. Chavez T. <virzuzz12345@gmail.com>
    @created 2026-10-19
    @updated 2026-11-08
    @updated 2026-11-09

    @description
    Vista del componente Livewire GestionAuxiliares: listado de auxiliares
    asignados con filtros de estado y curso, botón de creación fuera de la
    tarjeta y modal para crear un nuevo auxiliar.

    @changelog
    - 2026-10-19  [ARVIRZUZZTS]  feat: creación inicial de la vista.
    - 2026-11-08  [David E. Chavez T.]  style: mover la creación a un modal
      con inputs verticales y botones Cancelar/Guardar.
    - 2026-11-09  [OchoaCesar]  style: el botón "Crear Auxiliar" sale de la
      tarjeta (queda encima, a la derecha) y se agregan los desplegables
      Estado y Curso en la cabecera de la tarjeta; por defecto ambos en
      "Todos" muestran todos los auxiliares.

    @see  app/Livewire/Auxiliares/GestionAuxiliares.php
--}}

@section('title', 'Auxiliares')

<div class="flex flex-col gap-6 px-[2vh] mt-[2vh]">
    @if ($mensajeExito !== '')
        <x-ui.alert type="success" title="Listo">{{ $mensajeExito }}</x-ui.alert>
    @endif
    @if ($mensajeError !== '')
        <x-ui.alert type="danger" title="Error">{{ $mensajeError }}</x-ui.alert>
    @endif

    <div class="flex justify-end">
        <x-ui.button type="button" variant="default" wire:click="abrirModalCrear">
            Crear Auxiliar
        </x-ui.button>
    </div>

    <div class="bg-neutral-primary-soft border border-default rounded-base shadow-xs p-6">
        <div class="flex flex-col gap-4 mb-4 sm:flex-row sm:items-end sm:justify-between">
            <h3 class="text-lg font-semibold text-heading">Auxiliares asignados</h3>
            <div class="flex flex-col gap-3 sm:flex-row">
                <div class="sm:w-40">
                    <x-ui.select label="Estado" name="filtroEstado" wire:model.live="filtroEstado"
                        :options="['todos' => 'Todos', 'Activo' => 'Activo', 'Baja' => 'Baja']"
                        :selected="$filtroEstado" />
                </div>
                <div class="sm:w-56">
                    <x-ui.select label="Curso" name="filtroCurso" wire:model.live="filtroCurso"
                        :options="['todos' => 'Todos'] + $cursos->pluck('nombre_curso', 'id_curso')->all()"
                        :selected="$filtroCurso" />
                </div>
            </div>
        </div>
        <x-ui.table compact :headers="['Auxiliar', 'Código SIS', 'Curso', 'Estado']" :rows="$auxiliares->map(fn($a) => [
            ['heading' => true, 'value' => $a->nombre_usuario.' '.$a->apellido],
            $a->cod_sis,
            $a->nombre_curso,
            $a->estado,
        ])->toArray()" />
    </div>

    @if ($mostrarModalCrear)
        <div
            tabindex="-1"
            aria-hidden="true"
            wire:click.self="cerrarModalCrear"
            wire:keydown.escape.window="cerrarModalCrear"
            class="overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 flex justify-center items-center w-full md:inset-0 h-full max-h-full bg-overlay-modal/50"
        >
            <div class="relative p-4 w-full max-w-md max-h-full">
                <div class="relative bg-neutral-primary-soft border border-default rounded-base shadow-sm p-4 md:p-6">
                    <div class="flex items-center justify-between border-b border-default pb-4 md:pb-5">
                        <h3 class="text-lg font-medium text-heading">Crear auxiliar</h3>
                        <button
                            type="button"
                            wire:click="cerrarModalCrear"
                            class="text-body bg-transparent hover:bg-neutral-tertiary hover:text-heading rounded-base text-sm w-9 h-9 ms-auto inline-flex justify-center items-center cursor-pointer"
                        >
                            <svg class="w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 17.94 6M18 18 6.06 6"/></svg>
                            <span class="sr-only">Cerrar modal</span>
                        </button>
                    </div>

                    <form wire:submit="crear" class="space-y-4 py-4">
                        <x-ui.input name="nombre" label="Nombre" wire:model="nombre" required minlength="2" maxlength="50" pattern="[\p{L}\s]+" />
                        <x-ui.input name="apellido" label="Apellido" wire:model="apellido" required minlength="2" maxlength="50" pattern="[\p{L}\s]+" />
                        <x-ui.input name="codSis" label="Código SIS" wire:model="codSis" required inputmode="numeric" maxlength="9" pattern="[0-9]{9}" />
                        <x-ui.input name="password" type="password" label="Contraseña" wire:model="password" minlength="8" maxlength="50" placeholder="Si se deja vacía, será 12345678" helper="Mínimo 8 caracteres. Si se deja vacía, se usará 12345678." />
                        <div>
                            <label class="block text-sm font-medium text-heading mb-1">Curso</label>
                            <select wire:model="idCurso" required class="w-full rounded-base border border-default px-3 py-2">
                                @foreach ($cursos as $curso)
                                    <option value="{{ $curso->id_curso }}">{{ $curso->nombre_curso }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="flex flex-col gap-3 border-t border-default pt-4 sm:flex-row sm:items-center">
                            <x-ui.button type="submit" variant="default" class="w-full justify-center sm:w-auto" wire:loading.attr="disabled" wire:target="crear">
                                <span wire:loading.remove wire:target="crear">Guardar</span>
                                <span wire:loading wire:target="crear">Guardando…</span>
                            </x-ui.button>
                            <x-ui.button-cancelar class="w-full justify-center sm:w-auto" wire:click="cerrarModalCrear" wire:loading.attr="disabled" wire:target="crear">
                                Cancelar
                            </x-ui.button-cancelar>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
