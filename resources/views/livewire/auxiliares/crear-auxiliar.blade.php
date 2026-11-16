{{--
    @file    crear-auxiliar.blade.php
    @author  David E. Chavez T. <virzuzz12345@gmail.com>
    @created 2026-11-15

    @description
    Vista del alta de auxiliar como página (subopción "Crear auxiliar"). Es el
    mismo formulario que antes vivía en el modal de GestionAuxiliares (David E.
    Chavez T.), con sus campos, validaciones y el select de curso. "Guardar"
    crea y vuelve a la lista; "Cancelar" vuelve sin crear.

    @changelog
    - 2026-11-08  [David E. Chavez T.]  feat: formulario de alta (modal original).
    - 2026-11-15  [Diego Tejerina]  refactor: el formulario pasa de modal a
      página propia.

    @see  app/Livewire/Auxiliares/CrearAuxiliar.php
--}}

@section('title', 'Crear auxiliar')

<div class="px-[2vh] mt-[2vh]">
    <div class="mx-auto w-full max-w-md">
        @if ($mensajeError !== '')
            <x-ui.alert type="danger" title="Error" class="mb-4">{{ $mensajeError }}</x-ui.alert>
        @endif

        <div class="bg-neutral-primary-soft border border-default rounded-base shadow-xs p-4 md:p-6">
            @if ($cursos->isEmpty())
                <p class="text-sm text-body">No tienes cursos activos a los que asignar un auxiliar.</p>
            @else
                <form wire:submit="crear" class="space-y-4">
                    <x-ui.input name="nombre" label="Nombre" wire:model="nombre" required minlength="2" maxlength="50" pattern="[\p{L}\s]+" />
                    <x-ui.input name="apellido" label="Apellido" wire:model="apellido" required minlength="2" maxlength="50" pattern="[\p{L}\s]+" />
                    <x-ui.input name="codSis" label="Código SIS" wire:model="codSis" required inputmode="numeric" maxlength="9" pattern="[0-9]{9}" />
                    <x-ui.input name="password" type="password" label="Contraseña" wire:model="password" minlength="8" maxlength="50" placeholder="Si se deja vacía, será 12345678" helper="Mínimo 8 caracteres. Si se deja vacía, se usará 12345678." />

                    <x-ui.select
                        label="Curso"
                        name="idCurso"
                        wire:model="idCurso"
                        :options="$cursos->pluck('nombre_curso', 'id_curso')->all()"
                        :selected="$idCurso"
                        required
                    />

                    <div class="flex flex-col gap-3 border-t border-default pt-4 sm:flex-row sm:items-center">
                        <x-ui.button type="submit" variant="default" class="w-full justify-center sm:w-auto" wire:loading.attr="disabled" wire:target="crear">
                            <span wire:loading.remove wire:target="crear">Guardar</span>
                            <span wire:loading wire:target="crear">Guardando…</span>
                        </x-ui.button>
                        <x-ui.button variant="secondary" :href="route('auxiliares')" class="w-full justify-center sm:w-auto">
                            Cancelar
                        </x-ui.button>
                    </div>
                </form>
            @endif
        </div>
    </div>
</div>
