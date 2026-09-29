@extends('layouts.app')

@section('title', 'Materias')

@section('content')
    <p class="text-body">
        Materias registradas en el sistema.
    </p>

    <div class="mt-6">
        <x-ui.card title="Materias">
            <ul class="divide-y divide-default">
                @forelse ($cursos as $curso)
                    <li>
                        <a href="{{ route('materias.detalle', $curso->id_curso) }}" class="flex items-center justify-between py-3 -mx-2 px-2 rounded-base hover:bg-neutral-secondary-medium transition-colors">
                            <div>
                                <p class="font-medium text-heading">{{ $curso->nombre_curso }}</p>
                                <p class="text-sm text-body">#{{ $curso->id_curso }}</p>
                            </div>
                            <span class="text-xs font-medium px-1.5 py-0.5 bg-brand-softer text-fg-brand rounded-full">{{ $curso->estado === 'EnCurso' ? 'En curso' : 'Finalizado' }}</span>
                        </a>
                    </li>
                @empty
                    <li class="text-sm text-body">No hay materias registradas.</li>
                @endforelse
            </ul>
        </x-ui.card>
    </div>
@endsection