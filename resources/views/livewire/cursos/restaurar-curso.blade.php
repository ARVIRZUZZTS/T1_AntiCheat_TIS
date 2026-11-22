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
    @if (session('error'))
        <div role="alert" class="mx-auto mb-4 w-full max-w-2xl rounded-base border border-danger bg-danger-soft px-4 py-3 text-sm text-heading">
            {{ session('error') }}
        </div>
    @endif

    <div class="mx-auto w-full max-w-2xl overflow-hidden rounded-base border border-default bg-neutral-primary-soft shadow-xs">
        @if ($cursos->isEmpty())
            <div class="flex flex-col items-center gap-3 p-8 text-center">
                <svg class="size-10 text-body-subtle" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5v9a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 16.5v-9m16.5 0A2.25 2.25 0 0 0 18 5.25H6A2.25 2.25 0 0 0 3.75 7.5m16.5 0v-.75A2.25 2.25 0 0 0 18 4.5H6a2.25 2.25 0 0 0-2.25 2.25v.75m7.5 4.5h3" />
                </svg>
                <p class="text-sm text-body">No tienes cursos archivados</p>
            </div>
        @else
            <ul>
                @foreach ($cursos as $curso)
                    <li class="flex flex-col gap-3 border-b border-default p-4 last:border-b-0 sm:flex-row sm:items-center sm:justify-between sm:gap-4 sm:p-5">
                        <p class="min-w-0 font-medium text-heading break-words">{{ $curso->nombre_curso }}</p>
                        <x-ui.button
                            type="button"
                            variant="secondary"
                            size="sm"
                            class="w-full sm:w-auto"
                            wire:click='abrirModal({{ $curso->id_curso }}, @js($curso->nombre_curso))'
                            aria-haspopup="dialog"
                        >
                            Restaurar
                        </x-ui.button>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>

    <x-ui.modal-restaurar-curso :curso-id="$cursoId" :nombre-curso="$cursoNombre" />
</div>
