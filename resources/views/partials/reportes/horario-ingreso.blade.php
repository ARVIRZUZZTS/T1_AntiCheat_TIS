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
    Alpine. La página dispara el evento `reporte-horario-actualizar` cuando
    cambia el examen y este parcial lo escucha en window. Al recibirlo reinicia
    filtros y consulta el endpoint; si el examen queda vacío solo limpia el
    estado.

    @changelog
    - 2026-10-23  [Alex Candia]  refactor: se extrae la pestaña de
      reportes.blade.php a un parcial con su propio estado de Alpine.

    @see  resources/views/pages/reportes.blade.php
    @see  App\Http\Controllers\Api\ReporteHorarioIngresoController
--}}

<div
    x-data="{
        idCurso: null,
        idExamen: null,
        endpoint: @js(route('api.reportes.horarios-ingreso')),
        endpointExportar: @js(route('api.reportes.horarios-ingreso.exportar')),

        aulaId: '',
        horaDesde: '',
        horaHasta: '',
        horario: null,
        cargando: false,
        errorReporte: '',

        get aulas() {
            return this.horario ? this.horario.aulas : [];
        },

        get hayDatos() {
            return this.horario !== null && ! this.horario.sin_datos;
        },

        actualizarDesdeFiltros(evento) {
            this.idCurso = evento.detail.idCurso;
            this.idExamen = evento.detail.idExamen;
            this.aulaId = '';
            this.horaDesde = '';
            this.horaHasta = '';
            this.horario = null;
            this.errorReporte = '';

            if (this.idCurso && this.idExamen) {
                this.cargar();
            }
        },

        parametros() {
            const parametros = new URLSearchParams();
            parametros.set('id_curso', this.idCurso);
            parametros.set('id_examen', this.idExamen);

            if (this.aulaId) { parametros.set('id_aula', this.aulaId); }
            if (this.horaDesde) { parametros.set('hora_desde', this.horaDesde); }
            if (this.horaHasta) { parametros.set('hora_hasta', this.horaHasta); }

            return parametros;
        },

        async cargar() {
            if (! this.idCurso || ! this.idExamen) { return; }

            this.cargando = true;
            this.errorReporte = '';

            try {
                const respuesta = await fetch(this.endpoint + '?' + this.parametros().toString(), {
                    headers: { Accept: 'application/json' },
                    credentials: 'same-origin'
                });

                if (! respuesta.ok) {
                    throw new Error('HTTP ' + respuesta.status);
                }

                const payload = await respuesta.json();
                this.horario = payload.datos;
            } catch (error) {
                this.horario = null;
                this.errorReporte = 'No se pudo cargar el reporte. Intenta nuevamente.';
            } finally {
                this.cargando = false;
            }
        },

        limpiarFiltros() {
            this.aulaId = '';
            this.horaDesde = '';
            this.horaHasta = '';
            this.cargar();
        },

        exportar() {
            if (! this.hayDatos) { return; }

            window.location.href = this.endpointExportar + '?' + this.parametros().toString();
        }
    }"
    x-on:reporte-horario-actualizar.window="actualizarDesdeFiltros($event)"
    class="space-y-6"
