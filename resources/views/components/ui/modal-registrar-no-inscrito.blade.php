{{--
    @file    modal-registrar-no-inscrito.blade.php
    @author  Valery D. Ortuno P <valerydariana98@gmail.com>
    @created 2026-10-25

    @description
    Componente UI Blade para registrar la incidencia de un estudiante que no
    está en la lista del examen (HU 5), con la misma estética que el registro
    rápido (modal-registrar-tramposo) y los mismos campos de incidencia.

    Se escribe el SIS (solo dígitos, 8 o 9) y, mientras se escribe, se pregunta
    con el evento 'consultar-no-inscrito' si el estudiante existe; la respuesta
    llega por 'estudiante-no-inscrito-consultado':
     - si existe, se precargan sus datos en la ficha, como en el registro
       rápido;
     - si no existe, se piden nombres y apellidos (obligatorios) y se avisa
       que quedará como estudiante no verificado.

    Si en el buscador se escribió un nombre, llega en `sugerido` ya repartido
    entre nombres y apellidos y se precarga en esos campos, que se ven desde
    el inicio (mismo formulario que al registrar con SIS).

    Se abre con 'abrir-modal-no-inscrito' (con el SIS buscado precargado) y
    emite 'registrar-no-inscrito'. El botón queda deshabilitado mientras falte
    un dato obligatorio; el servidor vuelve a validar todo.

    @changelog
    - 2026-10-25  [Valery D. Ortuno P]  feat: creación inicial del componente.

    @see  resources/views/partials/modales/campos-incidencia.blade.php
    @see  App\Livewire\Monitoreo\MonitorEnVivo::registrarNoInscrito()
    @see  App\Services\CentralRiesgo\RegistrarIncidenciaService
--}}

@props([
    'contexto' => 'monitor',
    'comoAuxiliar' => false,
])

<div
    x-data="{
        show: false,
        sis: '',
        consultado: false,
        existe: false,
        verificado: true,
        nombre: '',
        apellido: '',
        sugerido: { nombre: '', apellido: '' },
        motivo: '',
        detalle: '',
        ahora: '',
        temporizador: null,
        abrir(datos) {
            const estudiante = (datos && datos.estudiante) || {};
            this.sugerido = (datos && datos.sugerido) || { nombre: '', apellido: '' };
            this.existe = false;
            this.nombre = '';
            this.apellido = '';
            this.motivo = '';
            this.detalle = '';
            this.ahora = new Date().toLocaleString('es-BO', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit', hour12: false });
            this.recibir(estudiante);
            this.show = true;
        },
        // Respuesta del servidor para el SIS consultado. Si ya se cambió el
        // SIS mientras llegaba, se descarta.
        recibir(estudiante) {
            if ((estudiante.sis || '') !== this.sis && this.show) {
                return;
            }
            this.sis = estudiante.sis || '';
            this.consultado = this.sisValido;
            this.verificado = estudiante.verificado !== false;
            if (estudiante.existe) {
                this.existe = true;
                this.nombre = estudiante.nombre || '';
                this.apellido = estudiante.apellido || '';
            } else {
                this.olvidarExistente();
            }
        },
        // Al dejar de mostrar a un estudiante existente, los campos vuelven a
        // lo buscado; si ya se estaban llenando a mano, se respetan.
        olvidarExistente() {
            if (this.existe || (this.nombre === '' && this.apellido === '')) {
                this.nombre = this.sugerido.nombre || '';
                this.apellido = this.sugerido.apellido || '';
            }
            this.existe = false;
        },
        // Solo dígitos, hasta 9; con 8 o 9 se pregunta si existe.
        escribirSis(valor) {
            this.sis = valor.replace(/\D/g, '').slice(0, 9);
            this.consultado = false;
            this.olvidarExistente();
            clearTimeout(this.temporizador);
            if (this.sisValido) {
                this.temporizador = setTimeout(() => this.$dispatch('consultar-no-inscrito', { sis: this.sis }), 350);
            }
        },
        get sisValido() {
            return /^\d{8,9}$/.test(this.sis);
        },
        get completo() {
            const estudianteListo = this.consultado && (this.existe || (this.nombre.trim() !== '' && this.apellido.trim() !== ''));
            return estudianteListo && this.motivo !== '' && (this.motivo !== 'otro' || this.detalle.trim() !== '');
        },
        registrar() {
            this.$dispatch('registrar-no-inscrito', {
                sis: this.sis,
                motivo: this.motivo,
                detalle: this.detalle,
                nombre: this.existe ? null : this.nombre,
                apellido: this.existe ? null : this.apellido,
            });
            this.show = false;
        }
    }"
    x-show="show"
    x-cloak
    tabindex="-1"
    role="dialog"
    aria-modal="true"
    aria-labelledby="titulo-modal-no-inscrito"
    @abrir-modal-no-inscrito.window="abrir($event.detail)"
    @estudiante-no-inscrito-consultado.window="recibir($event.detail.estudiante || {})"
    @click.self="show = false"
    @keydown.escape.window="show = false"
    class="overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 flex justify-center items-center w-full md:inset-0 h-full max-h-full bg-overlay-modal/50"
