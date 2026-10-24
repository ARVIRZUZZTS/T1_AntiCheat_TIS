{{--
    @file    horario-ingreso.blade.php
    @author  Alex Candia <alex.leonar.candia@gmail.com>
    @created 2026-10-23
    @updated 2026-10-23

    @description
    Pestaña "Reporte de Hora de ingreso". Es un parcial con su propio x-data:
    así la página de reportes solo conserva los selectores de materia/examen y
    cada reporte queda aislado en su archivo.

    Los ids del curso y del examen no se pasan como props porque cambian con
    Alpine. La página dispara `reporte-seleccion-actualizada` cuando cambia la
    selección y este parcial lo escucha en window. Al recibirlo consulta el
    endpoint; si el examen queda vacío solo limpia el estado.

    El reporte no tiene filtros ni resumen: solo muestra la tabla con el
    listado de ingresos del examen elegido.

    @changelog
    - 2026-10-23  [Alex Candia]  refactor: se extrae la pestaña de
      reportes.blade.php a un parcial con su propio estado de Alpine.
    - 2026-10-23  [Alex Candia]  fix: se eliminan los filtros por aula y rango
      horario; el reporte muestra todos los ingresos del examen.
    - 2026-10-23  [Alex Candia]  fix: se quitan el resumen de totales y primeros/últimos
      ingresos; solo se muestra el listado.

    @see  resources/views/pages/reportes.blade.php
    @see  App\Http\Controllers\Api\ReporteHorarioIngresoController
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
            return this.horario !== null && ! this.horario.sin_datos;
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

    <template x-if="horario && ! cargando && ! errorReporte">
        <div class="space-y-6">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div class="min-w-0">
                    <h2 class="text-lg font-semibold text-heading" x-text="'Ingresos de ' + horario.materia.nombre"></h2>
                    <p class="mt-1 text-sm text-body" x-text="horario.examen.tipo + (horario.examen.fecha_formateada ? ' - ' + horario.examen.fecha_formateada : '')"></p>
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

            <p x-show="horario.sin_datos" class="rounded-base border border-default bg-neutral-primary-soft p-8 text-center text-sm text-body">
                No hay ingresos registrados.
            </p>

            <div x-show="! horario.sin_datos" class="space-y-4">
                <x-ui.table :headers="['Nombres', 'Apellidos', 'Código SIS', 'Aula', 'Hora de ingreso']" min-width="min-w-[640px]">
                    <x-slot:body>
                        <template x-for="estudiante in horario.estudiantes" :key="estudiante.sis + estudiante.hora_ingreso">
                            <tr class="border-b border-default bg-neutral-primary last:border-0">
                                <td class="px-6 py-4" x-text="estudiante.nombres"></td>
                                <td class="px-6 py-4" x-text="estudiante.apellidos"></td>
                                <td class="px-6 py-4" x-text="estudiante.sis"></td>
                                <td class="px-6 py-4" x-text="estudiante.aula || 'Sin aula'"></td>
                                <td class="px-6 py-4 font-medium text-heading" x-text="estudiante.hora_ingreso || 'Sin registro'"></td>
                            </tr>
                        </template>
                    </x-slot:body>
                </x-ui.table>
            </div>
        </div>
    </template>
</div>
