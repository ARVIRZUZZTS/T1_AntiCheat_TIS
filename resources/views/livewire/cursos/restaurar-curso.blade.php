{{--
    @file    restaurar-curso.blade.php
    @author  Equipo T1
    @created 2026-11-21

    @description
    Vista de la subsección «Restaurar curso»: lista inicial de cursos
    archivados. El botón, modal y aviso se agregan en pasos siguientes.
--}}

@section('title', 'Restaurar curso')

<div class="px-4 py-4 sm:px-6 sm:py-6 lg:px-8">
    <div class="mx-auto w-full max-w-2xl overflow-hidden rounded-base border border-default bg-neutral-primary-soft shadow-xs">
        @if ($cursos->isEmpty())
            <p class="p-8 text-center text-sm text-body">No tienes cursos archivados</p>
        @else
            <ul>
                @foreach ($cursos as $curso)
                    <li class="flex flex-col gap-3 border-b border-default p-4 last:border-b-0 sm:flex-row sm:items-center sm:justify-between sm:gap-4 sm:p-5">
                        <p class="min-w-0 font-medium text-heading break-words">{{ $curso->nombre_curso }}</p>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</div>
