{{--
    @file    crear-auxiliar.blade.php
    @author  Diego Tejerina <josediegotejerinamolina@gmail.com>
    @created 2026-11-15
    @updated 2026-11-27
    @updated 2026-11-28

    @description
    Vista del alta de auxiliar como página (Auxiliares › Crear auxiliar). Pide
    nombre, apellido, tipo de auxiliar (UMSS con código SIS / externo con carnet
    opcional) y correo, y muestra en vivo el usuario de acceso que se generará.
    "Crear auxiliar" abre el modal "Confirmar creación"; "Cancelar" vuelve a la
    lista. El permiso de coordinador ya no se da aquí (irá en las asignaciones).

    @changelog
    - 2026-11-08  [David E. Chavez T.]  feat: formulario de alta (modal original).
    - 2026-11-15  [Diego Tejerina]  refactor: el formulario pasa a página propia.
    - 2026-11-27  [Diego Tejerina]  feat: tipo de auxiliar, carnet, correo,
      permiso de coordinador, vista previa del usuario y modal de confirmación.
    - 2026-11-28  [Valery D. Ortuno P]  refactor: sin los parentesis de "Auxiliar
      externo" y "Carnet de identidad"; sale el permiso "Puede crear y asignar
      auxiliares"; el usuario de acceso se ve en vivo al escribir nombre y
      apellido, sin el texto "Se genera automaticamente...". El carnet de
      identidad es obligatorio. Textos sin acentos.

    @see  app/Livewire/Auxiliares/CrearAuxiliar.php
--}}

@section('title', 'Crear auxiliar')

<div class="px-[2vh] mt-[2vh]">
    <div class="mx-auto w-full max-w-md">
        <div class="bg-neutral-primary-soft border border-default rounded-base shadow-xs p-4 md:p-6">
            <h1 class="mb-4 text-lg font-semibold text-heading">Crear auxiliar</h1>

            <form wire:submit="revisar" class="space-y-4">
                <x-ui.input name="nombre" label="Nombre" wire:model.live.debounce.400ms="nombre" required
                            maxlength="50" :error="$errors->first('nombre')" />
                <x-ui.input name="apellido" label="Apellido" wire:model.live.debounce.400ms="apellido" required
                            maxlength="50" :error="$errors->first('apellido')" />

                {{-- Tipo de auxiliar: radios enlazados en vivo para ocultar/mostrar
                     el código SIS al instante. --}}
                <fieldset>
                    <legend class="mb-2 text-sm font-medium text-heading">Tipo de auxiliar</legend>
                    <div class="flex items-center mb-2">
                        <input id="tipo-umss" type="radio" value="UMSS" wire:model.live="tipoAuxiliar"
                               class="w-4 h-4 text-brand bg-neutral-secondary-medium border border-default-medium focus:ring-2 focus:ring-brand-subtle" />
                        <label for="tipo-umss" class="ms-2 text-sm font-medium text-heading select-none">Auxiliar UMSS</label>
                    </div>
                    <div class="flex items-center">
                        <input id="tipo-externo" type="radio" value="externo" wire:model.live="tipoAuxiliar"
                               class="w-4 h-4 text-brand bg-neutral-secondary-medium border border-default-medium focus:ring-2 focus:ring-brand-subtle" />
                        <label for="tipo-externo" class="ms-2 text-sm font-medium text-heading select-none">Auxiliar externo</label>
                    </div>
                    @if ($tipoAuxiliar === 'externo')
                        <p class="mt-1 text-sm text-muted">Para personas que no son de la UMSS.</p>
                    @endif
                </fieldset>

                @if ($tipoAuxiliar === 'UMSS')
                    <x-ui.input name="codSis" label="Codigo SIS" wire:model="codSis" required
                                inputmode="numeric" maxlength="9" :error="$errors->first('codSis')" />
                @endif

                <x-ui.input name="carnet" label="Carnet de identidad" wire:model="carnet" required
                            inputmode="numeric" maxlength="8"
                            helper="Entre 6 y 8 digitos."
                            :error="$errors->first('carnet')" />

                <x-ui.input name="correo" type="email" label="Correo electronico" wire:model="correo" required
                            maxlength="100" helper="Alli recibira su usuario, contraseña y el enlace para ingresar."
                            :error="$errors->first('correo')" />

                {{-- Vista previa en vivo: el mismo usuario que se va a crear
                     (aux.nombre.apellido, con numero si ya existe). --}}
                <div class="rounded-base border border-dashed border-default px-3 py-2 text-sm text-body">
                    Usuario de acceso:
                    <span class="font-medium text-heading">{{ $this->usuarioVista !== '' ? $this->usuarioVista : '—' }}</span>
                </div>

                <div class="flex flex-col gap-3 border-t border-default pt-4 sm:flex-row sm:items-center">
                    <x-ui.button type="submit" variant="default" class="w-full justify-center sm:w-auto"
                                 wire:loading.attr="disabled" wire:target="revisar">
                        <span wire:loading.remove wire:target="revisar">Crear auxiliar</span>
                        <span wire:loading wire:target="revisar">Revisando…</span>
                    </x-ui.button>
                    <x-ui.button variant="secondary" :href="route('auxiliares')" class="w-full justify-center sm:w-auto">
                        Cancelar
                    </x-ui.button>
                </div>
            </form>
        </div>
    </div>

    @if ($confirmando)
        <div
            x-data
            tabindex="-1"
            role="dialog"
            aria-modal="true"
            aria-labelledby="titulo-confirmar-auxiliar"
            @keydown.escape.window="$wire.cancelarConfirmacion()"
            class="overflow-y-auto overflow-x-hidden fixed inset-0 z-50 flex justify-center items-center w-full h-full max-h-full bg-overlay-modal/50"
        >
            <div class="relative p-4 w-full max-w-md max-h-full">
                <div class="relative bg-neutral-primary-soft border border-default rounded-base shadow-sm p-6">
                    <h2 id="titulo-confirmar-auxiliar" class="text-lg font-semibold text-heading">Confirmar creacion</h2>

                    <dl class="mt-4 space-y-1 text-sm text-body">
                        <div class="flex gap-2">
                            <dt class="font-medium text-heading">Nombre completo:</dt>
                            <dd>{{ trim($nombre.' '.$apellido) }}</dd>
                        </div>
                        <div class="flex gap-2">
                            <dt class="font-medium text-heading">Usuario:</dt>
                            <dd>{{ $usuarioPreview }}</dd>
                        </div>
                        <div class="flex gap-2">
                            <dt class="font-medium text-heading">Correo:</dt>
                            <dd>{{ trim($correo) }}</dd>
                        </div>
                    </dl>

                    <p class="mt-4 text-sm font-medium text-heading">Podra ver:</p>
                    <ul class="mt-1 list-disc ps-5 text-sm text-body space-y-0.5">
                        <li>Cursos</li>
                        <li>Examenes › Ver examenes</li>
                        <li>Monitor en vivo › Proximos examenes</li>
                        <li>Ingreso y Monitor</li>
                        <li>Notificaciones</li>
                    </ul>

                    <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:justify-end">
                        <x-ui.button variant="secondary" class="w-full justify-center sm:w-auto"
                                     wire:click="cancelarConfirmacion">
                            Cancelar
                        </x-ui.button>
                        <x-ui.button variant="default" class="w-full justify-center sm:w-auto"
                                     wire:click="confirmar" wire:loading.attr="disabled" wire:target="confirmar">
                            <span wire:loading.remove wire:target="confirmar">Confirmar</span>
                            <span wire:loading wire:target="confirmar">Creando…</span>
                        </x-ui.button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
