{{--
    @file    ingresos-ilegales.blade.php
    @author  Alex Candia <alex.leonar.candia@gmail.com>
    @created 2026-10-23
    @updated 2026-10-23

    @description
    Pestaña "Reporte de intentos no autorizados". Parcial con su propio x-data
    que escucha el evento global de selección de materia/examen. Se unifica
    con el reporte de horario usando el mismo componente de tabla y scroll
    horizontal. Se mantienen los resúmenes.

    @see  resources/views/pages/reportes.blade.php
--}}
<div
    x-data="{
        idCurso: null,
        idExamen: null,
        endpoint: @js(route('reportes.ingresos-ilegales')),
        endpointExportar: @js(route('reportes.ingresos-ilegales.exportar', ['examen' => '__EXAMEN__'])),

        ingresos: [],
        cargando: false,
        errorReporte: '',
        cache: {},
        abortController: null,
        mostrarPrueba: @js(config('app.debug') || request()->has('pruebas_reporte')),
        examenInfo: null,

        get hayDatos() {
            if (this.mostrarPrueba && typeof localStorage !== 'undefined' && localStorage.getItem('reportes_pruebas') !== 'off') {
                return this.datosPrueba().length > 0;
            }
            return this.ingresos.length > 0;
        },

        datosPrueba() {
            if (! this.mostrarPrueba) {
                return [];
            }
            if (typeof localStorage !== 'undefined' && localStorage.getItem('reportes_pruebas') === 'off') {
                return [];
            }
            return [
                { sis: '20180001', nombre: 'Juan Carlos', apellido: 'Pérez López de Mendoza', estudiante: 'Juan Carlos Pérez López de Mendoza', aula: 'Aula 101', motivo: 'Intento sin estar inscrito', tipo: 'No autorizado', estado: 'Registrado', fecha: new Date().toISOString().replace('T',' ').slice(0,16), materia: 'Materia de Prueba' },
                { sis: '20180002', nombre: 'María Fernanda', apellido: 'González Rodríguez', estudiante: 'María Fernanda González Rodríguez', aula: 'Aula con Nombre Extremadamente Largo', motivo: 'Intento sin estar inscrito', tipo: 'No autorizado', estado: 'Registrado', fecha: new Date(Date.now()-3600000).toISOString().replace('T',' ').slice(0,16), materia: 'Materia de Prueba' },
                { sis: '20180001', nombre: 'Juan Carlos', apellido: 'Pérez López de Mendoza', estudiante: 'Juan Carlos Pérez López de Mendoza', aula: 'Aula 101', motivo: 'Intento sin estar inscrito', tipo: 'No autorizado', estado: 'Registrado', fecha: new Date(Date.now()-7200000).toISOString().replace('T',' ').slice(0,16), materia: 'Materia de Prueba' },
            ];
        },

        get fuente() {
            if (this.mostrarPrueba && typeof localStorage !== 'undefined' && localStorage.getItem('reportes_pruebas') !== 'off') {
                return this.datosPrueba();
            }
            return this.ingresos;
        },

        get totalIntentos() {
            return this.fuente.length;
        },

        get estudiantesDistintos() {
            return new Set(this.fuente.map(r => r.sis)).size;
        },

        actualizarSeleccion(evento) {
            const detalle = evento && evento.detail ? evento.detail : {};
            this.idCurso = detalle.idCurso ?? null;
            this.idExamen = detalle.idExamen ?? null;
            this.errorReporte = '';

            if (! this.idCurso || ! this.idExamen) {
                if (this.abortController) {
                    this.abortController.abort();
                    this.abortController = null;
                }
                this.ingresos = [];
                this.cargando = false;
                return;
            }

            const clave = this.idCurso + '|' + this.idExamen;
            if (this.cache[clave]) {
                if (this.abortController) {
                    this.abortController.abort();
                    this.abortController = null;
                }
                this.ingresos = this.cache[clave];
                this.cargando = false;
                return;
            }

            this.ingresos = [];
            this.cargar();
        },

        async cargar() {
            if (! this.idCurso || ! this.idExamen) {
                return;
            }

            const curso = this.idCurso;
            const examen = this.idExamen;

            if (this.abortController) {
                this.abortController.abort();
            }
            const controller = new AbortController();
            this.abortController = controller;

            this.cargando = true;
            this.errorReporte = '';

            try {
                const url = this.endpoint + '?examen=' + encodeURIComponent(examen) + '&curso=' + encodeURIComponent(curso);
                const response = await fetch(url, {
                    headers: { Accept: 'application/json' },
                    credentials: 'same-origin',
                    signal: controller.signal
                });

                if (! response.ok) {
                    throw new Error('HTTP ' + response.status);
                }

                const datos = await response.json();

                if (this.abortController === controller && this.idCurso === curso && this.idExamen === examen) {
                    const registros = Array.isArray(datos) ? datos : [];
                    this.ingresos = registros;
                    this.cache[curso + '|' + examen] = registros;
                }
            } catch (error) {
                if (error && error.name === 'AbortError') {
                    return;
                }
                if (this.abortController === controller && this.idCurso === curso && this.idExamen === examen) {
                    this.ingresos = [];
                    this.errorReporte = 'No se pudo cargar el reporte. Intenta nuevamente.';
                }
            } finally {
                if (this.abortController === controller) {
                    this.cargando = false;
                    this.abortController = null;
                }
            }
        },

        exportar() {
            if (! this.hayDatos) {
                return;
            }

            const url = this.endpointExportar.replace('__EXAMEN__', this.idExamen) + '?curso=' + encodeURIComponent(this.idCurso);
            window.location.href = url;
        }
    }"
    x-on:reporte-seleccion-actualizada.window="actualizarSeleccion($event)"
    class="space-y-6"
