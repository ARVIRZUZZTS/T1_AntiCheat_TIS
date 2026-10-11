{{--
    @file    modal-aviso.blade.php
    @author  Alex Candia <alex.leonar.candia@gmail.com>
    @created 2026-10-10
    @updated 2026-10-10

    @description
    Modal chico de aviso para confirmar que una acción ya se hizo (por ejemplo,
    el alta de un examen). Aparece solo al renderizarse, tiene un único botón
    "Aceptar" y se cierra con ese botón, con Esc o con un clic fuera, dejando a
    la persona en la misma pantalla donde estaba. Es solo presentación: no
    envía ni guarda nada.

    @changelog
    - 2026-10-10  [Alex Candia]  feat: creación inicial del componente.

    @see  resources/views/partials/materia-examenes.blade.php
    @see  resources/views/components/ui/alert.blade.php
--}}

@props([
    'mensaje' => '',
])

<div
    x-data="{ visible: true }"
    x-show="visible"
    x-cloak
    tabindex="-1"
    role="dialog"
    aria-modal="true"
    aria-labelledby="titulo-aviso"
    @click.self="visible = false"
    @keydown.escape.window="visible = false"
    class="overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 flex justify-center items-center w-full md:inset-0 h-full max-h-full bg-overlay-modal/50"
>
    <div class="relative p-4 w-full max-w-sm max-h-full">
        <div class="relative bg-neutral-primary-soft border border-default rounded-base shadow-sm p-6 text-center">
            <span class="mx-auto flex items-center justify-center w-12 h-12 rounded-full bg-success-soft text-fg-success-strong">
                <svg class="w-6 h-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.5 11.5 11 14l4.5-5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
            </span>

            <p id="titulo-aviso" class="mt-4 text-sm text-heading">{{ $mensaje }}</p>

            <x-ui.button variant="default" class="mt-5 w-full" @click="visible = false">
                Aceptar
            </x-ui.button>
        </div>
    </div>
</div>
