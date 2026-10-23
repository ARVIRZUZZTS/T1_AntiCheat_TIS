{{--
    @file    reportes.blade.php
    @author  Alex Candia <alex.leonar.candia@gmail.com>
    @created 2026-09-29
    @updated 2026-10-21

    @description
    Pantalla de reportes por materia y examen. Dos selectores dinámicos
    (materia → examen) condicionan la aparición de tres pestañas de reporte.
    El contenido de cada pestaña se implementará después; por ahora cada una
    muestra un placeholder.

    El selector de examen es un <select> a mano con las mismas clases de
    x-ui.select, porque las opciones deben cambiar cuando cambia la materia
    (x-ui.select renderiza las opciones en Blade y Alpine no puede
    reemplazarlas después). El de materia sí usa x-ui.select directamente.

    @changelog
    - 2026-09-29  [David E. Chavez T.]  feat: vista de reportes (mock).
    - 2026-10-21  [Alex Candia]  feat: selectores dinámicos de materia y examen,
      tres pestañas de reporte (asistencia, ingresos ilegales, hora de ingreso),
      y contenido condicional a que ambos selectores tengan valor.
--}}

@extends('layouts.app')

@section('title', 'Reportes')

@section('content')
    <div
        {{-- OJO: este atributo va entre comillas dobles, asi que dentro no puede
             aparecer ni una sola comilla doble, ni en codigo ni en comentarios.
             Para valores se usa @js() y para comentar, comillas simples. --}}
        x-data="{
            materiaId: '',
            examenId: '',
            tab: 'asistencia',
            ingresos: [],
            cargandoIngresos: false,
            examenesPorCurso: @js($examenesPorCurso),

            // Examenes de la materia elegida. Si no hay materia o no hay
            // examenes para esa materia, la lista queda vacia.
            get examenesVisibles() {
                return this.examenesPorCurso[this.materiaId] || [];
            },

            // Cambiar de materia invalida el examen elegido.
            init() {
                this.$watch('materiaId', () => { this.examenId = ''; this.ingresos = []; });
                this.$watch('examenId', () => { if (this.tab === 'ingresos' && this.examenId) { this.cargarIngresos(); } });
                this.$watch('tab', () => { if (this.tab === 'ingresos' && this.examenId) { this.cargarIngresos(); } });
            },
            async cargarIngresos() {
                if (! this.examenId) { return; }
                this.cargandoIngresos = true;
                try {
                    const url = '/reportes/ingresos-ilegales?examen=' + encodeURIComponent(this.examenId) + '&curso=' + encodeURIComponent(this.materiaId);
                    const response = await fetch(url, { headers: { 'Accept': 'application/json' } });
                    this.ingresos = await response.json();
                } catch (e) {
                    this.ingresos = [];
                } finally {
                    this.cargandoIngresos = false;
                }
            }
        }"
        class="pb-6"
    >
        {{-- Selectores: 1 columna en mobile, 2 en sm o mas grande. --}}
        <div class="grid grid-cols-1 gap-4 px-4 pt-4 sm:grid-cols-2 sm:px-6 sm:pt-6 lg:px-8">
            <x-ui.select
                label="Materia"
                name="materia"
                id="materia_reporte"
                x-model="materiaId"
                :options="$opcionesMaterias"
                placeholder="Selecciona una materia"
                required
            />

            <div>
                <label for="examen_reporte" class="block mb-2.5 text-sm font-medium text-heading">
                    Examen <span class="text-fg-danger-strong" aria-hidden="true">*</span>
                </label>
                <select
                    name="examen"
                    id="examen_reporte"
                    x-model="examenId"
                    :disabled="! materiaId"
                    class="block w-full px-3 py-2.5 border text-sm rounded-base shadow-xs placeholder:text-body bg-neutral-secondary-medium border-default-medium text-heading focus:ring-brand focus:border-brand disabled:opacity-50"
                >
                    <option value="" disabled>Selecciona un examen</option>
                    <template x-for="examen in examenesVisibles" :key="examen.id">
                        <option :value="examen.id" x-text="examen.etiqueta"></option>
                    </template>
                </select>
            </div>
        </div>


        <p x-show="materiaId && examenesVisibles.length === 0" class="px-4 mt-4 text-sm text-body sm:px-6 lg:px-8">
            No hay examenes para esta materia.
        </p>

        <div class="mt-6">
            @php
                $tabClases = 'shrink-0 whitespace-nowrap inline-flex items-center justify-center box-border border focus:ring-4 shadow-xs font-medium leading-5 rounded-base focus:outline-none px-4 py-2.5 text-sm';
                $tabActivo = 'text-white bg-brand border-transparent hover:bg-brand-strong focus:ring-brand-medium';
                $tabInactivo = 'text-body bg-neutral-secondary-medium border-default-medium hover:bg-neutral-tertiary-medium hover:text-heading focus:ring-neutral-tertiary';
            @endphp

            <nav class="flex gap-2 overflow-x-auto border-b border-default px-4 pb-4 sm:px-6 lg:px-8" aria-label="Tipos de reporte">
                <button type="button" @click="tab = 'asistencia'" :class="tab === 'asistencia' ? '{{ $tabActivo }}' : '{{ $tabInactivo }}'" class="{{ $tabClases }}">Reporte de asistencia</button>
                <button type="button" @click="tab = 'ingresos'" :class="tab === 'ingresos' ? '{{ $tabActivo }}' : '{{ $tabInactivo }}'" class="{{ $tabClases }}">Reporte de intentos no autorizados</button>
                <button type="button" @click="tab = 'horarios'" :class="tab === 'horarios' ? '{{ $tabActivo }}' : '{{ $tabInactivo }}'" class="{{ $tabClases }}">Reporte de Hora de ingreso</button>
            </nav>

            <section x-show="tab === 'asistencia' && materiaId && examenId" x-cloak class="mt-4 px-4 sm:px-6 lg:px-8">
            </section>
            <section x-show="tab === 'ingresos' && materiaId && examenId" x-cloak class="mt-4 px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 mb-4">
                    <div class="rounded-base border border-default bg-neutral-primary-soft p-4">
                        <p class="text-sm text-body">Total de intentos</p>
                        <p class="text-2xl font-semibold text-heading" x-text="ingresos.length"></p>
                    </div>
                    <div class="rounded-base border border-default bg-neutral-primary-soft p-4">
                        <p class="text-sm text-body">Estudiantes distintos</p>
                        <p class="text-2xl font-semibold text-heading" x-text="new Set(ingresos.map(r => r.sis)).size"></p>
                    </div>
                </div>

                <div class="flex justify-end mb-4">
                    <button type="button"
                            :disabled="ingresos.length === 0"
                            @click="if (ingresos.length) window.location.href = '/reportes/ingresos-ilegales/exportar/' + examenId + '?curso=' + materiaId"
                            class="inline-flex items-center justify-center box-border border focus:ring-4 shadow-xs font-medium leading-5 rounded-base focus:outline-none px-4 py-2.5 text-sm text-white bg-brand border-transparent hover:bg-brand-strong disabled:opacity-50 disabled:cursor-not-allowed">
                        Exportar a Excel
                    </button>
                </div>

                <template x-if="cargandoIngresos">
                    <p class="text-sm text-body">Cargando reporte...</p>
                </template>

                <div class="overflow-x-auto" x-show="! cargandoIngresos">
                    <table class="w-full text-sm text-left text-body">
                        <thead class="text-xs uppercase bg-neutral-secondary-medium text-heading">
                            <tr>
                                <th class="px-3 py-2">SIS</th>
                                <th class="px-3 py-2">Nombre</th>
                                <th class="px-3 py-2">Apellido</th>
                                <th class="px-3 py-2">Aula</th>
                                <th class="px-3 py-2">Motivo</th>
                                <th class="px-3 py-2">Tipo</th>
                                <th class="px-3 py-2">Estado</th>
                                <th class="px-3 py-2">Fecha</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="registro in ingresos" :key="registro.sis + registro.fecha">
                                <tr class="border-b border-default">
                                    <td class="px-3 py-2 font-mono" x-text="registro.sis"></td>
                                    <td class="px-3 py-2" x-text="registro.nombre"></td>
                                    <td class="px-3 py-2" x-text="registro.apellido"></td>
                                    <td class="px-3 py-2" x-text="registro.aula"></td>
                                    <td class="px-3 py-2" x-text="registro.motivo"></td>
                                    <td class="px-3 py-2" x-text="registro.tipo"></td>
                                    <td class="px-3 py-2" x-text="registro.estado"></td>
                                    <td class="px-3 py-2" x-text="registro.fecha"></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                    <p x-show="ingresos.length === 0" class="text-sm text-body mt-3">No hay intentos de ingreso no autorizados.</p>
                </div>
            </section>
            <section x-show="tab === 'horarios' && materiaId && examenId" x-cloak class="mt-4 px-4 sm:px-6 lg:px-8">
            </section>
        </div>
    </div>
@endsection
