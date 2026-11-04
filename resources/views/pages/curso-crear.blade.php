{{--
    @file    curso-crear.blade.php
    @author  Valery D. Ortuno P <valerydariana98@gmail.com>
    @created 2026-10-24
    @updated 2026-10-24
    @updated 2026-11-04

    @description
    "Crear curso": página propia (con flecha de volver a Cursos) con el
    formulario de alta de un curso: se piden materia, grupo, gestión y año, y
    debajo, en texto corrido, va el nombre del curso, que se arma solo con la
    materia y el grupo mientras se escriben ("{materia} - Grupo {grupo}"); no
    es un campo ni lo parece, para que nadie intente escribir ahí. El curso se
    crea activo, a nombre del docente, y al guardar se vuelve a la lista de
    cursos.

    El grupo es alfanumérico de hasta 3 caracteres (2, 10, 21A): el campo
    descarta lo demás y lo pasa a mayúsculas mientras se escribe. El año
    arranca en el actual: solo dígitos, hasta 4; el servidor exige el actual o
    uno posterior. Todo se vuelve a validar en StoreCursoRequest.

    Los errores del último envío los maneja Alpine (como en
    form-crear-examen): al corregir un campo se borran su rojo y su mensaje.
    Si el curso ya existe, el aviso va arriba y los cuatro campos quedan en
    rojo hasta que se cambie alguno.

    @changelog
    - 2026-10-24  [Valery D. Ortuno P]  feat: creación inicial de la página.
    - 2026-10-24  [Valery D. Ortuno P]  feat: formulario de materia, grupo,
      gestión y año (libre, el actual por defecto) con el nombre del curso
      arriba, que se llena solo, validaciones y guardado.
    - 2026-11-04  [Valery D. Ortuno P]  fix: el nombre del curso pasa a ser texto
      corrido debajo de los campos (ya no parece un campo); aviso arriba si el
      curso ya existe, con los campos implicados en rojo; al corregir un campo
      se borran su rojo y su mensaje (antes quedaban hasta el siguiente envío).

    @see  App\Http\Controllers\CursoController
    @see  App\Http\Requests\StoreCursoRequest
    @see  App\Services\Curso\RegistrarCursoService
--}}

@php
    $mensajes = collect($errors->messages())
        ->map(fn (array $lista): string => (string) ($lista[0] ?? ''))
        ->filter()
        ->all();

    $claseError = 'bg-danger-soft! border-danger-subtle! text-fg-danger-strong! focus:ring-danger! focus:border-danger!';
@endphp

@extends('layouts.app')

@section('title', 'Crear curso')

