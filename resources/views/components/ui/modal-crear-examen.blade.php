{{--
    @file    modal-crear-examen.blade.php
    @author  Alex Candia <alex.leonar.candia@gmail.com>
    @created 2026-10-05
    @updated 2026-10-05

    @description
    Componente UI Blade reutilizable para el modal de alta de un examen en una
    materia. Muestra el formulario (tipo, fecha de inicio, hora de inicio y
    duración) y sigue el mismo patrón dual de Alpine que modal-registro-ingreso:
    se abre y se cierra con los eventos de ventana `abrir-modal-crear-examen` y
    `cerrar-modal-crear-examen`, sin estado en el servidor.

    La fecha se escribe dd/mm/aaaa con los separadores colocados a mano (así el
    formato no depende del navegador) y la duración son solo dígitos en un
    `type="text"`, para que no aparezcan las flechitas del input number, que en
    móvil se comen medio campo. No hay hora de fin: el examen dura lo que diga
    la duración.

    El formulario tiene, además, el selector de ambientes: un **combobox** cuyo
    input es el buscador y cuya lista (el listbox) se abre al enfocarlo y se va
    filtrando con lo que se escribe. Con la búsqueda vacía están todos los
    ambientes del catálogo, que es lo que se necesita cuando son varios. Al
    tocar una opción se añade a la lista de elegidos, y cada elegido lleva una
    papelera para sacarlo.

    El desplegable va en el flujo y no flotante a propósito: el contenedor del
    modal es el que tiene `overflow-y-auto`, así que un listbox flotante
    (`absolute`) quedaría recortado contra el borde del modal.

    El guardado todavía no está conectado (no existe el servicio de alta de
    exámenes), así que el botón de guardar va deshabilitado y el formulario se
    presenta como pendiente.

    TODO(@equipo, 2026-10-05): reemplazar el aviso por el guardado real cuando
    exista el servicio de alta (`App\Services\Examen\RegistrarExamenService`),
    que es quien tendrá que escribir los ambientes elegidos en `examen_ambiente`.

    @props([
        'materia' => null,
        'tipos' => null,
        'ambientes' => [],
    ])

    @changelog
    - 2026-10-05  [Alex Candia]  feat: creación inicial del componente UI.
    - 2026-10-05  [Alex Candia]  refactor: la fecha pasa a texto dd/mm/aaaa con
      saneo en Alpine (el input date mostraba el formato del navegador), se quita
      la hora de fin —la duración ya la define— y la duración pasa a texto con
      solo dígitos, sin las flechitas del input number.
    - 2026-10-05  [Alex Condia]  feat: selector de ambientes con buscador
      dinámico, lista con scroll y papelera por cada ambiente elegido.
    - 2026-10-05  [Alex Candia]  refactor: el selector pasa a combobox: el input
      es el buscador y la lista es su listbox, que se abre al enfocarlo y se
      filtra mientras se escribe, con navegación por flechas y Enter para elegir.

    @see  resources/views/partials/materia-examenes.blade.php
    @see  App\Enums\TipoExamen
--}}

@php
    use App\Enums\TipoExamen;

    $opciones = $tipos ?? TipoExamen::opciones();
@endphp

