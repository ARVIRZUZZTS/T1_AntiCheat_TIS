{{--
    @file    curso-editar.blade.php
    @author  Alisson D. Alvarado <alvaradoalissondalet@gmail.com>
    @created 2026-11-21
    @updated 2026-11-23

    @description
    Lista los cursos propios y permite cargar y guardar su edición mediante la API existente.

    @changelog
    - 2026-11-21  [Alisson D. Alvarado]  feat: agregar la interfaz de edición de cursos.
    - 2026-11-21  [Alisson D. Alvarado]  feat: mostrar errores y aviso al editar un curso.
    - 2026-11-23  [Alisson D. Alvarado]  feat: seleccionar curso activo con su gestión y conservar el sidebar.

    @see App\Http\Controllers\EditarCursoVistaController
    @see resources/views/pages/curso-crear.blade.php
--}}

@extends('layouts.app')

@section('title', 'Editar curso')

@section('content')
    @php
        $claseError = 'bg-danger-soft! border-danger-subtle! text-fg-danger-strong! focus:ring-danger! focus:border-danger!';
    @endphp
    <div class="px-4 py-4 sm:px-6 sm:py-6 lg:px-8"
         x-data="{
             curso: null,
             seleccion: '',
             materia: '',
             grupo: '',
             gestion: '',
             anio: '',
             cargando: false,
             guardando: false,
             error: '',
             errores: {},
             duplicado: false,
             exito: false,
             solicitud: 0,

             async seleccionar(url) {
                 const solicitud = ++this.solicitud;
                 this.curso = null;
                 this.error = '';
                 this.errores = {};
                 this.duplicado = false;
                 this.exito = false;
                 // Una selección vacía también invalida cualquier carga anterior.
                 if (! url) {
                     this.cargando = false;
                     return;
                 }
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
                 this.errores = {};
                 this.duplicado = false;
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
                     if (respuesta.status === 409) {
                         this.duplicado = true;
                         return;
                     }
                     if (respuesta.status === 422 && resultado.errors) {
                         this.errores = resultado.errors;
                         return;
                     }
                     if (! respuesta.ok) throw new Error(resultado.message || 'No se pudo guardar el curso.');
                     this.curso = null;
                     // La etiqueta debe reflejar también la gestión recién guardada.
                     const opcion = document.getElementById('seleccionar-curso').selectedOptions[0];
                     const gestion = document.getElementById('gestion_curso').selectedOptions[0];
                     if (opcion && gestion) opcion.textContent = resultado.datos.nombre + ' - ' + gestion.textContent.trim() + ' ' + this.anio;
                     this.seleccion = '';
                     this.exito = true;
                 } catch (fallo) {
                     this.error = fallo.message || 'No se pudo guardar el curso.';
                 } finally {
                     this.guardando = false;
                 }
             },

             cancelar() {
                 this.solicitud++;
                 this.curso = null;
                 this.seleccion = '';
                 this.cargando = false;
                 this.error = '';
                 this.errores = {};
                 this.duplicado = false;
             },

             // Cualquier cambio en la combinación puede resolver el duplicado.
             limpiarError(campo) {
                 delete this.errores[campo];
                 this.duplicado = false;
             },

             errorDe(campo) {
                 return this.errores[campo]?.[0] || '';
             },

             claseErrorDe(campo) {
                 return this.duplicado || this.errorDe(campo) ? '{{ $claseError }}' : '';
             },

             nombreCurso() {
                 const materia = this.materia.trim().replace(/\s+/g, ' ');
                 return materia && this.grupo ? materia + ' - Grupo ' + this.grupo : '';
             },
         }">
        <div class="mx-auto w-full max-w-2xl">
            <p x-show="error" x-text="error" x-cloak role="alert" class="mb-4 text-sm text-fg-danger-strong"></p>
            <p x-show="cargando" x-cloak class="mb-4 text-sm text-body">Cargando curso...</p>

            <template x-if="exito">
                <x-ui.modal-aviso mensaje="Curso actualizado con éxito" />
            </template>

            @if ($cursos->isEmpty())
                <p class="rounded-base border border-default bg-neutral-primary-soft p-8 text-center text-sm text-body shadow-xs">
                    No tienes cursos activos.
                </p>
            @else
                <div class="rounded-base border border-default bg-neutral-primary-soft p-4 shadow-xs sm:p-6">
                    <x-ui.select
                        label="Curso y gestión"
                        name="seleccionar-curso"
                        :options="$cursos->mapWithKeys(fn (array $fila): array => [
                            route('api.cursos.detalle', $fila['curso']->id_curso) => $fila['curso']->nombre_curso
                                .($fila['gestion'] ? ' - '.$fila['gestion'] : ''),
                        ])->all()"
                        placeholder="Seleccione un curso"
                        x-model="seleccion"
                        @change="seleccionar(seleccion)"
                        x-bind:disabled="guardando"
                    />
                </div>
            @endif

            <div x-show="curso" x-cloak class="rounded-base border border-default bg-neutral-primary-soft p-4 shadow-sm md:p-6">
                <form @submit.prevent="guardar()" novalidate class="space-y-4">
                    <div x-show="duplicado" x-cloak role="alert">
                        <x-ui.alert type="danger" class="mb-0!">
                            Este curso ya existe en la gestión y el año elegidos.
                        </x-ui.alert>
                    </div>

                    {{-- Se conservan los valores mientras el servidor valida la combinación completa. --}}
                    <fieldset x-bind:disabled="guardando" class="space-y-4">
                        <div>
                            <x-ui.input label="Materia" id="materia_curso" name="materia"
                                placeholder="Ingrese el nombre de la materia" x-model="materia"
                                @input="limpiarError('materia')" x-bind:class="claseErrorDe('materia')"
                                x-bind:aria-invalid="claseErrorDe('materia') ? 'true' : null"
                                aria-describedby="error-materia" required />
                            <p id="error-materia" x-show="errorDe('materia')" x-text="errorDe('materia')" x-cloak
                               role="alert" class="mt-1 text-sm text-fg-danger-strong"></p>
                        </div>
                        <div>
                            <x-ui.input label="Grupo" id="grupo_curso" name="grupo"
                                placeholder="Ingrese el grupo" x-model="grupo"
                                @input="limpiarError('grupo')" x-bind:class="claseErrorDe('grupo')"
                                x-bind:aria-invalid="claseErrorDe('grupo') ? 'true' : null"
                                aria-describedby="error-grupo" required />
                            <p id="error-grupo" x-show="errorDe('grupo')" x-text="errorDe('grupo')" x-cloak
                               role="alert" class="mt-1 text-sm text-fg-danger-strong"></p>
                        </div>
                        <div>
                            <x-ui.select label="Gestion" id="gestion_curso" name="gestion"
                                :options="$gestiones" placeholder="Seleccione la gestion" x-model="gestion"
                                @change="limpiarError('gestion')" x-bind:class="claseErrorDe('gestion')"
                                x-bind:aria-invalid="claseErrorDe('gestion') ? 'true' : null"
                                aria-describedby="error-gestion" required />
                            <p id="error-gestion" x-show="errorDe('gestion')" x-text="errorDe('gestion')" x-cloak
                               role="alert" class="mt-1 text-sm text-fg-danger-strong"></p>
                        </div>
                        <div>
                            <x-ui.input label="Año" id="anio_curso" name="anio" inputmode="numeric"
                                placeholder="Ingrese el año" x-model="anio"
                                @input="limpiarError('anio')" x-bind:class="claseErrorDe('anio')"
                                x-bind:aria-invalid="claseErrorDe('anio') ? 'true' : null"
                                aria-describedby="error-anio" required />
                            <p id="error-anio" x-show="errorDe('anio')" x-text="errorDe('anio')" x-cloak
                               role="alert" class="mt-1 text-sm text-fg-danger-strong"></p>
                        </div>
                    </fieldset>

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
