{{--
    @file    validar-registros.blade.php
    @author  Valery D. Ortuno P <valerydariana98@gmail.com>
    @created 2026-11-08

    @description
    Vista del componente ValidarRegistros (HU 26): cards de los docentes
    pendientes de validación con su contador "X de 2 verificaciones", los
    docentes que ya aprobaron y los botones Aprobar / Desaprobar. Aprobar abre
    una confirmación; Desaprobar pide un motivo obligatorio (hasta 300).

    Una columna en móvil, dos en tablet y tres en pantallas grandes; los
    botones ocupan todo el ancho de la card en móvil. El margen exterior es el
    mismo de las demás pantallas (el layout no pone ninguno).

    @changelog
    - 2026-11-08  [Valery D. Ortuno P]  feat: creación inicial (HU 26).

    @see  App\Livewire\Docentes\ValidarRegistros
--}}

@php
    $requeridas = \App\Services\Docente\ValidarRegistroDocenteService::APROBACIONES_REQUERIDAS;
    $motivoMaximo = \App\Services\Docente\ValidarRegistroDocenteService::MOTIVO_MAXIMO;
    $pendientes = $this->solicitudes->count();
@endphp

<div
    x-data="{
        modal: { abierta: false, tipo: '', id: null, nombre: '', sis: '', motivo: '', error: '', enviando: false },
        abrir(tipo, solicitud) {
            this.modal = { abierta: true, tipo: tipo, id: solicitud.id, nombre: solicitud.nombre, sis: solicitud.sis, motivo: '', error: '', enviando: false };
        },
        cerrar() {
            if (! this.modal.enviando) this.modal.abierta = false;
        },
        async aprobar() {
            this.modal.enviando = true;
            await $wire.aprobar(this.modal.id);
            this.modal.enviando = false;
            this.modal.abierta = false;
        },
        async desaprobar() {
            if (this.modal.motivo.trim() === '') { this.modal.error = 'El motivo es obligatorio.'; return; }
            this.modal.enviando = true;
            const error = await $wire.desaprobar(this.modal.id, this.modal.motivo);
            this.modal.enviando = false;
            if (error) { this.modal.error = error; return; }
            this.modal.abierta = false;
        }
    }"
    @keydown.escape.window="cerrar()"
    class="space-y-4 px-4 pt-[2vh] pb-8 sm:px-6 lg:px-[2vh]"