>
    <div class="relative p-4 w-full max-w-md max-h-full">
        <div class="relative bg-neutral-primary-soft border border-default rounded-base shadow-sm p-4 md:p-6">
            {{-- Cabecera: misma que el registro rápido. --}}
            <div class="flex items-start justify-between gap-3 border-b border-default pb-4 md:pb-5">
                <div>
                    <h3 id="titulo-modal-no-inscrito" class="text-lg font-medium text-heading">Registrar incidencia</h3>
                    {{-- El estudiante ya se ve en el cuerpo; acá solo se avisa
                         cuando no existe y quedará como no verificado. --}}
                    <p x-show="consultado && ! existe" class="text-sm text-muted">Estudiante no verificado</p>
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
                <div>
                    <label for="no-inscrito-sis" class="mb-2 block text-sm font-medium text-heading">Codigo SIS <span class="text-fg-danger-strong" aria-hidden="true">*</span></label>
                    <input
                        type="text"
                        id="no-inscrito-sis"
                        inputmode="numeric"
                        maxlength="9"
                        placeholder="Ingrese el codigo SIS"
                        :value="sis"
                        @input="escribirSis($event.target.value); $event.target.value = sis"
                        class="block w-full rounded-base border border-default-medium bg-neutral-secondary-medium p-3 text-sm text-heading shadow-xs placeholder:text-body focus:border-brand focus:ring-brand"
                    >
                    <p x-show="sis !== '' && ! sisValido" class="mt-1 text-xs text-muted">El codigo SIS tiene 8 o 9 digitos.</p>
                </div>

                {{-- Existe en la base: sus datos precargados, como en el registro rápido. --}}
                <template x-if="consultado && existe">
                    <div>
                        @include('partials.modales.ficha-estudiante', [
                            'nombre' => "(nombre + ' ' + apellido)",
                            'sis' => 'sis',
                            'avatar' => false,
                            'fondo' => $comoAuxiliar ? 'revision' : 'riesgo',
                        ])
                    </div>
                </template>

                {{-- No existe: se piden sus datos y queda como no verificado. --}}
                <template x-if="! existe">
                    <div class="space-y-4">
                        <div>
                            <label for="no-inscrito-nombre" class="mb-2 block text-sm font-medium text-heading">Nombres <span class="text-fg-danger-strong" aria-hidden="true">*</span></label>
                            <input type="text" id="no-inscrito-nombre" maxlength="50" placeholder="Ingrese los nombres" x-model="nombre"
                                   class="block w-full rounded-base border border-default-medium bg-neutral-secondary-medium p-3 text-sm text-heading shadow-xs placeholder:text-body focus:border-brand focus:ring-brand">
                        </div>
                        <div>
                            <label for="no-inscrito-apellido" class="mb-2 block text-sm font-medium text-heading">Apellidos <span class="text-fg-danger-strong" aria-hidden="true">*</span></label>
                            <input type="text" id="no-inscrito-apellido" maxlength="50" placeholder="Ingrese los apellidos" x-model="apellido"
                                   class="block w-full rounded-base border border-default-medium bg-neutral-secondary-medium p-3 text-sm text-heading shadow-xs placeholder:text-body focus:border-brand focus:ring-brand">
                        </div>
                        <p class="text-xs text-muted">Se guardara como estudiante no verificado.</p>
                    </div>
                </template>

                @include('partials.modales.campos-incidencia', [
                    'contexto' => $contexto,
                    'comoAuxiliar' => $comoAuxiliar,
                    'prefijo' => 'no-inscrito',
                ])
            </div>

            {{-- Pie: mismo que el registro rápido. --}}
            <div class="border-t border-default pt-4 space-y-[2vh]">
                <button
                    type="button"
                    :disabled="! completo"
                    :class="completo ? 'text-white bg-danger border-transparent hover:bg-danger-strong cursor-pointer' : 'text-fg-disabled bg-disabled border-default-medium cursor-not-allowed'"
                    class="w-full inline-flex items-center justify-center box-border border focus:ring-4 focus:ring-danger-medium shadow-xs font-medium leading-5 rounded-base focus:outline-none px-4 py-2.5 text-sm"
                    @click="registrar()"
                >Registrar incidencia</button>
                <x-ui.button-cancelar class="w-full" @click="show = false">Cancelar</x-ui.button-cancelar>
            </div>
        </div>
    </div>
</div>