>
    <template x-if="!idCurso || !idExamen">
        <p class="rounded-base border border-default bg-neutral-primary-soft p-6 text-center text-sm text-body">Seleccione materia y examen para ver el reporte.</p>
    </template>

    <template x-if="idCurso && idExamen">
        <div class="space-y-6">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <x-ui.stat-card label="Total de intentos no autorizados" tone="brand">
                    <x-slot:metric><span x-text="totalIntentos"></span></x-slot:metric>
                </x-ui.stat-card>
                <x-ui.stat-card label="Estudiantes distintos" tone="success">
                    <x-slot:metric><span x-text="estudiantesDistintos"></span></x-slot:metric>
                    <p class="mt-1 text-xs text-body">que intentaron ingresar sin estar inscritos</p>
                </x-ui.stat-card>
            </div>

            <div class="flex flex-wrap items-start justify-between gap-3">
                <div class="min-w-0">
                    <h2 class="text-lg font-semibold text-heading" x-text="'Intentos de ingreso no autorizados - Materia'"></h2>
                    <p class="mt-1 text-sm text-body">Examen · Fecha - Estudiantes que intentaron ingresar sin estar inscritos.</p>
                </div>

                <x-ui.button
                    variant="success"
                    @click="exportar()"
                    x-bind:disabled="! hayDatos"
                    x-bind:class="hayDatos ? '' : 'opacity-50 cursor-not-allowed'"
                >
                    Exportar a Excel
                </x-ui.button>
            </div>

    <p x-show="cargando" x-cloak class="rounded-base border border-default bg-neutral-primary-soft p-6 text-center text-sm text-body">
        Cargando reporte...
    </p>

    <x-ui.alert x-show="errorReporte" x-cloak type="danger">
        <span x-text="errorReporte"></span>
    </x-ui.alert>

            <div x-show="! cargando && ! errorReporte">
                <p x-show="fuente.length === 0" class="rounded-base border border-default bg-neutral-primary-soft p-8 text-center text-sm text-body">
                    No hay intentos de ingreso no autorizados.
                </p>

                <div x-show="fuente.length > 0" class="overflow-x-auto">
                    <x-ui.table :headers="['Nombres', 'Apellidos', 'Código SIS', 'Aula', 'Fecha y hora del intento']" min-width="min-w-[720px]">
                        <x-slot:body>
                            <template x-for="registro in [...fuente].sort((a,b)=> (b.fecha||'').localeCompare(a.fecha||''))" :key="registro.sis + registro.fecha + Math.random()">
                                <tr class="border-b border-default bg-neutral-primary last:border-0">
                                    <td class="px-6 py-4" x-text="registro.nombre"></td>
                                    <td class="px-6 py-4" x-text="registro.apellido"></td>
                                    <td class="px-6 py-4 font-mono" x-text="registro.sis"></td>
                                    <td class="px-6 py-4" x-text="registro.aula"></td>
                                    <td class="px-6 py-4" x-text="registro.fecha"></td>
                                </tr>
                            </template>
                        </x-slot:body>
                    </x-ui.table>
                </div>
            </div>
        </div>
    </template>
</div>
