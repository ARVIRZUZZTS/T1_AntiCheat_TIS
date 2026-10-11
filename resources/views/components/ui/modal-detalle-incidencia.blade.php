{{--
    @file    modal-detalle-incidencia.blade.php
    @author  OchoaCesar <cesareduardonick@gmail.com>
    @created 2026-10-11
    @updated 2026-10-11

    @description
    Modal de detalle de una incidencia de la central de riesgos con sus dos
    submodales de decisión (confirmar / rechazar), en un único componente
    Alpine. Se abre con el evento window 'abrir-detalle-incidencia' (lo
    despacha el botón "Ver detalle" de la tarjeta de notificación) y consulta
    GET /api/incidencias/{id} (DetalleIncidenciaController) con estados
    cargando, error y listo.

    El detalle muestra la ficha del estudiante (partial modales/ficha-
    estudiante), el pill de estado, las filas del hecho (partial
    modales/filas-detalle) y la descripción. Los botones de acción solo
    aparecen si la incidencia no está confirmada.

    Los submodales (z-[60]) confirman la decisión antes de ejecutar POST
    /api/incidencias/{id}/confirmar o /rechazar con el header X-CSRF-TOKEN; al
    completarse cierran submodal y detalle y muestran el toast de éxito con el
    mensaje del criterio. "Cancelar", Escape o un clic fuera del submodal
    cierran únicamente el submodal. Errores del servidor se muestran con
    x-ui.alert dentro del submodal, que permanece abierto.

    @changelog
    - 2026-10-11  [OchoaCesar]  feat: creación inicial del componente (detalle +
      submodales de confirmación y rechazo + toast).
    - 2026-10-11  [OchoaCesar]  fix: los submodales (z-[60]) y el toast (z-[70])
      quedaban detrás del detalle (z-50) porque el CSS compilado de Vite era
      anterior a estas utilidades arbitrarias; se reconstruyen los assets.
    - 2026-10-11  [OchoaCesar]  style: más espacio vertical entre los botones
      de los submodales (gap-4).
    - 2026-10-11  [OchoaCesar]  fix: el error del submodal no repetía el texto
      "No se pudo completar la operación" (título + cuerpo); el cuerpo ahora
      muestra el detalle del servidor o un mensaje distinto del título.
    - 2026-10-11  [OchoaCesar]  fix: comillas dobles dentro de un comentario del
      x-data cerraban el atributo HTML y volcaban JS crudo como texto en la
      página; los comentarios del x-data no pueden usar comillas dobles.

    @see  App\Http\Controllers\Api\DetalleIncidenciaController
    @see  App\Http\Controllers\Api\ResolverIncidenciaController
    @see  resources/views/pages/notificaciones.blade.php
    @see  resources/views/partials/modales/ficha-estudiante.blade.php
    @see  resources/views/partials/modales/filas-detalle.blade.php
--}}

@props([])

<div
    class="contents"
    x-data="{
        show: false,
        sub: null,
        id: null,
        detalle: {},
        estado: 'cargando',
        error: '',
        enviando: false,
        errorSub: '',
        toastVisible: false,
        toastMensaje: '',
        toastTemporizador: null,

        async abrir(evento) {
            if (!evento.detail || !evento.detail.id) {
                return;
            }
            this.id = evento.detail.id;
            this.detalle = {};
            this.sub = null;
            this.errorSub = '';
            this.show = true;
            await this.cargar();
        },

        async cargar() {
            this.estado = 'cargando';
            this.error = '';
            try {
                const url = '{{ route('api.incidencias.detalle', ['idIncidencia' => '__ID__']) }}'.replace('__ID__', this.id);
                const respuesta = await fetch(url, { headers: { 'Accept': 'application/json' } });
                if (!respuesta.ok) {
                    throw new Error('HTTP ' + respuesta.status);
                }
                const cuerpo = await respuesta.json();
                this.detalle = cuerpo.datos;
                this.estado = 'listo';
            } catch (e) {
                this.estado = 'error';
            }
        },

        estadoVisible() {
            return this.detalle.estado === 'Confirmado' ? 'Confirmado' : 'En revisión';
        },

        fechaDe() {
            const fecha = this.detalle.fecha_registro;
            if (!fecha) {
                return '—';
            }
            const partes = String(fecha).substring(0, 10).split('-');
            if (partes.length !== 3) {
                return String(fecha);
            }
            return partes[2] + '/' + partes[1] + '/' + partes[0];
        },

        async confirmar() {
            await this.resolver(
                '{{ route('api.incidencias.confirmar', ['idIncidencia' => '__ID__']) }}'.replace('__ID__', this.id),
                'Se ha añadido estudiante a la central de riesgos'
            );
        },

        async rechazar() {
            await this.resolver(
                '{{ route('api.incidencias.rechazar', ['idIncidencia' => '__ID__']) }}'.replace('__ID__', this.id),
                'Se ha rechazado la incidencia'
            );
        },

        async resolver(url, mensajeExito) {
            this.enviando = true;
            this.errorSub = '';
            try {
                const respuesta = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                    },
                });
                if (!respuesta.ok) {
                    const cuerpo = await respuesta.json().catch(() => ({}));
                    // El alert ya lleva un título fijo: el cuerpo solo agrega
                    // el detalle del servidor (mensaje o message) o un texto
                    // distinto, para no repetir el título.
                    this.errorSub = cuerpo.mensaje || cuerpo.message || 'Ocurrió un problema inesperado.';
                    return;
                }
                this.sub = null;
                this.show = false;
                this.mostrarToast(mensajeExito);
            } catch (e) {
                this.errorSub = 'No se pudo conectar con el servidor.';
            } finally {
                this.enviando = false;
            }
        },

        mostrarToast(mensaje) {
            this.toastMensaje = mensaje;
            this.toastVisible = true;
            // Flowbite oculta el toast con clases al usar su botón de cierre:
            // se limpian para poder mostrarlo de nuevo.
            const toast = document.getElementById('toast-incidencia');
            if (toast) {
                toast.classList.remove('hidden', 'opacity-0');
            }
            clearTimeout(this.toastTemporizador);
            this.toastTemporizador = setTimeout(() => {
                this.toastVisible = false;
            }, 5000);
        },
    }"
    @toast-exito.window="mostrarToast($event.detail.mensaje)"
