@extends('layouts.app')

@section('title', 'Restaurar curso')

@section('content')
    <div class="px-4 py-4 sm:px-6 sm:py-6 lg:px-8">
        @if (session('mensaje'))
            <x-ui.modal-aviso :mensaje="session('mensaje')" />
        @endif

        <div class="mx-auto w-full max-w-2xl overflow-hidden rounded-base border border-default bg-neutral-primary-soft shadow-xs">
            @if ($cursos->isEmpty())
                <div class="p-8 text-center text-sm text-body">No tienes cursos archivados</div>
            @else
                <ul>
                    @foreach ($cursos as $curso)
                        <li class="flex flex-col gap-3 border-b border-default p-4 last:border-b-0 sm:flex-row sm:items-center sm:justify-between sm:gap-4 sm:p-5">
                            <p class="min-w-0 font-medium text-heading break-words">{{ $curso->nombre_curso }}</p>
                            <form method="POST" action="{{ route('cursos.restaurar.store', $curso) }}" class="w-full shrink-0 sm:w-auto">
                                @csrf
                                <x-ui.button type="submit" variant="secondary" size="sm" class="w-full sm:w-auto">
                                    Restaurar curso
                                </x-ui.button>
                            </form>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
@endsection