>
    <div class="rounded-base border border-default bg-neutral-primary-soft p-4 shadow-xs sm:p-6">
        <form @submit.prevent="cargar()" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <label for="aula_horario" class="block mb-2.5 text-sm font-medium text-heading">Aula</label>
                <select
                    name="id_aula"
                    id="aula_horario"
                    x-model="aulaId"
                    class="block w-full px-3 py-2.5 border text-sm rounded-base shadow-xs placeholder:text-body bg-neutral-secondary-medium border-default-medium text-heading focus:ring-brand focus:border-brand"
                >
                    <option value="">Todas las aulas</option>
                    <template x-for="aula in aulas" :key="aula.id">
                        <option :value="aula.id" x-text="aula.nombre"></option>
                    </template>
                </select>
            </div>

            <x-ui.input label="Desde" name="hora_desde" id="hora_desde" type="time" x-model="horaDesde" />
            <x-ui.input label="Hasta" name="hora_hasta" id="hora_hasta" type="time" x-model="horaHasta" />

            <div class="flex flex-col gap-2 sm:col-span-2 sm:flex-row sm:items-end lg:col-span-1 lg:flex-col lg:items-stretch">
                <x-ui.button type="submit" class="w-full">Aplicar filtros</x-ui.button>
                <x-ui.button type="button" variant="secondary" class="w-full" @click="limpiarFiltros()">Limpiar</x-ui.button>
            </div>
        </form>
    </div>

    <p x-show="cargando" x-cloak class="rounded-base border border-default bg-neutral-primary-soft p-6 text-center text-sm text-body">
        Cargando reporte...
    </p>

    <x-ui.alert x-show="errorReporte" x-cloak type="danger">
        <span x-text="errorReporte"></span>
    </x-ui.alert>

    <template x-if="horario && ! cargando">
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

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div class="flex items-center justify-between gap-3 rounded-base border border-default bg-neutral-primary-soft p-4 shadow-xs sm:p-6">
                    <div class="min-w-0">
                        <p class="truncate text-xl font-semibold text-fg-brand sm:text-3xl" x-text="horario.resumen.total"></p>
                        <p class="truncate text-sm text-body">Estudiantes ingresados</p>
                    </div>
                    <span class="flex size-10 shrink-0 items-center justify-center rounded-base bg-neutral-secondary-soft text-fg-brand sm:size-12">
                        <svg class="h-5 w-5 sm:h-6 sm:w-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 19h4a1 1 0 0 0 1-1v-1a3 3 0 0 0-3-3h-2m-2-4h2a3 3 0 0 1 3 3v2a1 1 0 0 1-1 1h-4m-3-9a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm-2 8H6a3 3 0 0 0-3 3v1a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-1a3 3 0 0 0-3-3h-3Z"/></svg>
                    </span>
                </div>

                <div class="flex items-center justify-between gap-3 rounded-base border border-default bg-neutral-primary-soft p-4 shadow-xs sm:p-6">
                    <div class="min-w-0">
                        <p class="truncate text-xl font-semibold text-fg-success sm:text-3xl" x-text="horario.resumen.primer_ingreso || 'Sin registros'"></p>
                        <p class="truncate text-sm text-body">Primer ingreso</p>
                    </div>
                    <span class="flex size-10 shrink-0 items-center justify-center rounded-base bg-neutral-secondary-soft text-fg-success sm:size-12">
                        <svg class="h-5 w-5 sm:h-6 sm:w-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                    </span>
                </div>

                <div class="flex items-center justify-between gap-3 rounded-base border border-default bg-neutral-primary-soft p-4 shadow-xs sm:p-6">
                    <div class="min-w-0">
                        <p class="truncate text-xl font-semibold text-fg-warning sm:text-3xl" x-text="horario.resumen.ultimo_ingreso || 'Sin registros'"></p>
                        <p class="truncate text-sm text-body">Último ingreso</p>
                    </div>
                    <span class="flex size-10 shrink-0 items-center justify-center rounded-base bg-neutral-secondary-soft text-fg-warning sm:size-12">
                        <svg class="h-5 w-5 sm:h-6 sm:w-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                    </span>
                </div>
            </div>

            <p x-show="horario.sin_datos" class="rounded-base border border-default bg-neutral-primary-soft p-8 text-center text-sm text-body">
                No hay ingresos registrados.
            </p>

            <div x-show="! horario.sin_datos" class="space-y-4">
                <div class="space-y-3 sm:hidden">
                    <template x-for="estudiante in horario.estudiantes" :key="estudiante.sis + estudiante.hora_ingreso">
                        <article class="rounded-xl border border-default bg-neutral-primary-soft p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="break-words text-sm font-bold text-fg-brand" x-text="estudiante.apellidos + ', ' + estudiante.nombres"></p>
                                    <p class="mt-1 break-words text-xs text-body" x-text="estudiante.sis + ' - ' + (estudiante.aula || 'Sin aula')"></p>
                                </div>
                                <span class="shrink-0 rounded-base bg-neutral-secondary-soft px-2 py-1 text-xs font-semibold text-heading" x-text="estudiante.hora_ingreso || 'Sin registro'"></span>
                            </div>
                        </article>
                    </template>
                </div>

                <div class="hidden overflow-x-auto rounded-base border border-default bg-neutral-primary-soft shadow-xs sm:block">
                    <table class="w-full min-w-[640px] text-left text-sm text-body">
                        <thead class="border-b border-default bg-neutral-secondary-soft text-sm text-body">
                            <tr>
                                <th scope="col" class="px-6 py-3 font-medium">Nombres</th>
                                <th scope="col" class="px-6 py-3 font-medium">Apellidos</th>
                                <th scope="col" class="px-6 py-3 font-medium">Código SIS</th>
                                <th scope="col" class="px-6 py-3 font-medium">Aula</th>
                                <th scope="col" class="px-6 py-3 font-medium">Hora de ingreso</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="estudiante in horario.estudiantes" :key="estudiante.sis + estudiante.hora_ingreso">
                                <tr class="border-b border-default last:border-0">
                                    <td class="px-6 py-4" x-text="estudiante.nombres"></td>
                                    <td class="px-6 py-4" x-text="estudiante.apellidos"></td>
                                    <td class="px-6 py-4" x-text="estudiante.sis"></td>
                                    <td class="px-6 py-4" x-text="estudiante.aula || 'Sin aula'"></td>
                                    <td class="px-6 py-4 font-medium text-heading" x-text="estudiante.hora_ingreso || 'Sin registro'"></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </template>
</div>
