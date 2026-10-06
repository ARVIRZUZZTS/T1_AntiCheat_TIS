{{--
    @file    modal-crear-examen.blade.php
    @author  Alex Candia <alex.leonar.candia@gmail.com>
    @created 2026-10-05
    @updated 2026-10-05

    @description
    Componente UI Blade reutilizable para el modal de alta de un examen en una
    materia. Muestra el formulario (tipo, fecha, ventana horaria y duración) y
    sigue el mismo patrón dual de Alpine que modal-registro-ingreso: se abre y
    se cierra con los eventos de ventana `abrir-modal-crear-examen` y
    `cerrar-modal-crear-examen`, sin estado en el servidor.

    El guardado todavía no está conectado (no existe el servicio de alta de
    exámenes), así que el botón de guardar va deshabilitado y el formulario se
    presenta como pendiente.

    TODO(@equipo, 2026-10-05): reemplazar el aviso por el guardado real cuando
    exista el servicio de alta (`App\Services\Examen\RegistrarExamenService`).

    @props([
        'materia' => null,
        'tipos' => null,
    ])

    @changelog
    - 2026-10-05  [Alex Candia]  feat: creación inicial del componente UI.

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
        horaFin: '',
        duracion: ''
    }"
    x-show="show"
    x-cloak
    tabindex="-1"
    aria-hidden="true"
    @click.self="$dispatch('cerrar-modal-crear-examen')"
    @keydown.escape.window="$dispatch('cerrar-modal-crear-examen')"
    @cerrar-modal-crear-examen.window="show = false"
    @abrir-modal-crear-examen.window="show = true; tipo = ''; fecha = ''; horaInicio = ''; horaFin = ''; duracion = ''"
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
                    label="Fecha"
                    id="fecha_examen"
                    name="fecha"
                    type="date"
                    x-model="fecha"
                />

                {{-- Mobile: los horarios se apilan para que los inputs de hora
                     no queden estrechos; desde sm: van en dos columnas. --}}
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <x-ui.input
                        label="Hora de inicio"
                        id="hora_inicio_examen"
                        name="hora_inicio"
                        type="time"
                        x-model="horaInicio"
                    />

                    <x-ui.input
                        label="Hora de fin"
                        id="hora_fin_examen"
                        name="hora_fin"
                        type="time"
                        x-model="horaFin"
                    />
                </div>

                <x-ui.input
                    label="Duración (minutos)"
                    id="duracion_examen"
                    name="duracion"
                    type="number"
                    min="1"
                    x-model="duracion"
                />

                <x-ui.alert type="brand">
                    Todavía no hay endpoint de alta de exámenes: el formulario no guarda los datos.
                </x-ui.alert>
            </div>

            {{-- Mobile: los botones se apilan a lo ancho (stretch del column
                 flex) y el de guardar queda arriba; desde sm: van en fila. --}}
            <div class="border-t border-default pt-4 flex flex-col-reverse gap-[2vh] sm:flex-row">
                <x-ui.button-cancelar class="flex-1" @click="$dispatch('cerrar-modal-crear-examen')">Cancelar</x-ui.button-cancelar>
                <x-ui.button variant="default" class="flex-1" disabled>Guardar examen</x-ui.button>
            </div>
        </div>
    </div>
</div>
