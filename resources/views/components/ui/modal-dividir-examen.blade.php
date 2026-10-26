{{--
    @file    modal-dividir-examen.blade.php
    @author  Alisson D. Alvarado <alvaradoalissondalet@gmail.com>
    @created 2026-10-25
    @updated 2026-10-26

    @description
    Modal de vista previa para la division de examen por ambientes.
    Muestra cada ambiente, su rango o criterio y la cantidad de estudiantes
    asignados, con botones Cancelar y Confirmar division. Al confirmar, delega
    en el endpoint que usa DividirExamenService y avisa a la vista para abrir
    el modal de exito.

    @changelog
    - 2026-10-25  [Alisson D. Alvarado]  feat: creacion inicial del modal de vista previa de division.
    - 2026-10-26  [Alisson D. Alvarado]  fix: corrige el selector CSRF, usa la ruta nombrada
      y muestra el error de la division sin cerrar el modal.

    @see  resources/views/partials/examenes-curso.blade.php
    @see  App\Http\Controllers\Api\DividirExamenController
--}}

@props([])

<div
    x-data="{
        show: false,
        examenId: null,
        ambientes: [],
        divisionPor: '',
        total: 0,
        cargando: false,
        error: '',
        abrir(datos) {
            datos = datos || {};
            this.examenId = datos.examenId || null;
            this.ambientes = datos.ambientes || [];
            this.divisionPor = datos.divisionPor || '';
            this.total = datos.total || 0;
            this.error = '';
            this.cargando = false;
            this.show = true;
        },
        async confirmar() {
            if (! this.examenId) {
                return;
            }

            this.cargando = true;
            this.error = '';

            try {
                const url = '{{ route('api.examenes.dividir.confirmar', ['examen' => '__ID__']) }}'
                    .replace('__ID__', this.examenId);
                const respuesta = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                    },
                    body: JSON.stringify({
                        ambientes: this.ambientes.map((ambiente) => ambiente.id_ambiente),
                        division_por: this.divisionPor
                    })
                });
                const cuerpo = await respuesta.json().catch(() => ({}));

                if (! respuesta.ok || ! cuerpo.exito) {
                    this.error = cuerpo.mensaje || cuerpo.message || 'No se pudo dividir el examen.';
                    return;
                }

                this.show = false;
                window.dispatchEvent(new CustomEvent('division-confirmada', { detail: cuerpo }));
            } catch (e) {
                this.error = 'No se pudo conectar con el servidor.';
            } finally {
                this.cargando = false;
            }
        },
        cancelar() {
            if (this.cargando) {
                return;
            }
            this.show = false;
        }
    }"
    x-show="show"
    x-cloak
    tabindex="-1"
    role="dialog"
    aria-modal="true"
    aria-labelledby="titulo-modal-dividir"
    @abrir-modal-dividir.window="abrir($event.detail)"
    @keydown.escape.window="cancelar()"
    @click.self="cancelar()"
    class="overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 flex justify-center items-center w-full md:inset-0 h-full max-h-full bg-overlay-modal/50"
>
    <div class="relative p-4 w-full max-w-2xl max-h-full">
        <div class="relative bg-neutral-primary-soft border border-default rounded-base shadow-sm">
            <div class="flex items-center justify-between p-4 md:p-5 border-b border-default rounded-t-base">
                <h3 id="titulo-modal-dividir" class="text-lg font-semibold text-heading">
                    Vista previa de division
                </h3>
                <button type="button" @click="cancelar()" class="text-body bg-transparent hover:bg-neutral-tertiary hover:text-heading rounded-base text-sm w-8 h-8 ms-auto inline-flex justify-center items-center">
                    <svg class="w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 17.94 6M18 18 6.06 6"/></svg>
                    <span class="sr-only">Cerrar</span>
                </button>
            </div>
            <div class="p-4 md:p-5 space-y-4">
                <div x-show="error !== ''" x-cloak>
                    <x-ui.alert type="danger" title="No se pudo dividir el examen">
                        <span x-text="error"></span>
                    </x-ui.alert>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left text-body">
                        <thead class="text-xs text-body uppercase bg-neutral-secondary-soft">
                            <tr>
                                <th scope="col" class="px-6 py-3">Ambiente</th>
                                <th scope="col" class="px-6 py-3">Rango / criterio</th>
                                <th scope="col" class="px-6 py-3">Cantidad de estudiantes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="amb in ambientes" :key="amb.id_ambiente">
                                <tr class="bg-neutral-primary-soft border-b border-default">
                                    <td class="px-6 py-4 font-medium text-heading" x-text="amb.nombre_ambiente"></td>
                                    <td class="px-6 py-4" x-text="amb.rango || amb.criterio"></td>
                                    <td class="px-6 py-4" x-text="amb.cantidad"></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
                <p class="text-sm text-body">Total de estudiantes: <span x-text="total"></span></p>
                <div class="flex items-center gap-2">
                    <x-ui.button variant="secondary" @click="cancelar()" x-bind:disabled="cargando">Cancelar</x-ui.button>
                    <x-ui.button variant="default" @click="confirmar()" x-bind:disabled="cargando">
                        <span x-show="! cargando">Confirmar division</span>
                        <span x-show="cargando" x-cloak>Dividiendo...</span>
                    </x-ui.button>
                </div>
            </div>
        </div>
    </div>
</div>
