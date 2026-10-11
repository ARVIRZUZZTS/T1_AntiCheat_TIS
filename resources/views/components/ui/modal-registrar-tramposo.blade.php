{{--
    @file    modal-registrar-tramposo.blade.php
    @author  David E. Chavez T. <virzuzz12345@gmail.com>
    @created 2026-10-10
    @updated 2026-10-10

    @description
    Componente UI Blade reutilizable para el modal de registro de tramposo
    (M5). Muestra la ficha del estudiante, el selector de motivo (los casos
    del enum App\Enums\Motivo como tarjetas de radio) y una descripción
    opcional, además de la acción de adjuntar foto. Se abre con
    'abrir-modal-tramposo' o 'registrar-incidencia' y emite
    'registrar-tramposo' con el motivo y el detalle.

    @changelog
    - 2026-10-10  [David E. Chavez T.]  feat: creación inicial del componente.

    @see  resources/views/pages/monitoreo.blade.php
    @see  app/Enums/Motivo.php
    @see  plan_modales.md (sección 8)
--}}

@props([])

@php
    $motivos = \App\Enums\Motivo::cases();
@endphp

<div
    x-data="{
        show: false,
        nombre: '',
        sis: '',
        materia: '',
        aula: '',
        motivo: '',
        detalle: '',
        abrir(datos) {
            datos = datos || {};
            this.nombre = datos.nombre || '';
            this.sis = datos.sis || '';
            this.materia = datos.materia || '';
            this.aula = datos.aula || '';
            this.motivo = '';
            this.detalle = '';
            this.show = true;
        }
    }"
    x-show="show"
    x-cloak
    tabindex="-1"
    role="dialog"
    aria-modal="true"
    aria-labelledby="titulo-modal-tramposo"
    @abrir-modal-tramposo.window="abrir($event.detail)"
    @registrar-incidencia.window="abrir($event.detail)"
    @cerrar-modal-tramposo.window="show = false"
    @click.self="show = false"
    @keydown.escape.window="show = false"
    class="overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 flex justify-center items-center w-full md:inset-0 h-full max-h-full bg-overlay-modal/50"
>
    <div class="relative p-4 w-full max-w-md max-h-full">
        <div class="relative bg-neutral-primary-soft border border-default rounded-base shadow-sm p-4 md:p-6">
            {{-- Cabecera --}}
            <div class="flex items-start justify-between gap-3 border-b border-default pb-4 md:pb-5">
                <div>
                    <h3 id="titulo-modal-tramposo" class="text-lg font-medium text-heading">Registrar Tramposo</h3>
                    <p class="text-sm text-muted"><span x-text="nombre"></span> · <span x-text="sis"></span></p>
                </div>
                <button
                    type="button"
                    @click="show = false"
                    class="text-body bg-transparent hover:bg-neutral-tertiary hover:text-heading rounded-base text-sm w-9 h-9 ms-auto inline-flex justify-center items-center cursor-pointer"
                >
                    <svg class="w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 17.94 6M18 18 6.06 6"/></svg>
                    <span class="sr-only">Cerrar modal</span>
                </button>
            </div>

            {{-- Cuerpo --}}
            <div class="space-y-4 py-4">
                @include('partials.modales.ficha-estudiante', [
                    'nombre' => 'nombre',
                    'sis' => 'sis',
                    'tone' => 'riesgo',
                ])

                <div>
                    <p class="mb-2 text-sm font-medium text-heading">Motivo</p>
                    <div class="space-y-2">
                        @foreach ($motivos as $item)
                            <label
                                for="motivo-tramposo-{{ $item->name }}"
                                class="flex cursor-pointer items-center gap-3 rounded-base p-3 transition"
                                :class="motivo === @js($item->value)
                                    ? 'border-2 border-brand bg-brand-softer'
                                    : 'border border-light hover:bg-neutral-secondary-soft'"
                            >
                                <input
                                    type="radio"
                                    id="motivo-tramposo-{{ $item->name }}"
                                    name="motivo_tramposo"
                                    value="{{ $item->value }}"
                                    x-model="motivo"
                                    class="h-4 w-4 shrink-0 accent-brand"
                                >
                                <span class="text-sm text-heading">{{ $item->etiqueta() }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <x-ui.textarea
                    name="detalle_tramposo"
                    rows="3"
                    placeholder="Describe lo observado..."
                    x-model="detalle"
                />
            </div>

            {{-- Pie --}}
            <div class="border-t border-default pt-4 space-y-[2vh]">
                <x-ui.button
                    variant="danger"
                    class="w-full"
                    @click="$dispatch('registrar-tramposo', { sis: sis, motivo: motivo, detalle: detalle }); show = false"
                >Registrar Tramposo</x-ui.button>
                <div class="flex gap-[2vh]">
                    <x-ui.button-cancelar class="flex-1" @click="show = false">Cancelar</x-ui.button-cancelar>
                    <x-ui.button variant="secondary" class="flex-1">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="mr-1.5 h-4 w-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m18.375 12.739-7.693 7.693a4.5 4.5 0 0 1-6.364-6.364l10.94-10.94A3 3 0 1 1 19.5 7.372L8.552 18.32m.009-.01-.01.01m5.699-9.941-7.81 7.81a1.5 1.5 0 0 0 2.112 2.13" />
                        </svg>
                        Adjuntar Foto
                    </x-ui.button>
                </div>
            </div>
        </div>
    </div>
</div>
