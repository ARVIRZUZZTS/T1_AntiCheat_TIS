{{--
    @file    examen-crear-seleccion.blade.php
    @author  Valery D. Ortuno P <valerydariana98@gmail.com>
    @author  Alex Candia <alex.leonar.candia@gmail.com>
    @created 2026-11-15

    @description
    Pagina de la subopcion "Crear examen" del sidebar. Arriba un selector de
    curso y, al elegir uno, el formulario de alta que ya existe
    (x-ui.form-crear-examen, de Alex Candia / Valery D. Ortuno P) precargado para
    ese curso. El selector es server-driven: al cambiarlo recarga con ?curso=ID
    (ExamenController@crearFormulario), de modo que no depende del SPA de la
    pagina Examenes ni duplica su logica. El envio y la validacion no cambian: el
    formulario hace POST a cursos.examenes.store y, si falla, StoreExamenRequest
    redirige de vuelta con los errores y el curso elegido.

    @changelog
    - 2026-10-24  [Valery D. Ortuno P]  feat: pagina de alta de examen
      (pages/examen-crear) con el formulario reutilizable.
    - 2026-11-15  [Diego Tejerina]  refactor: se agrega el selector de curso
      arriba para la subopcion "Crear examen", reutilizando ese formulario.

    @see  App\Http\Controllers\ExamenController::crearFormulario()
    @see  resources/views/components/ui/form-crear-examen.blade.php
--}}

@extends('layouts.app')

@section('title', 'Crear examen')

@section('content')
    <div class="px-4 py-4 sm:px-6 sm:py-6 lg:px-8">
        @if ($cursos->isEmpty())
            <p class="mx-auto max-w-2xl rounded-base border border-default bg-neutral-primary-soft p-8 text-center text-sm text-body shadow-xs">
                No tienes cursos activos para crear un examen.
            </p>
        @else
            <div class="mx-auto w-full max-w-2xl space-y-4">
                {{-- Selector de curso. Al cambiar recarga la pagina con ?curso=ID;
                     con JS deshabilitado, el boton "Continuar" hace el mismo GET. --}}
                <form method="GET" action="{{ route('examenes.crear') }}"
                      class="rounded-base border border-default bg-neutral-primary-soft p-4 shadow-xs">
                    <x-ui.select
                        label="Curso al que pertenece"
                        name="curso"
                        :options="$cursos->mapWithKeys(fn ($curso) => [$curso->id_curso => $curso->nombre_curso])->all()"
                        :selected="$cursoSeleccionado?->id_curso"
                        placeholder="Selecciona un curso"
                        :required="true"
                        onchange="this.form.submit()"
                    />
                    <noscript>
                        <div class="mt-3">
                            <x-ui.button type="submit">Continuar</x-ui.button>
                        </div>
                    </noscript>
                </form>

                @if ($cursoSeleccionado)
                    <div class="rounded-base border border-default bg-neutral-primary-soft p-4 shadow-xs md:p-6">
                        <x-ui.form-crear-examen
                            :nombre-curso="$cursoSeleccionado->nombre_curso"
                            :ambientes="$ambientes"
                            :materiales="$materiales"
                            :normas="$normas"
                            :curso-id="$cursoSeleccionado->id_curso"
                            :cancelar-url="route('examenes')"
                        />
                    </div>
                @else
                    <p class="rounded-base border border-default bg-neutral-primary-soft p-6 text-center text-sm text-body shadow-xs">
                        Elige un curso para cargar el formulario de alta.
                    </p>
                @endif
            </div>
        @endif
    </div>
@endsection