>
    {{-- Toast de resultado de la acción. --}}
    <div x-show="toastVisible" x-cloak x-transition class="fixed top-4 right-4 z-[70]">
        <x-ui.toast id="toast-incidencia" variant="success">
            <span x-text="toastMensaje"></span>
        </x-ui.toast>
    </div>

    {{-- Modal de detalle. --}}
    <div
        x-show="show"
        x-cloak
        tabindex="-1"
        role="dialog"
        aria-modal="true"
        aria-labelledby="titulo-detalle-incidencia"
        @abrir-detalle-incidencia.window="abrir($event)"
        @click.self="if (!sub) show = false"
        @keydown.escape.window="if (!sub) show = false"
        class="overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 flex justify-center items-center w-full md:inset-0 h-full max-h-full bg-overlay-modal/50"
    >
        <div class="relative p-4 w-full max-w-md max-h-full">
            <div class="relative bg-neutral-primary-soft border border-default rounded-base shadow-sm p-4 md:p-6">
                {{-- Cabecera --}}
                <div class="flex items-start justify-between gap-3 border-b border-default pb-4 md:pb-5">
                    <h3 id="titulo-detalle-incidencia" class="text-lg font-medium text-heading">Detalle del registro</h3>
                    <button
                        type="button"
                        @click="show = false"
                        class="text-body bg-transparent hover:bg-neutral-tertiary hover:text-heading rounded-base text-sm w-9 h-9 ms-auto inline-flex justify-center items-center cursor-pointer"
                    >
                        <svg class="w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 17.94 6M18 18 6.06 6"/></svg>
                        <span class="sr-only">Cerrar modal</span>
                    </button>
                </div>

                {{-- Cargando --}}
                <div x-show="estado === 'cargando'" class="animate-pulse space-y-3 py-4" aria-hidden="true">
                    <div class="h-12 rounded-base bg-neutral-secondary-medium"></div>
                    <div class="h-24 rounded-base bg-neutral-secondary-medium"></div>
                    <div class="h-16 rounded-base bg-neutral-secondary-medium"></div>
                </div>

                {{-- Error de la consulta. --}}
                <div x-show="estado === 'error'" x-cloak class="py-4">
                    <x-ui.alert type="danger" title="No se pudo cargar la incidencia">
                        Ocurrió un problema al consultar el detalle.
                        <div class="mt-2">
                            <x-ui.button variant="secondary" @click="cargar()">Reintentar</x-ui.button>
                        </div>
                    </x-ui.alert>
                </div>

                {{-- Detalle. --}}
                <div x-show="estado === 'listo'" x-cloak class="space-y-4 py-4">
                    @include('partials.modales.ficha-estudiante', [
                        'nombre' => 'detalle.estudiante_nombre',
                        'sis' => 'detalle.sis_estudiante',
                        'tone' => 'riesgo',
                    ])

                    <div class="flex justify-end">
                        <span class="rounded-full border border-warning-subtle bg-warning-soft px-3 py-0.5 text-xs font-semibold text-fg-warning" x-text="estadoVisible()"></span>
                    </div>

                    @include('partials.modales.filas-detalle', [
                        'filas' => [
                            ['label' => 'Motivo', 'valor' => 'detalle.motivo || \'—\''],
                            ['label' => 'Materia de origen', 'valor' => 'detalle.materia || \'—\''],
                            ['label' => 'Reportado por', 'valor' => 'detalle.reportado_por || \'—\''],
                            ['label' => 'Fecha del hecho', 'valor' => 'fechaDe()'],
                        ],
                    ])

                    <div>
                        <label for="descripcion-incidencia" class="mb-1.5 block text-sm font-bold text-heading">
                            Descripción del hecho
                        </label>
                        <textarea
                            id="descripcion-incidencia"
                            rows="4"
                            readonly
                            :value="detalle.descripcion || ''"
                            class="w-full resize-none rounded-base border border-default bg-neutral-secondary-soft p-3 text-sm text-body focus:outline-none"
                        ></textarea>
                    </div>
                </div>

                {{-- Acciones: solo mientras la incidencia siga sin confirmar. --}}
                <div x-show="estado === 'listo' && detalle.estado !== 'Confirmado'" x-cloak class="mt-4 space-y-3 border-t border-default pt-4">
                    <x-ui.button variant="danger" class="w-full" @click="errorSub = ''; sub = 'confirmar'">
                        Confirmar
                    </x-ui.button>
                    <x-ui.button variant="default" class="w-full" @click="errorSub = ''; sub = 'rechazar'">
                        Rechazar
                    </x-ui.button>
                </div>
            </div>
        </div>
    </div>

    {{-- Submodal: confirmar ingreso a la central de riesgos. --}}
    <div
        x-show="sub === 'confirmar'"
        x-cloak
        tabindex="-1"
        role="dialog"
        aria-modal="true"
        aria-labelledby="titulo-confirmar-incidencia"
        @click.self="sub = null"
        @keydown.escape.window="sub = null"
        class="overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-[60] flex justify-center items-center w-full md:inset-0 h-full max-h-full bg-overlay-modal/50"
    >
        <div class="relative p-4 w-full max-w-sm max-h-full">
            <div class="relative bg-neutral-primary-soft border border-default rounded-base shadow-sm p-6 text-center">
                <p id="titulo-confirmar-incidencia" class="text-sm font-medium text-heading">
                    ¿Está seguro que quiere ingresar este estudiante a la central de riesgos?
                </p>

                <div x-show="errorSub" x-cloak class="mt-4 text-left">
                    <x-ui.alert type="danger" title="No se pudo completar la operación">
                        <span x-text="errorSub"></span>
                    </x-ui.alert>
                </div>

                <div class="mt-5 flex flex-col gap-4">
                    <x-ui.button variant="default" class="w-full" x-bind:disabled="enviando" @click="confirmar()">
                        Confirmar Incidencia
                    </x-ui.button>
                    <x-ui.button-cancelar class="w-full" @click="sub = null">
                        Cancelar
                    </x-ui.button-cancelar>
                </div>
            </div>
        </div>
    </div>

    {{-- Submodal: rechazar la incidencia. --}}
    <div
        x-show="sub === 'rechazar'"
        x-cloak
        tabindex="-1"
        role="dialog"
        aria-modal="true"
        aria-labelledby="titulo-rechazar-incidencia"
        @click.self="sub = null"
        @keydown.escape.window="sub = null"
        class="overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-[60] flex justify-center items-center w-full md:inset-0 h-full max-h-full bg-overlay-modal/50"
    >
        <div class="relative p-4 w-full max-w-sm max-h-full">
            <div class="relative bg-neutral-primary-soft border border-default rounded-base shadow-sm p-6 text-center">
                <p id="titulo-rechazar-incidencia" class="text-sm font-medium text-heading">
                    ¿Está seguro que quiere rechazar esta incidencia?
                </p>

                <div x-show="errorSub" x-cloak class="mt-4 text-left">
                    <x-ui.alert type="danger" title="No se pudo completar la operación">
                        <span x-text="errorSub"></span>
                    </x-ui.alert>
                </div>

                <div class="mt-5 flex flex-col gap-4">
                    <x-ui.button variant="danger" class="w-full" x-bind:disabled="enviando" @click="rechazar()">
                        Rechazar
                    </x-ui.button>
                    <x-ui.button-cancelar class="w-full" @click="sub = null">
                        Cancelar
                    </x-ui.button-cancelar>
                </div>
            </div>
        </div>
    </div>
</div>
