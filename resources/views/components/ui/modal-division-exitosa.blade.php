{{--
    @file    modal-division-exitosa.blade.php
    @author  Alisson D. Alvarado <alvaradoalissondalet@gmail.com>
    @created 2026-10-25
    @updated 2026-10-26

    @description
    Modal de confirmacion tras dividir el examen con exito. Muestra el mensaje
    devuelto por el endpoint y un resumen con cada ambiente y la cantidad de
    estudiantes asignados. El boton Aceptar cierra el proceso.

    @changelog
    - 2026-10-25  [Alisson D. Alvarado]  feat: creacion inicial.
    - 2026-10-26  [Alisson D. Alvarado]  fix: muestra el mensaje del endpoint y
      valida que el resumen sea una lista antes de recorrerlo.

    @see  resources/views/partials/examenes-curso.blade.php
    @see  App\Http\Controllers\Api\DividirExamenController
--}}

@props([])

<div
    x-data="{
        show: false,
        mensaje: 'Examen dividido con éxito',
        resumen: [],
        abrir(datos) {
            datos = datos || {};
            this.mensaje = datos.mensaje || 'Examen dividido con éxito';
            this.resumen = Array.isArray(datos.resumen) ? datos.resumen : [];
            this.show = true;
        },
        aceptar() {
            this.show = false;
        }
    }"
    x-show="show"
    x-cloak
    tabindex="-1"
    role="dialog"
    aria-modal="true"
    aria-labelledby="titulo-modal-division-exitosa"
    @division-confirmada.window="abrir($event.detail)"
    @keydown.escape.window="aceptar()"
    @click.self="aceptar()"
    class="overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 flex justify-center items-center w-full md:inset-0 h-full max-h-full bg-overlay-modal/50"
>
    <div class="relative p-4 w-full max-w-sm max-h-full">
        <div class="relative bg-neutral-primary-soft border border-default rounded-base shadow-sm p-6 text-center">
            <span class="mx-auto flex items-center justify-center w-12 h-12 rounded-full bg-success-soft text-fg-success-strong">
                <svg class="w-6 h-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.5 11.5 11 14l4.5-5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
            </span>

            <p id="titulo-modal-division-exitosa" class="mt-4 text-sm text-heading" x-text="mensaje"></p>

            <div class="mt-4 text-left text-sm text-body">
                <p class="font-medium text-heading">Resumen de ambientes</p>
                <div class="mt-2 space-y-1">
                    <template x-for="ambiente in resumen" :key="ambiente.nombre_ambiente">
                        <div class="flex justify-between gap-4">
                            <span x-text="ambiente.nombre_ambiente"></span>
                            <span x-text="ambiente.cantidad"></span>
                        </div>
                    </template>
                </div>
            </div>

            <x-ui.button variant="default" class="mt-5 w-full" @click="aceptar()">
                Aceptar
            </x-ui.button>
        </div>
    </div>
</div>
