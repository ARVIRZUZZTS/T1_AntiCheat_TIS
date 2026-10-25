{{--
    @file    modal-incidencia-registrada.blade.php
    @author  Valery D. Ortuno P <valerydariana98@gmail.com>
    @created 2026-10-25

    @description
    Modal de confirmación del registro de una incidencia en el monitor en vivo
    (HU 5), con el mismo diseño que el aviso de "Examen creado con exito":
    ícono, "Estudiante agregado a la central de riesgos" y un único botón
    "Aceptar", que emite 'cerrar-resumen-incidencia' y deja a la persona en el
    monitor, que es la pantalla desde la que registró.

    Se pinta solo mientras el monitor tiene una incidencia recién registrada,
    así que aparece abierto.

    @changelog
    - 2026-10-25  [Valery D. Ortuno P]  feat: creación inicial del componente.

    @see  App\Livewire\Monitoreo\MonitorEnVivo
    @see  resources/views/components/ui/modal-aviso.blade.php
--}}

<div
    x-data
    tabindex="-1"
    role="dialog"
    aria-modal="true"
    aria-labelledby="titulo-incidencia-registrada"
    @click.self="$dispatch('cerrar-resumen-incidencia')"
    @keydown.escape.window="$dispatch('cerrar-resumen-incidencia')"
    class="overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 flex justify-center items-center w-full md:inset-0 h-full max-h-full bg-overlay-modal/50"
>
    <div class="relative p-4 w-full max-w-sm max-h-full">
        <div class="relative bg-neutral-primary-soft border border-default rounded-base shadow-sm p-6 text-center">
            <span class="mx-auto flex items-center justify-center w-12 h-12 rounded-full bg-success-soft text-fg-success-strong">
                <svg class="w-6 h-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.5 11.5 11 14l4.5-5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
            </span>

            <p id="titulo-incidencia-registrada" class="mt-4 text-sm text-heading">Estudiante agregado a la central de riesgos</p>

            <x-ui.button variant="default" class="mt-5 w-full" @click="$dispatch('cerrar-resumen-incidencia')">
                Aceptar
            </x-ui.button>
        </div>
    </div>
</div>
