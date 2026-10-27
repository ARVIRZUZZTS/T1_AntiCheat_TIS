{{--
    @file    reportes.blade.php
    @author  Alex Candia <alex.leonar.candia@gmail.com>
    @created 2026-09-29
    @updated 2026-10-23
    @updated 2026-10-24
    @updated 2026-10-26

    @description
    Pantalla de reportes por materia y examen. Dos selectores dinámicos
    (materia -> examen) condicionan la aparición de tres pestañas de reporte.

    Esta vista solo conserva el estado compartido (materia, examen y pestaña
    activa). El contenido de cada reporte vive en su propio parcial bajo
    partials/reportes/ para que el archivo no siga creciendo. Los parciales
    escuchan el evento `reporte-seleccion-actualizada`. Se unificaron las
    tablas con scroll horizontal usando el componente `x-ui.table`.

    El selector de examen es un <select> a mano con las mismas clases de
    x-ui.select, porque las opciones deben cambiar cuando cambia la materia
    (x-ui.select renderiza las opciones en Blade y Alpine no puede
    reemplazarlas después). El de materia sí usa x-ui.select directamente.

    @changelog
    - 2026-09-29  [David E. Chavez T.]  feat: vista de reportes (mock).
    - 2026-10-21  [Alex Candia]  feat: selectores dinámicos de materia y examen,
      tres pestañas de reporte (asistencia, ingresos ilegales, hora de ingreso),
      y contenido condicional a que ambos selectores tengan valor.
    - 2026-10-23  [Alex Candia]  feat: frontend del reporte de horario de ingreso
      con filtros por aula y rango horario, resumen, listado y exportación.
    - 2026-10-23  [Alex Candia]  refactor: la pestaña de horario de ingreso pasa
      a partials/reportes/horario-ingreso.blade.php con su propio estado Alpine.
    - 2026-10-23  [Alex Candia]  refactor: se unifican las tablas con
      `x-ui.table` y scroll horizontal. Se extrae "intentos no autorizados" a
      `partials/reportes/ingresos-ilegales.blade.php` y se mantienen los resúmenes.
    - 2026-10-24  [Valery D. Ortuno P]  style: la fila de pestañas pierde la
      línea inferior que la separaba del contenido, igual que en la vista del
      curso, para que las pestañas tengan el mismo formato en todo el sistema.
    - 2026-10-26  [Alex Candia]  feat: el evento de selección incluye materia,
      tipo de examen y fecha formateada para que los tres reportes usen el
      mismo título y subtítulo.

    @see  resources/views/partials/reportes/horario-ingreso.blade.php
--}}

@extends('layouts.app')

@section('title', 'Reportes')

@section('content')
    <div
        {{-- OJO: este atributo va entre comillas dobles, así que dentro no puede
             aparecer ni una sola comilla doble, ni en código ni en comentarios.
             Para valores se usa @js() y para comentar, comillas simples. --}}
        x-data="{
            materiaId: '',
            examenId: '',
            tab: 'asistencia',
            materias: @js($opcionesMaterias),
            examenesPorCurso: @js($examenesPorCurso),

            // Exámenes de la materia elegida. Si no hay materia o no hay
            // exámenes para esa materia, la lista queda vacía.
            get examenesVisibles() {
                return this.examenesPorCurso[this.materiaId] || [];
            },

            get materiaSeleccionada() {
                return this.materias[this.materiaId] || '';
            },

            get examenSeleccionado() {
                const id = String(this.examenId);

                return this.examenesVisibles.find(examen => String(examen.id) === id) || null;
            },

            get tipoExamenSeleccionado() {
                return this.examenSeleccionado ? this.examenSeleccionado.tipo : '';
            },

            get fechaExamenSeleccionada() {
                return this.examenSeleccionado ? this.examenSeleccionado.fecha_formateada : '';
            },

            actualizarReportes() {
                this.$dispatch('reporte-seleccion-actualizada', {
                    idCurso: this.materiaId,
                    idExamen: this.examenId,
                    nombreMateria: this.materiaSeleccionada,
                    tipoExamen: this.tipoExamenSeleccionado,
                    fechaExamen: this.fechaExamenSeleccionada
                });
            },

            init() {
                this.$watch('materiaId', () => {
                    if (this.examenId !== '') {
                        this.examenId = '';
                        return;
                    }

                    this.actualizarReportes();
                });
                this.$watch('examenId', () => this.actualizarReportes());
            }
        }"
        class="pb-6"
    >
        {{-- Selectores: 1 columna en mobile, 2 en sm o más grande. --}}
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
            No hay exámenes para esta materia.
        </p>

        <div class="mt-6">
            @php
                $tabClases = 'shrink-0 whitespace-nowrap inline-flex items-center justify-center box-border border focus:ring-4 shadow-xs font-medium leading-5 rounded-base focus:outline-none px-4 py-2.5 text-sm';
                $tabActivo = 'text-white bg-brand border-transparent hover:bg-brand-strong focus:ring-brand-medium';
                $tabInactivo = 'text-body bg-neutral-secondary-medium border-default-medium hover:bg-neutral-tertiary-medium hover:text-heading focus:ring-neutral-tertiary';
            @endphp

            <nav class="flex gap-2 overflow-x-auto px-4 sm:px-6 lg:px-8" aria-label="Tipos de reporte">
                <button type="button" @click="tab = 'asistencia'" :class="tab === 'asistencia' ? '{{ $tabActivo }}' : '{{ $tabInactivo }}'" class="{{ $tabClases }}">Reporte de asistencia</button>
                <button type="button" @click="tab = 'ingresos'" :class="tab === 'ingresos' ? '{{ $tabActivo }}' : '{{ $tabInactivo }}'" class="{{ $tabClases }}">Reporte de intentos no autorizados</button>
                <button type="button" @click="tab = 'horarios'" :class="tab === 'horarios' ? '{{ $tabActivo }}' : '{{ $tabInactivo }}'" class="{{ $tabClases }}">Reporte de horarios de ingreso</button>
            </nav>

            <section x-show="tab === 'asistencia' && materiaId && examenId" x-cloak class="mt-4 px-4 sm:px-6 lg:px-8">
                @include('partials.reportes.asistencia')
            </section>
            <section x-show="tab === 'ingresos' && materiaId && examenId" x-cloak class="mt-4 px-4 sm:px-6 lg:px-8">
                @include('partials.reportes.ingresos-ilegales')
            </section>
            <section x-show="tab === 'horarios' && materiaId && examenId" x-cloak class="mt-4 px-4 sm:px-6 lg:px-8">
                @include('partials.reportes.horario-ingreso')
            </section>
        </div>
        @include('partials.reportes.test-datos')
    </div>
@endsection
