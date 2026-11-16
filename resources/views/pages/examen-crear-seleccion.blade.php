{{--
    @file    examen-crear-seleccion.blade.php
    @author  Valery D. Ortuno P <valerydariana98@gmail.com>
    @author  Alex Candia <alex.leonar.candia@gmail.com>
    @created 2026-11-15

    @description
    Pagina de la subopcion "Crear examen" del sidebar. Incrusta el formulario de
    alta que ya existe (x-ui.form-crear-examen, de Alex Candia / Valery D. Ortuno
    P) con todos los cursos activos para elegir el curso dentro del propio
    formulario. El envio y la validacion no cambian: el formulario hace POST a
    cursos.examenes.store y, si falla, StoreExamenRequest redirige de vuelta con
    los errores y el curso elegido.

    @changelog
    - 2026-10-24  [Valery D. Ortuno P]  feat: pagina de alta de examen
      (pages/examen-crear) con el formulario reutilizable.
    - 2026-11-15  [Diego Tejerina]  refactor: se agrega el selector de curso
      arriba para la subopcion "Crear examen", reutilizando ese formulario.
    - 2026-11-15  [Alex Candia]  fix: el curso se elige dentro del formulario;
      se quita el selector aparte para no duplicarlo.

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
            <div class="mx-auto w-full max-w-2xl rounded-base border border-default bg-neutral-primary-soft p-4 shadow-xs md:p-6">
                <x-ui.form-crear-examen
                    :nombre-curso="$cursoSeleccionado?->nombre_curso ?? ''"
                    :cursos="$cursos->mapWithKeys(fn ($curso) => [$curso->id_curso => $curso->nombre_curso])->all()"
                    :ambientes="$ambientes"
                    :materiales="$materiales"
                    :normas="$normas"
                    :curso-id="$cursoSeleccionado?->id_curso"
                    :accion-url="route('cursos.examenes.store', ['curso' => '__CURSO__'])"
                    :cancelar-url="route('examenes')"
                />
            </div>
        @endif
    </div>
@endsection
