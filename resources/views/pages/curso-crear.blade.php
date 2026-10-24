{{--
    @file    curso-crear.blade.php
    @author  Valery D. Ortuno P <valerydariana98@gmail.com>
    @created 2026-10-24
    @updated 2026-10-24

    @description
    "Crear curso": página propia (con flecha de volver a Cursos) con el
    formulario de alta de un curso. Arriba va "Nombre del curso", de solo
    lectura, que se arma solo con la materia y el grupo mientras se escriben
    ("{materia} - Grupo {grupo}"). Debajo se piden materia, grupo, gestión y
    año. El curso se crea activo, a nombre del docente, y al guardar se vuelve
    a la lista de cursos.

    El grupo es alfanumérico de hasta 3 caracteres (2, 10, 21A): el campo
    descarta lo demás y lo pasa a mayúsculas mientras se escribe. El año
    arranca en el actual y es libre: solo dígitos, hasta 4; el servidor exige
    entre 2000 y 2099. Todo se vuelve a validar en StoreCursoRequest.

    @changelog
    - 2026-10-24  [Valery D. Ortuno P]  feat: creación inicial de la página.
    - 2026-10-24  [Valery D. Ortuno P]  feat: formulario de materia, grupo,
      gestión y año (libre, el actual por defecto) con el nombre del curso
      arriba, que se llena solo, validaciones y guardado.

    @see  App\Http\Controllers\CursoController
    @see  App\Http\Requests\StoreCursoRequest
    @see  App\Services\Curso\RegistrarCursoService
--}}

@extends('layouts.app')

@section('title', 'Crear curso')

@section('content')
    <div class="px-4 py-4 sm:px-6 sm:py-6 lg:px-8">
        <div class="mx-auto w-full max-w-2xl rounded-base border border-default bg-neutral-primary-soft p-4 shadow-sm md:p-6"
             x-data="{
                 materia: @js(old('materia', '')),
                 grupo: @js(old('grupo', '')),
                 anio: @js((string) old('anio', now()->year)),
                 // Solo letras y numeros, hasta 3, en mayusculas: 21a -> 21A.
                 sanearGrupo() {
                     this.grupo = this.grupo.replace(/[^a-zA-Z0-9]/g, '').slice(0, 3).toUpperCase();
                 },
                 // Solo digitos, hasta 4; el rango 2000-2099 lo valida el servidor.
                 sanearAnio() {
                     this.anio = this.anio.replace(/\D/g, '').slice(0, 4);
                 },
                 nombreCurso() {
                     const materia = this.materia.trim().replace(/\s+/g, ' ');

                     return materia !== '' && this.grupo !== '' ? materia + ' - Grupo ' + this.grupo : '';
                 }
             }">
            <form method="POST" action="{{ route('cursos.store') }}" class="space-y-4">
                @csrf

                {{-- Se arma solo con la materia y el grupo; no se envía: el
                     servidor vuelve a armarlo con los datos validados. --}}
                <x-ui.input
                    label="Nombre del curso"
                    id="nombre_curso"
                    placeholder="Se completa con la materia y el grupo"
                    x-bind:value="nombreCurso()"
                    readonly
                    class="cursor-default"
                />

                <x-ui.input
                    label="Materia"
                    id="materia_curso"
                    name="materia"
                    maxlength="80"
                    placeholder="Ingrese el nombre de la materia"
                    x-model="materia"
                    :error="$errors->first('materia')"
                    required
                />

                <x-ui.input
                    label="Grupo"
                    id="grupo_curso"
                    name="grupo"
                    maxlength="3"
                    placeholder="Ingrese el grupo"
                    x-model="grupo"
                    @input="sanearGrupo()"
                    :error="$errors->first('grupo')"
                    required
                />

                <x-ui.select
                    label="Gestion"
                    id="gestion_curso"
                    name="gestion"
                    :options="$gestiones"
                    :selected="old('gestion')"
                    placeholder="Seleccione la gestion"
                    :error="$errors->first('gestion')"
                    required
                />

                <x-ui.input
                    label="Año"
                    id="anio_curso"
                    name="anio"
                    inputmode="numeric"
                    maxlength="4"
                    placeholder="Ingrese el año"
                    x-model="anio"
                    @input="sanearAnio()"
                    :error="$errors->first('anio')"
                    required
                />

                <div class="flex flex-col-reverse gap-[2vh] border-t border-default pt-4 sm:flex-row">
                    <x-ui.button-cancelar class="flex-1" @click="window.location.href = {{ \Illuminate\Support\Js::from(route('cursos')) }}">Cancelar</x-ui.button-cancelar>
                    <x-ui.button type="submit" variant="default" class="flex-1">Crear curso</x-ui.button>
                </div>
            </form>
        </div>
    </div>
@endsection