@section('content')
    <div class="px-4 py-4 sm:px-6 sm:py-6 lg:px-8">
        <div class="mx-auto w-full max-w-2xl rounded-base border border-default bg-neutral-primary-soft p-4 shadow-sm md:p-6"
             x-data="{
                 materia: @js(old('materia', '')),
                 grupo: @js(old('grupo', '')),
                 gestion: @js((string) old('gestion', '')),
                 anio: @js((string) old('anio', now()->year)),
                 errores: @js($mensajes),

                 // Al corregir un campo se borra su error. Tocar cualquiera de
                 // los cuatro borra tambien el aviso de curso repetido, porque
                 // cambiar uno ya lo vuelve otro curso; el servidor revisa de
                 // nuevo al guardar.
                 init() {
                     ['materia', 'grupo', 'gestion', 'anio'].forEach((campo) => {
                         this.$watch(campo, () => this.limpiarErrores([campo, 'curso']));
                     });
                 },
                 limpiarErrores(campos) {
                     this.errores = Object.fromEntries(
                         Object.entries(this.errores).filter(([clave]) => ! campos.includes(clave))
                     );
                 },
                 errorDe(campo) {
                     return this.errores[campo] ?? '';
                 },
                 // Rojo si el campo tiene su error o si el curso ya existe
                 // (los cuatro campos forman el curso).
                 claseErrorDe(campo) {
                     return this.errorDe(campo) || this.errorDe('curso') ? '{{ $claseError }}' : '';
                 },

                 // Solo letras y numeros, hasta 3, en mayusculas: 21a -> 21A.
                 sanearGrupo() {
                     this.grupo = this.grupo.replace(/[^a-zA-Z0-9]/g, '').slice(0, 3).toUpperCase();
                 },
                 // Solo digitos, hasta 4; que sea el actual o posterior lo
                 // valida el servidor.
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

                {{-- Curso repetido: aviso general arriba del formulario. --}}
                <div x-show="errorDe('curso')" x-cloak>
                    <x-ui.alert type="danger" class="mb-0!">
                        <span x-text="errorDe('curso')"></span>
                    </x-ui.alert>
                </div>

                <div>
                    <x-ui.input
                        label="Materia"
                        id="materia_curso"
                        name="materia"
                        maxlength="80"
                        placeholder="Ingrese el nombre de la materia"
                        x-model="materia"
                        x-bind:class="claseErrorDe('materia')"
                        x-bind:aria-invalid="claseErrorDe('materia') ? 'true' : null"
                        required
                    />
                    <p x-show="errorDe('materia')" x-text="errorDe('materia')" x-cloak
                       class="mt-1 text-sm text-fg-danger-strong"></p>
                </div>

                <div>
                    <x-ui.input
                        label="Grupo"
                        id="grupo_curso"
                        name="grupo"
                        maxlength="3"
                        placeholder="Ingrese el grupo"
                        x-model="grupo"
                        @input="sanearGrupo()"
                        x-bind:class="claseErrorDe('grupo')"
                        x-bind:aria-invalid="claseErrorDe('grupo') ? 'true' : null"
                        required
                    />
                    <p x-show="errorDe('grupo')" x-text="errorDe('grupo')" x-cloak
                       class="mt-1 text-sm text-fg-danger-strong"></p>
                </div>

                <div>
                    <x-ui.select
                        label="Gestion"
                        id="gestion_curso"
                        name="gestion"
                        :options="$gestiones"
                        :selected="old('gestion')"
                        placeholder="Seleccione la gestion"
                        x-model="gestion"
                        x-bind:class="claseErrorDe('gestion')"
                        x-bind:aria-invalid="claseErrorDe('gestion') ? 'true' : null"
                        required
                    />
                    <p x-show="errorDe('gestion')" x-text="errorDe('gestion')" x-cloak
                       class="mt-1 text-sm text-fg-danger-strong"></p>
                </div>

                <div>
                    <x-ui.input
                        label="Año"
                        id="anio_curso"
                        name="anio"
                        inputmode="numeric"
                        maxlength="4"
                        placeholder="Ingrese el año"
                        x-model="anio"
                        @input="sanearAnio()"
                        x-bind:class="claseErrorDe('anio')"
                        x-bind:aria-invalid="claseErrorDe('anio') ? 'true' : null"
                        required
                    />
                    <p x-show="errorDe('anio')" x-text="errorDe('anio')" x-cloak
                       class="mt-1 text-sm text-fg-danger-strong"></p>
                </div>

                {{-- Vista previa en texto corrido, debajo de los campos y sin
                     recuadro, para que no parezca otro campo. Se arma sola con
                     la materia y el grupo y no se envía (el servidor vuelve a
                     armar el nombre). --}}
                <p class="text-sm text-body break-words" aria-live="polite">
                    Nombre del curso:
                    <span x-show="nombreCurso()" x-text="nombreCurso()" class="font-semibold text-heading"></span>
                    <span x-show="! nombreCurso()" class="text-muted">se forma con la materia y el grupo</span>
                </p>

                <div class="flex flex-col-reverse gap-[2vh] border-t border-default pt-4 sm:flex-row">
                    <x-ui.button-cancelar class="flex-1" @click="window.location.href = {{ \Illuminate\Support\Js::from(route('cursos')) }}">Cancelar</x-ui.button-cancelar>
                    <x-ui.button type="submit" variant="default" class="flex-1">Crear curso</x-ui.button>
                </div>
            </form>
        </div>
    </div>
@endsection
