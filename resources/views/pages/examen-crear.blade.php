{{--
    @file    examen-crear.blade.php
    @author  Valery D. Ortuno P <valerydariana98@gmail.com>
    @created 2026-10-24

    @description
    Formulario de alta de un examen como página propia, a la que se llega
    desde la sección Exámenes eligiendo un curso y "Crear examen". La página
    aporta el encabezado (flecha de volver a Exámenes) y el contenedor; el
    formulario, sus catálogos y sus errores viven en x-ui.form-crear-examen.

    Si la validación falla, StoreExamenRequest redirige de vuelta acá con los
    errores y lo escrito; si el alta sale bien, ExamenController lleva a los
    exámenes del curso.

    @changelog
    - 2026-10-24  [Valery D. Ortuno P]  feat: creación inicial de la página.

    @see  App\Http\Controllers\ExamenController
    @see  resources/views/components/ui/form-crear-examen.blade.php
--}}

@extends('layouts.app')

@section('title', 'Crear examen')

@section('subtitle', $curso->nombre_curso)

@section('content')
    <div class="px-4 py-4 sm:px-6 sm:py-6 lg:px-8">
        <div class="mx-auto w-full max-w-2xl rounded-base border border-default bg-neutral-primary-soft p-4 shadow-sm md:p-6">
            <x-ui.form-crear-examen
                :materia="$curso->nombre_curso"
                :ambientes="$ambientes"
                :materiales="$materiales"
                :normas="$normas"
                :curso-id="$curso->id_curso"
                :cancelar-url="route('examenes')"
            />
        </div>
    </div>
@endsection
