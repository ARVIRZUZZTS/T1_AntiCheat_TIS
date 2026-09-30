{{--
    @file    modal-registro-ingreso.blade.php
    @author  David E. Chavez T. <virzuzz12345@gmail.com>
    @created 2026-09-29
    @updated 2026-09-29

    @description
    Componente UI Blade reutilizable para el modal de registro de ingreso de
    un estudiante a un examen. Muestra el nombre del estudiante, un campo
    para la hora de ingreso y los botones Confirmar / Cancelar. Sigue el
    patrón dual Livewire/Alpine de los otros modales del sistema.

    @changelog
    - 2026-09-29  [David E. Chavez T.]  feat: creación inicial del componente UI.

    @see  resources/views/pages/monitoreo.blade.php
    @see  resources/views/components/ui/modal-habilitar.blade.php
--}}

@props([])

<div
    x-data="{
        show: false,
        nombre: '',
        sis: '',
        hora: '',
        error: ''
    }"
    x-show="show"
    x-cloak
    tabindex="-1"
    aria-hidden="true"
    @click.self="$dispatch('cerrar-modal-ingreso')"
    @keydown.escape.window="$dispatch('cerrar-modal-ingreso')"
    @cerrar-modal-ingreso.window="show = false"
    @abrir-modal-ingreso.window="show = true; nombre = $event.detail.nombre; sis = $event.detail.sis; hora = ''; error = ''"
    class="overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 flex justify-center items-center w-full md:inset-0 h-full max-h-full bg-overlay-modal/50"
>
    <div class="relative p-4 w-full max-w-md max-h-full">
        <div class="relative bg-neutral-primary-soft border border-default rounded-base shadow-sm p-4 md:p-6">
            <div class="flex items-center justify-between border-b border-default pb-4 md:pb-5">
                <h3 class="text-lg font-medium text-heading">
                    Registrar Ingreso
                </h3>
                <button
                    type="button"
                    @click="$dispatch('cerrar-modal-ingreso')"
                    class="text-body bg-transparent hover:bg-neutral-tertiary hover:text-heading rounded-base text-sm w-9 h-9 ms-auto inline-flex justify-center items-center cursor-pointer"
                >
                    <svg class="w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 17.94 6M18 18 6.06 6"/></svg>
                    <span class="sr-only">Cerrar modal</span>
                </button>
            </div>

            <div class="space-y-4 py-4">
                <p class="text-sm text-body">
                    Registrar el ingreso del estudiante al examen.
                </p>

                <p x-show="nombre !== '' || sis !== ''" class="text-sm font-semibold text-heading">
                    Estudiante: <span x-text="nombre"></span> (SIS: <span x-text="sis"></span>)
                </p>

                <div>
                    <label for="hora_ingreso" class="block text-sm font-medium text-body mb-1">
                        Hora de ingreso
                    </label>
                    <input
                        type="time"
                        id="hora_ingreso"
                        x-model="hora"
                        class="w-full rounded-base border border-default bg-neutral-primary-soft px-3 py-2 text-sm text-heading focus:outline-none focus:ring-2 focus:ring-brand-medium"
                    >
                </div>

                <p x-show="error" x-text="error" class="text-xs text-fg-danger font-medium"></p>
            </div>

            <div class="border-t border-default pt-4 space-y-[2vh]">
                <x-ui.button variant="default" class="w-full" @click="$dispatch('confirmar-ingreso', { hora: hora })">Confirmar</x-ui.button>
                <div class="flex gap-[2vh]">
                    <x-ui.button-cancelar class="flex-1" @click="$dispatch('cerrar-modal-ingreso')">Cancelar</x-ui.button-cancelar>
                    <x-ui.button variant="default" class="flex-1 bg-danger text-white" @click="$dispatch('cerrar-modal-ingreso')">Rechazar</x-ui.button>
                </div>
            </div>
        </div>
    </div>
</div>