<div
    x-data="{
        show: false,
        tipo: '',
        fecha: '',
        horaInicio: '',
        duracion: '',
        busquedaAmbiente: '',
        indiceAmbientes: [],
        ambientesElegidos: [],
        listaAbierta: false,
        indiceResaltado: -1,
        init() {
            const indice = document.getElementById('indice-ambientes-examen');

            this.indiceAmbientes = indice ? JSON.parse(indice.textContent) : [];
        },

        sanearFecha() {
            const digitos = this.fecha.replace(/\D/g, '').slice(0, 8);

            this.fecha = digitos.length <= 2
                ? digitos
                : digitos.length <= 4
                    ? digitos.slice(0, 2) + '/' + digitos.slice(2)
                    : digitos.slice(0, 2) + '/' + digitos.slice(2, 4) + '/' + digitos.slice(4);
        },

        sanearDuracion() {
            this.duracion = this.duracion.replace(/\D/g, '');
        },

        ambientesVisibles() {
            const termino = this.busquedaAmbiente.trim().toLowerCase();
            const elegidos = this.ambientesElegidos.map((ambiente) => ambiente.id);

            return this.indiceAmbientes.filter((ambiente) =>
                !elegidos.includes(ambiente.id)
                && ambiente.termino.indexOf(termino) !== -1
            );
        },

        agregarAmbiente(id) {
            const ambiente = this.indiceAmbientes.find((item) => item.id === id);

            if (! ambiente || this.ambientesElegidos.some((item) => item.id === id)) {
                return;
            }

            this.ambientesElegidos = this.ambientesElegidos.concat(ambiente);
            this.busquedaAmbiente = '';
            this.indiceResaltado = -1;
        },

        quitarAmbiente(id) {
            this.ambientesElegidos = this.ambientesElegidos.filter((ambiente) => ambiente.id !== id);
        },

        moverResaltado(paso) {
            const total = this.ambientesVisibles().length;

            if (total === 0) {
                this.indiceResaltado = -1;

                return;
            }

            this.listaAbierta = true;
            this.indiceResaltado = (this.indiceResaltado + paso + total) % total;
        },
        elegirResaltado() {
            const ambiente = this.ambientesVisibles()[this.indiceResaltado];

            if (! ambiente) {
                return;
            }

            this.agregarAmbiente(ambiente.id);
            this.listaAbierta = false;
        }
    }"
    x-show="show"
    x-cloak
    tabindex="-1"
    aria-hidden="true"
    @click.self="$dispatch('cerrar-modal-crear-examen')"
    @keydown.escape.window="$dispatch('cerrar-modal-crear-examen')"
    @cerrar-modal-crear-examen.window="show = false"
    @abrir-modal-crear-examen.window="show = true; tipo = ''; fecha = ''; horaInicio = ''; duracion = ''; busquedaAmbiente = ''; ambientesElegidos = []; listaAbierta = false; indiceResaltado = -1"
    class="overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 flex justify-center items-center w-full md:inset-0 h-full max-h-full bg-overlay-modal/50"
