{{--
    @file    modal-registrar-tramposo.blade.php
    @author  David E. Chavez T. <virzuzz12345@gmail.com>
    @created 2026-10-10
    @updated 2026-10-10
    @updated 2026-10-25

    @description
    Componente UI Blade reutilizable para el registro rápido de incidencia
    (M5) de un estudiante de la lista del monitor: muestra su ficha (nombre y
    SIS precargados) y los campos comunes de la incidencia (fecha y hora,
    estado según el rol, motivo de la vista y descripción). Se abre con
    'abrir-modal-tramposo' o 'registrar-incidencia' y emite 'registrar-tramposo'
    con el motivo y el detalle.

    El botón queda deshabilitado mientras falte el motivo, o la descripción
    cuando el motivo es "Otro"; el servidor vuelve a validar todo.

    @changelog
    - 2026-10-10  [David E. Chavez T.]  feat: creación inicial del componente.
    - 2026-10-25  [Valery D. Ortuno P]  feat: los campos de la incidencia pasan
      al parcial compartido con el registro del no inscrito (motivos de
      Motivo::paraIngreso() / paraMonitor(), fecha y hora, estado según el rol,
      descripción obligatoria con "Otro" y tope de 300), y el botón se
      deshabilita mientras falte un dato obligatorio; el encabezado ya no repite
      nombre y SIS, que se ven en la ficha (HU 5).

    @see  resources/views/partials/modales/campos-incidencia.blade.php
    @see  resources/views/components/ui/modal-registrar-no-inscrito.blade.php
    @see  app/Enums/Motivo.php
--}}

@props([
    'contexto' => 'monitor',
    'comoAuxiliar' => false,
])

<div
    x-data="{
        show: false,
        nombre: '',
        sis: '',
        motivo: '',
        detalle: '',
        ahora: '',
        abrir(datos) {
            datos = datos || {};
            this.nombre = datos.nombre || '';
            this.sis = datos.sis || '';
            this.motivo = '';
            this.detalle = '';
            this.ahora = new Date().toLocaleString('es-BO', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit', hour12: false });
            this.show = true;
        },
        get completo() {
            return this.motivo !== '' && (this.motivo !== 'otro' || this.detalle.trim() !== '');
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
                {{-- Sin nombre ni SIS: la ficha del estudiante ya está en el cuerpo. --}}
                <h3 id="titulo-modal-tramposo" class="text-lg font-medium text-heading">Registrar incidencia</h3>
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
                    'avatar' => false,
                    'fondo' => $comoAuxiliar ? 'revision' : 'riesgo',
                ])

                @include('partials.modales.campos-incidencia', [
                    'contexto' => $contexto,
                    'comoAuxiliar' => $comoAuxiliar,
                    'prefijo' => 'tramposo',
                ])
            </div>

            {{-- Pie --}}
            <div class="border-t border-default pt-4 space-y-[2vh]">
                <button
                    type="button"
                    :disabled="! completo"
                    :class="completo ? 'text-white bg-danger border-transparent hover:bg-danger-strong cursor-pointer' : 'text-fg-disabled bg-disabled border-default-medium cursor-not-allowed'"
                    class="w-full inline-flex items-center justify-center box-border border focus:ring-4 focus:ring-danger-medium shadow-xs font-medium leading-5 rounded-base focus:outline-none px-4 py-2.5 text-sm"
                    @click="$dispatch('registrar-tramposo', { sis: sis, motivo: motivo, detalle: detalle }); show = false"
                >Registrar incidencia</button>
                <x-ui.button-cancelar class="w-full" @click="show = false">Cancelar</x-ui.button-cancelar>
            </div>
        </div>
    </div>
</div>
