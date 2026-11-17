{{--
    @file    curso-eliminar.blade.php
    @author  Alex Candia
    @created 2026-11-16

    @description
    Página "Eliminar curso" (subopción del sidebar, debajo de "Editar curso"):
    una lista simple con todos los cursos del docente autenticado. Cada fila
    muestra únicamente el nombre del curso y su botón "Eliminar curso".

    El modal de confirmación todavía no está integrado: el botón queda marcado
    con `data-eliminar-curso` para conectarlo en la siguiente iteración.

    @changelog
    - 2026-11-16  [Alex Candia]  feat: lista de cursos con botón de eliminación,
      sin modal de confirmación.

    @see  App\Http\Controllers\CursoController::eliminar()
    @see  App\Services\Curso\EliminarCursoService
--}}

@extends('layouts.app')

@section('title', 'Eliminar curso')

@section('content')
    <div class="px-4 py-4 sm:px-6 sm:py-6 lg:px-8">
        <ul class="mx-auto w-full max-w-2xl overflow-hidden rounded-base border border-default bg-neutral-primary-soft shadow-xs">
            @forelse ($cursos as $curso)
                <li class="flex flex-col gap-3 border-b border-default p-4 last:border-b-0 sm:flex-row sm:items-center sm:justify-between sm:gap-4 sm:p-5">
                    <p class="min-w-0 font-medium text-heading break-words">{{ $curso->nombre_curso }}</p>

                    <x-ui.button
                        type="button"
                        variant="danger"
                        size="sm"
                        class="w-full shrink-0 sm:w-auto"
                        data-eliminar-curso="{{ $curso->id_curso }}"
                        aria-label="Eliminar curso {{ $curso->nombre_curso }}"
                    >
                        Eliminar curso
                    </x-ui.button>
                </li>
            @empty
                <li class="p-8 text-center text-sm text-body">No tienes cursos registrados</li>
            @endforelse
        </ul>
    </div>
@endsection
