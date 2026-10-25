{{--
    @file    horario-ingreso.blade.php
    @author  Alex Candia <alex.leonar.candia@gmail.com>
    @created 2026-10-23
    @updated 2026-10-25

    @description
    Pestaña "Reporte de horarios de ingreso". Solo muestra tabla con
    nombres, apellidos, código SIS, aula, hora de ingreso y hora de salida.
    Orden por hora de ingreso del más temprano al más tardío.
--}}

<div
    x-data="{
        idCurso: null,
        idExamen: null,
        endpoint: @js(route('api.reportes.horarios-ingreso')),
        endpointExportar: @js(route('api.reportes.horarios-ingreso.exportar')),

        horario: null,
        cargando: false,
        errorReporte: '',
        cache: {},
        abortController: null,

        get hayDatos() {
            return this.horario !== null && this.horario && ! this.horario.sin_datos && Array.isArray(this.horario.estudiantes) && this.horario.estudiantes.length > 0;
        },

        actualizarSeleccion(evento) {
            const detalle = evento && evento.detail ? evento.detail : {};
            this.idCurso = detalle.idCurso ?? null;
            this.idExamen = detalle.idExamen ?? null;
            this.errorReporte = '';

            if (this.idCurso === undefined || this.idCurso === '' || this.idExamen === undefined || this.idExamen === '') {
                if (this.abortController) {
                    this.abortController.abort();
                    this.abortController = null;
                }
                this.horario = null;
                this.cargando = false;
                return;
            }

            const clave = this.idCurso + '|' + this.idExamen;
            if (this.cache[clave]) {
                if (this.abortController) {
                    this.abortController.abort();
                    this.abortController = null;
                }
                this.horario = this.cache[clave];
                this.cargando = false;
                return;
            }

            this.horario = null;
            this.cargar();
        },

        parametros() {
            const parametros = new URLSearchParams();
            parametros.set('id_curso', this.idCurso);
            parametros.set('id_examen', this.idExamen);

            return parametros;
        },

        async cargar() {
            if (! this.idCurso || ! this.idExamen) { return; }

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
                const respuesta = await fetch(this.endpoint + '?' + new URLSearchParams({ id_curso: curso, id_examen: examen }).toString(), {
                    headers: { Accept: 'application/json' },
                    credentials: 'same-origin',
                    signal: controller.signal
                });

                if (! respuesta.ok) {
                    throw new Error('HTTP ' + respuesta.status);
                }

                const payload = await respuesta.json();

                if (this.abortController === controller && this.idCurso === curso && this.idExamen === examen) {
                    this.horario = payload.datos;
                    this.cache[curso + '|' + examen] = payload.datos;
                }
            } catch (error) {
                if (error && error.name === 'AbortError') {
                    return;
                }
                if (this.abortController === controller && this.idCurso === curso && this.idExamen === examen) {
                    this.horario = null;
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
            if (! this.hayDatos) { return; }

            window.location.href = this.endpointExportar + '?' + this.parametros().toString();
        }
    }"
    x-on:reporte-seleccion-actualizada.window="actualizarSeleccion($event)"
    class="space-y-6"
>
    <p x-show="cargando" x-cloak class="rounded-base border border-default bg-neutral-primary-soft p-6 text-center text-sm text-body">
        Cargando reporte...
    </p>

    <x-ui.alert x-show="errorReporte && ! cargando" x-cloak type="danger">
        <span x-text="errorReporte"></span>
    </x-ui.alert>

        <template x-if="!cargando && !errorReporte && horario">
            <div class="space-y-6">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2" x-show="!horario.sin_datos && Array.isArray(horario.estudiantes) && horario.estudiantes.length > 0">
                    <x-ui.stat-card label="Primer ingreso" tone="brand">
                        <x-slot:metric><span x-text="(horario && horario.resumen && horario.resumen.primer_ingreso) ? horario.resumen.primer_ingreso : '-'"></span></x-slot:metric>
                    </x-ui.stat-card>
                    <x-ui.stat-card label="Último ingreso" tone="brand">
                        <x-slot:metric><span x-text="(horario && horario.resumen && horario.resumen.ultimo_ingreso) ? horario.resumen.ultimo_ingreso : '-'"></span></x-slot:metric>
                    </x-ui.stat-card>
                </div>

                <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-start sm:justify-between">
                    <div class="min-w-0">
                        <h2 class="text-lg font-semibold text-heading" x-text="'Horarios de ingreso - ' + (horario.materia ? horario.materia.nombre : '')"></h2>
                        <p class="mt-1 text-sm text-body" x-text="(horario.examen ? horario.examen.tipo : '') + (horario.examen && horario.examen.fecha_formateada ? ' · ' + horario.examen.fecha_formateada : '')"></p>
                    </div>

                    <x-ui.button
                        variant="success"
                        @click="exportar()"
                        class="w-full sm:w-auto"
                        x-bind:disabled="! hayDatos"
                        x-bind:class="hayDatos ? 'w-full sm:w-auto' : 'w-full sm:w-auto opacity-50 cursor-not-allowed'"
                    >
                        Exportar a Excel
                    </x-ui.button>
                </div>

                <p x-show="horario.sin_datos || !Array.isArray(horario.estudiantes) || horario.estudiantes.length === 0" class="rounded-base border border-default bg-neutral-primary-soft p-8 text-center text-sm text-body">
                    No hay ingresos registrados.
                </p>

                <div x-show="!horario.sin_datos && Array.isArray(horario.estudiantes) && horario.estudiantes.length > 0" class="space-y-4 overflow-x-auto">
                    <x-ui.table :headers="['Nombres', 'Apellidos', 'Código SIS', 'Aula', 'Hora de ingreso', 'Hora de salida']" min-width="min-w-[720px]">
                        <x-slot:body>
                            <template x-for="estudiante in [...horario.estudiantes].sort((a,b)=>((a.hora_ingreso||'').localeCompare(b.hora_ingreso||'')))" :key="estudiante.sis + estudiante.hora_ingreso">
                                <tr class="border-b border-default bg-neutral-primary last:border-0">
                                    <td class="px-6 py-4" x-text="estudiante.nombres"></td>
                                    <td class="px-6 py-4" x-text="estudiante.apellidos"></td>
                                    <td class="px-6 py-4 font-mono" x-text="estudiante.sis"></td>
                                    <td class="px-6 py-4" x-text="estudiante.aula || 'Sin aula'"></td>
                                    <td class="px-6 py-4 font-medium text-heading" x-text="estudiante.hora_ingreso || 'Sin registro'"></td>
                                    <td class="px-6 py-4 text-heading" x-text="(estudiante.hora_salida && estudiante.hora_salida !== '') ? estudiante.hora_salida : '-'"></td>
                                </tr>
                            </template>
                        </x-slot:body>
                    </x-ui.table>
                </div>
            </div>
        </template>
</div>
