{{--
    Modal de opciones al rechazar un ingreso.
--}}
@props([])

<div
    x-data="{
        show: false,
        sis: '',
        nombre: '',
        abrir(datos) {
            datos = datos || {};
            this.sis = datos.sis || '';
            this.nombre = datos.nombre || '';
            this.show = true;
        }
    }"
    x-show="show"
    x-cloak
    tabindex="-1"
    role="dialog"
    aria-modal="true"
    @abrir-modal-rechazo.window="abrir($event.detail)"
    @click.self="show = false"
    @keydown.escape.window="show = false"
    class="overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 flex justify-center items-center w-full md:inset-0 h-full max-h-full bg-overlay-modal/50"
>
    <div class="relative p-4 w-full max-w-md max-h-full">
        <div class="relative bg-neutral-primary-soft border border-default rounded-base shadow-sm p-4 md:p-6">
            <h3 class="text-lg font-medium text-heading mb-2">Rechazar ingreso</h3>
            <p class="text-sm text-muted mb-4">¿Desea registrar una incidencia mayor?</p>

            <div class="space-y-3">
                <x-ui.button
                    variant="danger"
                    class="w-full"
                    @click="$dispatch('abrir-modal-tramposo', { sis: sis, nombre: nombre }); show = false"
                >Registrar incidencia</x-ui.button>

                <x-ui.button
                    variant="secondary"
                    class="w-full"
                    @click="$dispatch('rechazar-ingreso', { sis: sis }); show = false"
                >Rechazar sin incidencia</x-ui.button>

                <x-ui.button-cancelar class="w-full" @click="show = false">Cancelar</x-ui.button-cancelar>
            </div>
        </div>
    </div>
</div>