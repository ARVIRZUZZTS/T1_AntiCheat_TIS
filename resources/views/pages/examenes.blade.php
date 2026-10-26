{{--
    @file    examenes.blade.php
    @author  David E. Chavez T. <virzuzz12345@gmail.com>
    @created 2026-09-29
    @updated 2026-09-29
    @updated 2026-10-24
    @updated 2026-10-25

    @description
    Sección Exámenes del docente. Es una sola página: arriba el selector de
    materia y, apenas se elige una, las cuatro pestañas de gestión (lista,
    alta, invitación y división) en el parcial examenes-curso. Sin materia
    seleccionada no se muestran las pestañas. Los botones que navegaban a otra
    página ya no existen.

    La materia puede llegar por el query string (`?curso=ID`) o por el input
    viejo cuando falló el alta, para que la página vuelva con la selección y la
    pestaña Crear Examen abierta.

    @changelog
    - 2026-09-29  [David E. Chavez T.]  feat: vista de exámenes.
    - 2026-10-12  [Diego Tejerina]  fix: la tabla fija un ancho mínimo
      (min-w-[640px]) para que en mobile las columnas no se aplasten; el
      scroll horizontal queda dentro de la tabla, no en la página.
    - 2026-10-24  [Valery D. Ortuno P]  feat: la vista deja de ser un mock: lista
      los cursos activos del docente para elegir uno y ver o crear sus exámenes.
    - 2026-10-24  [Valery D. Ortuno P]  style: la tabla con radio pasa a filas
      seleccionables con el anillo azul (como las tarjetas de exámenes), un
      clic fuera de las filas suelta la selección y los textos visibles van
      sin acentos.
    - 2026-10-25  [Alex Candia]  refactor: la sección pasa a una sola página con
      selector de materia; el parcial de exámenes se incrusta debajo y los
      botones de navegación anteriores se quitan.

    @see  App\Http\Controllers\ExamenController
    @see  App\Services\Curso\ListarCursosDocenteService
    @see  resources/views/partials/examenes-curso.blade.php
--}}

@extends('layouts.app')

@section('title', 'Examenes')

@section('content')
    <div class="px-4 py-4 sm:px-6 sm:py-6 lg:px-8">
        @if ($cursos->isEmpty())
            <p class="rounded-base border border-default bg-neutral-primary-soft p-8 text-center text-sm text-body shadow-xs">
                No tienes cursos activos.
            </p>
        @else
            @include('partials.examenes-curso', [
                'cursos' => $cursos,
                'cursoInicial' => $cursoInicial,
                'tabInicial' => $tabInicial,
                'ambientes' => $ambientes,
                'materiales' => $materiales,
                'normas' => $normas,
                'puedeCrearExamen' => true,
            ])
        @endif
    </div>
@endsection
