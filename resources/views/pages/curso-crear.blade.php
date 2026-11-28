{{--
    @file    curso-crear.blade.php
    @author  Valery D. Ortuno P <valerydariana98@gmail.com>
    @created 2026-10-24
    @updated 2026-11-28

    @description
    "Crear curso": formulario de alta. La materia se elige con un buscador
    (x-ui.selector-materia) que filtra el catálogo por nombre o código, muestra
    el semestre y deja agregar una materia nueva si no existe. El grupo es un
    número del 1 al 10. Debajo van la gestión y el año. El nombre del curso lo
    arma el servidor como "Materia - Grupo N". Todo se valida en
    StoreCursoRequest / RegistrarCursoService.

    Los errores del último envío los maneja Alpine: al corregir un campo se
    borran su rojo y su mensaje. Si el curso ya existe, el aviso va arriba y los
    campos quedan en rojo hasta que se cambie alguno.

    @changelog
    - 2026-10-24  [Valery D. Ortuno P]  feat: creación inicial de la página.
    - 2026-11-04  [Valery D. Ortuno P]  fix: nombre del curso como texto corrido;
      aviso de curso repetido arriba.
    - 2026-11-28  [Diego Tejerina]  feat: la materia se elige con el buscador del
      catálogo (y "Agregar materia"); el grupo pasa a ser un número del 1 al 10.

    @see  App\Http\Controllers\CursoController
    @see  App\Http\Requests\StoreCursoRequest
    @see  resources/views/components/ui/selector-materia.blade.php
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
                 grupo: @js(old('grupo', '')),
                 gestion: @js((string) old('gestion', '')),
                 anio: @js((string) old('anio', now()->year)),
                 errores: @js($mensajes),

                 init() {
                     ['grupo', 'gestion', 'anio'].forEach((campo) => {
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
                 claseErrorDe(campo) {
                     return this.errorDe(campo) || this.errorDe('curso') ? '{{ $claseError }}' : '';
                 },
                 // Solo digitos, hasta 2 (1 a 10); el rango lo valida el servidor.
                 sanearGrupo() {
                     this.grupo = this.grupo.replace(/\D/g, '').slice(0, 2);
                 },
                 sanearAnio() {
                     this.anio = this.anio.replace(/\D/g, '').slice(0, 4);
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

                <x-ui.selector-materia
                    name="id_materia"
                    :materias="$materias"
                    :selected="old('id_materia')"
                    :error="$errors->first('id_materia') ?: $errors->first('materia')"
                />

                <div>
                    <x-ui.input
                        label="Grupo"
                        id="grupo_curso"
                        name="grupo"
                        inputmode="numeric"
                        maxlength="2"
                        placeholder="Numero del 1 al 10"
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

                <div class="flex flex-col-reverse gap-[2vh] border-t border-default pt-4 sm:flex-row">
                    <x-ui.button-cancelar class="flex-1" @click="window.location.href = {{ \Illuminate\Support\Js::from(route('cursos')) }}">Cancelar</x-ui.button-cancelar>
                    <x-ui.button type="submit" variant="default" class="flex-1">Crear curso</x-ui.button>
                </div>
            </form>
        </div>
    </div>
@endsection
