{{--
    @file    curso-editar.blade.php
    @author  Alisson D. Alvarado <alvaradoalissondalet@gmail.com>
    @created 2026-11-21
    @updated 2026-11-21

    @description
    Lista los cursos propios y permite cargar y guardar su edición mediante la API existente.

    @changelog
    - 2026-11-21  [Alisson D. Alvarado]  feat: agregar la interfaz de edición de cursos.

    @see App\Http\Controllers\EditarCursoVistaController
    @see resources/views/pages/curso-crear.blade.php
--}}

@extends('layouts.app')

@section('title', 'Editar curso')

@section('content')
    <div class="px-4 py-4 sm:px-6 sm:py-6 lg:px-8"
         x-data="{
             curso: null,
             materia: '',
             grupo: '',
             gestion: '',
             anio: '',
             cargando: false,
             guardando: false,
             error: '',
             mensaje: '',
             solicitud: 0,

             async seleccionar(url) {
                 const solicitud = ++this.solicitud;
                 this.curso = null;
                 this.error = '';
                 this.mensaje = '';
                 this.cargando = true;

                 try {
                     const respuesta = await fetch(url, {
                         headers: { 'Accept': 'application/json' },
                         credentials: 'same-origin',
                     });
                     if (! respuesta.ok) throw new Error('No se pudo cargar el curso.');
                     const resultado = await respuesta.json();
                     if (solicitud !== this.solicitud) return;
                     this.curso = { ...resultado.datos, url };
                     this.materia = resultado.datos.materia;
                     this.grupo = resultado.datos.grupo;
                     this.gestion = String(resultado.datos.gestion);
                     this.anio = String(resultado.datos.anio);
                 } catch (fallo) {
                     if (solicitud === this.solicitud) this.error = fallo.message;
                 } finally {
                     if (solicitud === this.solicitud) this.cargando = false;
                 }
             },

             async guardar() {
                 if (! this.curso || this.guardando) return;
                 this.guardando = true;
                 this.error = '';
                 try {
                     const respuesta = await fetch(this.curso.url, {
                         method: 'PUT',
                         credentials: 'same-origin',
                         headers: {
                             'Accept': 'application/json',
                             'Content-Type': 'application/json',
                             'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                         },
                         body: JSON.stringify({
                             materia: this.materia, grupo: this.grupo, gestion: this.gestion, anio: this.anio,
                         }),
                     });
                     const resultado = await respuesta.json().catch(() => ({}));
                     if (! respuesta.ok) throw new Error(resultado.message || 'No se pudo guardar el curso.');
                     this.curso = null;
                     this.mensaje = 'Curso actualizado con exito';
                     const nombre = document.getElementById('curso-nombre-' + resultado.datos.id);
                     if (nombre) nombre.textContent = resultado.datos.nombre;
                 } catch (fallo) {
                     this.error = fallo.message || 'No se pudo guardar el curso.';
                 } finally {
                     this.guardando = false;
                 }
             },

             cancelar() {
                 this.solicitud++;
                 this.curso = null;
                 this.cargando = false;
                 this.error = '';
             },

             nombreCurso() {
                 const materia = this.materia.trim().replace(/\s+/g, ' ');
                 return materia && this.grupo ? materia + ' - Grupo ' + this.grupo : '';
             },
         }">
        <div class="mx-auto w-full max-w-2xl">
            <p x-show="mensaje" x-text="mensaje" x-cloak role="status" class="mb-4 text-sm text-fg-success-strong"></p>
            <p x-show="error" x-text="error" x-cloak role="alert" class="mb-4 text-sm text-fg-danger-strong"></p>
            <p x-show="cargando" x-cloak class="mb-4 text-sm text-body">Cargando curso...</p>

            <ul x-show="! curso" class="overflow-hidden rounded-base border border-default bg-neutral-primary-soft shadow-xs">
                @forelse ($cursos as $fila)
                    @php $curso = $fila['curso']; @endphp
                    <li class="flex flex-col gap-3 border-b border-default p-4 last:border-b-0 sm:flex-row sm:items-center sm:justify-between sm:gap-4 sm:p-5">
                        <p id="curso-nombre-{{ $curso->id_curso }}" class="min-w-0 font-medium text-heading break-words">{{ $curso->nombre_curso }}</p>
                        <x-ui.button type="button" size="sm" class="w-full shrink-0 sm:w-auto"
                            :data-url="route('api.cursos.detalle', $curso->id_curso)"
                            :aria-label="'Editar curso '.$curso->nombre_curso"
                            @click="seleccionar($el.dataset.url)">
                            Editar curso
                        </x-ui.button>
                    </li>
                @empty
                    <li class="p-8 text-center text-sm text-body">No tienes cursos registrados</li>
                @endforelse
            </ul>

            <div x-show="curso" x-cloak class="rounded-base border border-default bg-neutral-primary-soft p-4 shadow-sm md:p-6">
                <form @submit.prevent="guardar()" class="space-y-4">
                    <x-ui.input label="Materia" id="materia_curso" name="materia"
                        placeholder="Ingrese el nombre de la materia" x-model="materia" />
                    <x-ui.input label="Grupo" id="grupo_curso" name="grupo"
                        placeholder="Ingrese el grupo" x-model="grupo" />
                    <x-ui.select label="Gestion" id="gestion_curso" name="gestion"
                        :options="$gestiones" placeholder="Seleccione la gestion" x-model="gestion" />
                    <x-ui.input label="Año" id="anio_curso" name="anio" inputmode="numeric"
                        placeholder="Ingrese el año" x-model="anio" />

                    <p class="text-sm text-body break-words" aria-live="polite">
                        Nombre del curso:
                        <span x-show="nombreCurso()" x-text="nombreCurso()" class="font-semibold text-heading"></span>
                        <span x-show="! nombreCurso()" class="text-muted">se forma con la materia y el grupo</span>
                    </p>

                    <div class="flex flex-col-reverse gap-[2vh] border-t border-default pt-4 sm:flex-row">
                        <x-ui.button-cancelar class="flex-1" x-bind:disabled="guardando" @click="cancelar()">Cancelar</x-ui.button-cancelar>
                        <x-ui.button type="submit" variant="default" class="flex-1" x-bind:disabled="guardando">
                            <span x-text="guardando ? 'Guardando...' : 'Guardar cambios'"></span>
                        </x-ui.button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