>
    <div class="relative p-4 w-full max-w-md max-h-full">
        <div class="relative bg-neutral-primary-soft border border-default rounded-base shadow-sm p-4 md:p-6">
            <div class="flex items-center justify-between border-b border-default pb-4 md:pb-5">
                <h3 class="text-lg font-medium text-heading">
                    Crear examen
                </h3>
                <button
                    type="button"
                    @click="$dispatch('cerrar-modal-crear-examen')"
                    class="text-body bg-transparent hover:bg-neutral-tertiary hover:text-heading rounded-base text-sm w-9 h-9 ms-auto inline-flex justify-center items-center cursor-pointer"
                >
                    <svg class="w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 17.94 6M18 18 6.06 6"/></svg>
                    <span class="sr-only">Cerrar modal</span>
                </button>
            </div>

            <div class="space-y-4 py-4">
                @if ($materia)
                    <p class="text-sm text-body">
                        Materia: <span class="font-medium text-heading">{{ $materia }}</span>
                    </p>
                @endif

                <x-ui.select
                    label="Tipo de examen"
                    id="tipo_examen"
                    name="tipo_examen"
                    x-model="tipo"
                    :options="$opciones"
                    placeholder="Selecciona un tipo"
                />

                <x-ui.input
                    label="Fecha de inicio"
                    id="fecha_examen"
                    name="fecha"
                    type="text"
                    placeholder="dd/mm/aaaa"
                    inputmode="numeric"
                    maxlength="10"
                    x-model="fecha"
                    @input="sanearFecha()"
                />

                <x-ui.input
                    label="Hora de inicio"
                    id="hora_inicio_examen"
                    name="hora_inicio"
                    type="time"
                    x-model="horaInicio"
                />

                <x-ui.input
                    label="Duración (minutos)"
                    id="duracion_examen"
                    name="duracion"
                    type="text"
                    inputmode="numeric"
                    x-model="duracion"
                    @input="sanearDuracion()"
                />
                <div @click.outside="listaAbierta = false">
                    <label for="busqueda_ambiente" class="block mb-2.5 text-sm font-medium text-heading">
                        Ambientes
                    </label>

                    <input type="text" name="busqueda_ambiente" id="busqueda_ambiente"
                           role="combobox"
                           aria-autocomplete="list"
                           aria-controls="lista-ambientes"
                           :aria-expanded="listaAbierta"
                           autocomplete="off"
                           x-model="busquedaAmbiente"
                           @click="listaAbierta = true"
                           @input="listaAbierta = true; indiceResaltado = -1"
                           @keydown.down.prevent="moverResaltado(1)"
                           @keydown.up.prevent="moverResaltado(-1)"
                           @keydown.enter.prevent="elegirResaltado()"
                           @keydown.escape="listaAbierta = false"
                           placeholder="Buscar ambiente..."
                           class="block w-full p-3 border border-default-medium bg-neutral-secondary-medium text-heading text-sm rounded-base focus:ring-brand focus:border-brand shadow-xs placeholder:text-body" />

                    <ul id="lista-ambientes" role="listbox" aria-label="Ambientes disponibles"
                        x-show="listaAbierta" x-cloak
                        class="mt-2 max-h-40 overflow-y-auto rounded-base border border-default divide-y divide-default">
                        <template x-for="(ambiente, posicion) in ambientesVisibles()" :key="ambiente.id">
                            <li>
                                <button type="button" role="option"
                                        :aria-selected="posicion === indiceResaltado"
                                        @click="agregarAmbiente(ambiente.id)"
                                        class="flex w-full items-center justify-between gap-2 px-3 py-2.5 text-start text-sm hover:bg-neutral-secondary-medium focus:outline-none focus:ring-2 focus:ring-brand-medium"
                                        :class="posicion === indiceResaltado ? 'bg-neutral-secondary-medium text-heading' : 'text-body'">
                                    <span class="truncate" x-text="ambiente.nombre"></span>
                                    <svg class="w-4 h-4 shrink-0 text-fg-brand" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v14m7-7H5"/></svg>
                                </button>
                            </li>
                        </template>

                        <li x-show="ambientesVisibles().length === 0" class="px-3 py-2.5 text-sm text-body">
                            No hay ambientes que coincidan con la búsqueda.
                        </li>
                    </ul>
                    <p class="mt-3 mb-1.5 text-xs text-muted">
                        Ambientes del examen: <span x-text="ambientesElegidos.length"></span>
                    </p>

                    <ul class="flex flex-col gap-2">
                        <template x-for="ambiente in ambientesElegidos" :key="ambiente.id">
                            <li class="flex items-center justify-between gap-2 px-3 py-2 rounded-base border border-default bg-neutral-secondary-soft">
                                <span class="truncate text-sm text-heading" x-text="ambiente.nombre"></span>
                                <button type="button" @click="quitarAmbiente(ambiente.id)"
                                        class="shrink-0 inline-flex items-center justify-center w-9 h-9 rounded-base text-body hover:text-fg-danger hover:bg-neutral-secondary-medium focus:outline-none focus:ring-2 focus:ring-danger-medium"
                                        :aria-label="'Quitar ' + ambiente.nombre">
                                    <svg class="w-4 h-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0 1 16.138 21H7.862a2 2 0 0 1-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 0 0-1-1H10a1 1 0 0 0-1 1v3M4 7h16"/></svg>
                                    <span class="sr-only" x-text="'Quitar ' + ambiente.nombre"></span>
                                </button>
                            </li>
                        </template>
                    </ul>
                </div>

                <x-ui.alert type="brand">
                    Todavía no hay endpoint de alta de exámenes: el formulario no guarda los datos.
                </x-ui.alert>
            </div>

            <div class="border-t border-default pt-4 flex flex-col-reverse gap-[2vh] sm:flex-row">
                <x-ui.button-cancelar class="flex-1" @click="$dispatch('cerrar-modal-crear-examen')">Cancelar</x-ui.button-cancelar>
                <x-ui.button variant="default" class="flex-1" disabled>Guardar examen</x-ui.button>
            </div>
        </div>
    </div>

    {{-- Índice de ambientes para el buscador. Va como application/json y se lee
         con JSON.parse sobre textContent (no como atributo) por el mismo motivo
         que en el buscador de registro: el escapado del HTML no se mete en medio. --}}
    <script type="application/json" id="indice-ambientes-examen">@json($ambientes)</script>
</div>