>
    <div class="flex flex-wrap items-center justify-between gap-2">
        <h3 class="text-lg font-semibold text-heading">Solicitudes de registro de docentes</h3>
        @if ($pendientes > 0)
            <span class="text-sm text-body">{{ $pendientes }} {{ $pendientes === 1 ? 'pendiente' : 'pendientes' }}</span>
        @endif
    </div>

    @if ($mensaje !== '')
        <x-ui.alert :type="$tipoMensaje" class="items-start">
            <span class="flex-1">{{ $mensaje }}</span>
            <button type="button" wire:click="cerrarMensaje" class="ms-3 shrink-0 font-semibold cursor-pointer" aria-label="Cerrar aviso">✕</button>
        </x-ui.alert>
    @endif

    @if ($pendientes === 0)
        <div class="rounded-base border border-dashed border-default-medium bg-neutral-primary-soft px-4 py-10 text-center">
            <p class="text-sm text-body">No hay docentes pendientes de validacion</p>
        </div>
    @else
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
            @foreach ($this->solicitudes as $solicitud)
                @php $cantidad = count($solicitud['aprobaciones']); @endphp
                <article wire:key="solicitud-{{ $solicitud['id'] }}"
                         class="flex flex-col rounded-base border border-default bg-neutral-primary-soft p-4 shadow-xs sm:p-5">
                    <div class="mb-3 flex items-center gap-3">
                        <x-ui.avatar :name="$solicitud['nombre']" size="md" />
                        <div class="min-w-0 flex-1">
                            <p class="truncate font-medium text-heading" title="{{ $solicitud['nombre'] }}">{{ $solicitud['nombre'] }}</p>
                            <x-ui.badge type="status-pendiente">Pendiente</x-ui.badge>
                        </div>
                    </div>

                    <dl class="mb-3 space-y-1 text-sm">
                        <div class="flex gap-2">
                            <dt class="shrink-0 text-body">Correo:</dt>
                            <dd class="min-w-0 break-all text-heading">{{ $solicitud['correo'] !== '' ? $solicitud['correo'] : '-' }}</dd>
                        </div>
                        <div class="flex gap-2">
                            <dt class="shrink-0 text-body">Codigo SIS:</dt>
                            <dd class="font-mono text-heading">{{ $solicitud['sis'] }}</dd>
                        </div>
                        <div class="flex gap-2">
                            <dt class="shrink-0 text-body">Cuenta creada:</dt>
                            <dd class="text-heading">{{ $solicitud['fecha'] }}</dd>
                        </div>
                    </dl>

                    <div class="mb-4 rounded-base bg-neutral-secondary-soft p-3">
                        <div class="mb-2 flex items-center justify-between gap-2">
                            <p class="text-sm font-medium text-heading">{{ $cantidad }} de {{ $requeridas }} verificaciones</p>
                            <div class="flex gap-1" aria-hidden="true">
                                @for ($i = 0; $i < $requeridas; $i++)
                                    <span @class([
                                        'h-1.5 w-6 rounded-full',
                                        'bg-success' => $i < $cantidad,
                                        'bg-neutral-tertiary-medium' => $i >= $cantidad,
                                    ])></span>
                                @endfor
                            </div>
                        </div>
                        <p class="mb-1 text-xs text-body">Aprobaron:</p>
                        @if ($cantidad === 0)
                            <p class="text-xs text-body italic">Aun no hay aprobaciones</p>
                        @else
                            <ul class="flex flex-wrap gap-1">
                                @foreach ($solicitud['aprobaciones'] as $aprobador)
                                    <li class="rounded-full bg-success-soft px-2 py-0.5 text-xs text-fg-success-strong">✓ {{ $aprobador }}</li>
                                @endforeach
                            </ul>
                        @endif
                    </div>

                    <div class="mt-auto flex flex-col gap-2 sm:flex-row">
                        <x-ui.button type="button" class="w-full sm:flex-1 cursor-pointer" :disabled="$solicitud['ya_aprobo']"
                                     @click="abrir('aprobar', {{ Js::from(['id' => $solicitud['id'], 'nombre' => $solicitud['nombre'], 'sis' => $solicitud['sis']]) }})">
                            {{ $solicitud['ya_aprobo'] ? 'Ya aprobaste' : 'Aprobar' }}
                        </x-ui.button>
                        <x-ui.button variant="danger" type="button" class="w-full sm:flex-1 cursor-pointer" :disabled="$solicitud['ya_aprobo']"
                                     @click="abrir('desaprobar', {{ Js::from(['id' => $solicitud['id'], 'nombre' => $solicitud['nombre'], 'sis' => $solicitud['sis']]) }})">
                            Desaprobar
                        </x-ui.button>
                    </div>
                </article>
            @endforeach
        </div>
    @endif

    {{-- Confirmar la aprobación --}}
    <div x-show="modal.abierta && modal.tipo === 'aprobar'" x-cloak role="dialog" aria-modal="true" aria-labelledby="titulo-aprobar"
         @click.self="cerrar()"
         class="fixed inset-0 z-50 flex items-center justify-center bg-overlay-modal/50 p-4">
        <div class="w-full max-w-md rounded-base border border-default bg-neutral-primary-soft p-4 shadow-sm md:p-6">
            <div class="mb-4 flex items-center justify-between border-b border-default pb-3">
                <h3 id="titulo-aprobar" class="text-lg font-medium text-heading">Aprobar registro</h3>
                <button type="button" class="text-body cursor-pointer" @click="cerrar()" aria-label="Cerrar">✕</button>
            </div>
            <p class="mb-2 text-sm text-body">
                La cuenta queda habilitada cuando {{ $requeridas }} docentes aprueban su registro.
            </p>
            <p class="mb-4 text-sm font-semibold text-heading wrap-break-word">
                <span x-text="modal.nombre"></span> (SIS: <span x-text="modal.sis"></span>)
            </p>
            <div class="flex flex-col gap-3 sm:flex-row">
                <x-ui.button type="button" class="w-full sm:w-auto cursor-pointer" x-bind:disabled="modal.enviando" @click="aprobar()">
                    <span x-text="modal.enviando ? 'Aprobando...' : 'Aprobar'"></span>
                </x-ui.button>
                <x-ui.button-cancelar type="button" class="w-full sm:w-auto" @click="cerrar()">Cancelar</x-ui.button-cancelar>
            </div>
        </div>
    </div>

    {{-- Desaprobar con motivo --}}
    <div x-show="modal.abierta && modal.tipo === 'desaprobar'" x-cloak role="dialog" aria-modal="true" aria-labelledby="titulo-desaprobar"
         @click.self="cerrar()"
         class="fixed inset-0 z-50 flex items-center justify-center bg-overlay-modal/50 p-4">
        <div class="w-full max-w-md rounded-base border border-default bg-neutral-primary-soft p-4 shadow-sm md:p-6">
            <div class="mb-4 flex items-center justify-between border-b border-default pb-3">
                <h3 id="titulo-desaprobar" class="text-lg font-medium text-heading">Desaprobar registro</h3>
                <button type="button" class="text-body cursor-pointer" @click="cerrar()" aria-label="Cerrar">✕</button>
            </div>
            <p class="mb-2 text-sm text-body">La cuenta queda rechazada y no podra ingresar al sistema.</p>
            <p class="mb-4 text-sm font-semibold text-heading wrap-break-word">
                <span x-text="modal.nombre"></span> (SIS: <span x-text="modal.sis"></span>)
            </p>
            <div class="mb-4">
                <label for="motivo-desaprobar" class="mb-2 block text-sm font-medium text-heading">Motivo <span class="text-fg-danger-strong">*</span></label>
                <textarea id="motivo-desaprobar" x-model="modal.motivo" rows="3" maxlength="{{ $motivoMaximo }}"
                          @input="modal.error = ''"
                          :class="modal.error ? 'border-danger-subtle' : 'border-default-medium'"
                          class="block w-full rounded-base border bg-neutral-secondary-medium px-3 py-2.5 text-sm text-heading"></textarea>
                <div class="mt-1 flex justify-between gap-2 text-xs">
                    <p x-show="modal.error" x-text="modal.error" class="text-fg-danger-strong"></p>
                    <p class="ms-auto text-body" x-text="modal.motivo.length + '/{{ $motivoMaximo }}'"></p>
                </div>
            </div>
            <div class="flex flex-col gap-3 sm:flex-row">
                <x-ui.button variant="danger" type="button" class="w-full sm:w-auto cursor-pointer" x-bind:disabled="modal.enviando" @click="desaprobar()">
                    <span x-text="modal.enviando ? 'Desaprobando...' : 'Desaprobar'"></span>
                </x-ui.button>
                <x-ui.button-cancelar type="button" class="w-full sm:w-auto" @click="cerrar()">Cancelar</x-ui.button-cancelar>
            </div>
        </div>
    </div>
</div>
