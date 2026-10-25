{{--
    @file    asistencia.blade.php
    @author  AI Assistant
    @created 2026-10-24

    @description
    Pestaña "Reporte de asistencia" (HU-7). Parcial con su propio x-data
    que escucha el evento global de selección de materia/examen.
--}}
<div
    x-data="{
        idCurso: null,
        idExamen: null,
        endpoint: @js(route('api.examenes.monitoreo.asistencia', ['idExamen' => '__EXAMEN__'])),
        endpointExportar: @js(route('reportes.asistencia.exportar', ['examen' => '__EXAMEN__'])),
        datos: null,
        cargando: false,
        errorReporte: '',
        cache: {},
        abortController: null,
        filtroAula: '',
        filtroDocente: '',
        mostrarPrueba: @js(config('app.debug') || request()->has('pruebas_reporte')),

        get hayDatos() {
            if (this.mostrarPrueba && this.datosPrueba) {
                return true;
            }
            return this.datos && Array.isArray(this.datos.estudiantes) && this.datos.estudiantes.length > 0;
        },

        get datosPrueba() {
            if (! this.mostrarPrueba) {
                return null;
            }
            if (localStorage.getItem('reportes_pruebas') === 'off') {
                return null;
            }
            return {
                examen: { tipo_examen: { nombre_tipo_examen: 'Examen Parcial' }, fecha: new Date().toISOString().slice(0,10), hora_inicio: '08:00' },
                estudiantes: [
                    { sis: '20180001', nombre: 'Juan Carlos', apellido: 'Pérez López de Mendoza', aula: 'Aula 101 - Edificio A', hora_ingreso: '08:05', estado_asistencia: 'presente', registrador: { nombre_usuario: 'Docente', apellido: 'Titular' } },
                    { sis: '20180002', nombre: 'María Fernanda', apellido: 'González Rodríguez', aula: 'Aula 101 - Edificio A', hora_ingreso: null, estado_asistencia: 'pendiente', registrador: null },
                    { sis: '20180003', nombre: 'Luis Alberto', apellido: 'Quispe Mamani', aula: 'Aula 102 - Edificio B', hora_ingreso: '08:12', estado_asistencia: 'presente', registrador: { nombre_usuario: 'Auxiliar', apellido: 'de Laboratorio' } },
                    { sis: '99999999', nombre: 'Nombre Extremadamente Largo Para Probar', apellido: 'Apellido También Muy Largo Que Podría Romper El Diseño', aula: 'Aula con Nombre Extremadamente Largo Para Pruebas', hora_ingreso: null, estado_asistencia: 'pendiente', registrador: null },
                ]
            };
        },

        get fuente() {
            return this.mostrarPrueba && this.datosPrueba ? this.datosPrueba : this.datos;
        },

        get estudiantes() {
            const f = this.fuente;
            if (! f || ! Array.isArray(f.estudiantes)) return [];
            let lista = f.estudiantes;
            if (this.filtroAula) {
                lista = lista.filter(e => e.aula === this.filtroAula);
            }
            if (this.filtroDocente) {
                lista = lista.filter(e => {
                    const reg = e.registrador ? (e.registrador.nombre_usuario + ' ' + e.registrador.apellido).toLowerCase() : '';
                    return reg.includes(this.filtroDocente.toLowerCase());
                });
            }
            return lista;
        },

        get registrados() {
            return this.estudiantes.length;
        },

        get presentes() {
            return this.estudiantes.filter(e => e.estado_asistencia === 'presente').length;
        },

        get ausentes() {
            return this.estudiantes.filter(e => e.estado_asistencia !== 'presente').length;
        },

        get aulas() {
            const f = this.fuente;
            if (! f || ! Array.isArray(f.estudiantes)) return [];
            return [...new Set(f.estudiantes.map(e => e.aula).filter(Boolean))];
        },

        get docentes() {
            const f = this.fuente;
            if (! f || ! Array.isArray(f.estudiantes)) return [];
            const set = new Set();
            f.estudiantes.forEach(e => {
                if (e.registrador) {
                    set.add((e.registrador.nombre_usuario + ' ' + e.registrador.apellido));
                }
            });
            return [...set];
        },

        get resumenPorAula() {
            const res = {};
            this.estudiantes.forEach(e => {
                const aula = e.aula || 'Sin aula';
                if (! res[aula]) {
                    res[aula] = { aula, docente: e.registrador ? (e.registrador.nombre_usuario + ' ' + e.registrador.apellido) : 'Sin registro', registrados: 0, presentes: 0, ausentes: 0 };
                }
                res[aula].registrados++;
                if (e.estado_asistencia === 'presente') res[aula].presentes++;
                else res[aula].ausentes++;
            });
            return Object.values(res);
        },

        get listaAusentes() {
            return this.estudiantes.filter(e => e.estado_asistencia !== 'presente');
        },

        get listaIngresados() {
            return this.estudiantes.filter(e => e.estado_asistencia === 'presente');
        },

        get nombreMateria() {
            const f = this.fuente;
            return f && f.examen && f.examen.curso ? f.examen.curso.nombre_curso : (f && f.materia ? f.materia.nombre : 'Materia');
        },

        get nombreExamen() {
            const f = this.fuente;
            if (! f || ! f.examen) return '';
            const tipo = f.examen.tipo_examen ? f.examen.tipo_examen.nombre_tipo_examen : (f.examen.tipo || '');
            return tipo;
        },

        get fechaExamen() {
            const f = this.fuente;
            return f && f.examen ? (f.examen.fecha || '') : '';
        },

        actualizarSeleccion(evento) {
            const detalle = evento && evento.detail ? evento.detail : {};
            this.idCurso = detalle.idCurso ?? null;
            this.idExamen = detalle.idExamen ?? null;
            this.errorReporte = '';
            this.filtroAula = '';
            this.filtroDocente = '';

            if (! this.idCurso || ! this.idExamen) {
                if (this.abortController) {
                    this.abortController.abort();
                    this.abortController = null;
                }
                this.datos = null;
                this.cargando = false;
                return;
            }

            const clave = this.idCurso + '|' + this.idExamen;
            if (this.cache[clave]) {
                if (this.abortController) {
                    this.abortController.abort();
                    this.abortController = null;
                }
                this.datos = this.cache[clave];
                this.cargando = false;
                return;
            }

            this.datos = null;
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
                const url = this.endpoint.replace('__EXAMEN__', examen);
                const response = await fetch(url, {
                    headers: { Accept: 'application/json' },
                    credentials: 'same-origin',
                    signal: controller.signal
                });

                if (! response.ok) {
                    throw new Error('HTTP ' + response.status);
                }

                const payload = await response.json();

                if (this.abortController === controller && this.idCurso === curso && this.idExamen === examen) {
                    this.datos = payload;
                    this.cache[curso + '|' + examen] = payload;
                }
            } catch (error) {
                if (error && error.name === 'AbortError') {
                    return;
                }
                if (this.abortController === controller && this.idCurso === curso && this.idExamen === examen) {
                    this.datos = null;
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
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <x-ui.stat-card label="Estudiantes registrados" tone="brand">
                    <x-slot:metric><span x-text="registrados"></span></x-slot:metric>
                </x-ui.stat-card>
                <x-ui.stat-card label="Presentes (ingresaron)" tone="success">
                    <x-slot:metric><span x-text="presentes"></span></x-slot:metric>
                </x-ui.stat-card>
                <x-ui.stat-card label="Ausentes (sin registro de ingreso)" tone="danger">
                    <x-slot:metric><span x-text="ausentes"></span></x-slot:metric>
                </x-ui.stat-card>
            </div>

            <div class="flex flex-wrap items-start justify-between gap-3">
                <div class="min-w-0">
                    <h2 class="text-lg font-semibold text-heading" x-text="'Asistencia - ' + nombreMateria"></h2>
                    <p class="mt-1 text-sm text-body"><span x-text="nombreExamen"></span> · <span x-text="fechaExamen"></span></p>
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

            <div class="flex flex-wrap gap-3">
                <div class="w-full sm:w-auto">
                    <label class="block mb-1.5 text-sm text-body">Filtrar por aula</label>
                    <select x-model="filtroAula" class="block px-3 py-2.5 border text-sm rounded-base bg-neutral-secondary-medium border-default-medium text-heading">
                        <option value="">Todas las aulas</option>
                        <template x-for="a in aulas" :key="a">
                            <option :value="a" x-text="a"></option>
                        </template>
                    </select>
                </div>
                <div class="w-full sm:w-auto">
                    <label class="block mb-1.5 text-sm text-body">Filtrar por docente</label>
                    <select x-model="filtroDocente" class="block px-3 py-2.5 border text-sm rounded-base bg-neutral-secondary-medium border-default-medium text-heading">
                        <option value="">Todos los docentes</option>
                        <template x-for="d in docentes" :key="d">
                            <option :value="d" x-text="d"></option>
                        </template>
                    </select>
                </div>
            </div>

            <p x-show="cargando" x-cloak class="rounded-base border border-default bg-neutral-primary-soft p-6 text-center text-sm text-body">Cargando reporte...</p>
            <x-ui.alert x-show="errorReporte && !cargando" x-cloak type="danger"><span x-text="errorReporte"></span></x-ui.alert>

            <div x-show="!cargando && !errorReporte">
                <p x-show="!hayDatos" class="rounded-base border border-default bg-neutral-primary-soft p-8 text-center text-sm text-body">No hay datos para mostrar</p>

                <div x-show="hayDatos" class="space-y-6">
                    <div>
                        <h3 class="text-base font-semibold text-heading mb-2">Resumen por aula</h3>
                        <div class="overflow-x-auto">
                            <x-ui.table :headers="['Aula', 'Docente a cargo', 'Registrados', 'Presentes', 'Ausentes']" min-width="min-w-[640px]">
                                <x-slot:body>
                                    <template x-for="r in resumenPorAula" :key="r.aula">
                                        <tr class="border-b border-default bg-neutral-primary last:border-0">
                                            <td class="px-6 py-4" x-text="r.aula"></td>
                                            <td class="px-6 py-4" x-text="r.docente"></td>
                                            <td class="px-6 py-4" x-text="r.registrados"></td>
                                            <td class="px-6 py-4" x-text="r.presentes"></td>
                                            <td class="px-6 py-4" x-text="r.ausentes"></td>
                                        </tr>
                                    </template>
                                </x-slot:body>
                            </x-ui.table>
                        </div>
                    </div>

                    <div>
                        <h3 class="text-base font-semibold text-heading mb-2">Lista de ausentes</h3>
                        <div class="overflow-x-auto">
                            <x-ui.table :headers="['Nombres', 'Apellidos', 'Código SIS', 'Aula']" min-width="min-w-[640px]">
                                <x-slot:body>
                                    <template x-for="e in listaAusentes" :key="'aus-'+e.sis">
                                        <tr class="border-b border-default bg-neutral-primary last:border-0">
                                            <td class="px-6 py-4" x-text="e.nombre"></td>
                                            <td class="px-6 py-4" x-text="e.apellido"></td>
                                            <td class="px-6 py-4 font-mono" x-text="e.sis"></td>
                                            <td class="px-6 py-4" x-text="e.aula"></td>
                                        </tr>
                                    </template>
                                </x-slot:body>
                            </x-ui.table>
                        </div>
                    </div>

                    <div>
                        <h3 class="text-base font-semibold text-heading mb-2">Lista de ingresados</h3>
                        <div class="overflow-x-auto">
                            <x-ui.table :headers="['Nombres', 'Apellidos', 'Código SIS', 'Aula', 'Hora de ingreso']" min-width="min-w-[720px]">
                                <x-slot:body>
                                    <template x-for="e in listaIngresados" :key="'ing-'+e.sis">
                                        <tr class="border-b border-default bg-neutral-primary last:border-0">
                                            <td class="px-6 py-4" x-text="e.nombre"></td>
                                            <td class="px-6 py-4" x-text="e.apellido"></td>
                                            <td class="px-6 py-4 font-mono" x-text="e.sis"></td>
                                            <td class="px-6 py-4" x-text="e.aula"></td>
                                            <td class="px-6 py-4" x-text="e.hora_ingreso || '-'"></td>
                                        </tr>
                                    </template>
                                </x-slot:body>
                            </x-ui.table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>
