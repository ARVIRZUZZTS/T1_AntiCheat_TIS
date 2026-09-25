@extends('layouts.app')

@section('title', 'Materias')

@section('content')
    <p class="text-body">
        Gestión de materias. Datos mockeados: vendrán del endpoint
        <code class="text-fg-brand">/materias</code>.
    </p>

    <div class="mt-6">
        <x-ui.card title="Materias (mock)">
            <ul class="divide-y divide-default">
                @foreach ([
                    ['MAT-101', 'Matemática I', 'A'],
                    ['FIS-201', 'Física II', 'B'],
                    ['QUM-301', 'Química Orgánica', 'C'],
                    ['INF-401', 'Programación IV', 'A'],
                ] as [$codigo, $nombre, $seccion])
                    <li class="flex items-center justify-between py-3">
                        <div>
                            <p class="font-medium text-heading">{{ $nombre }}</p>
                            <p class="text-sm text-body">{{ $codigo }}</p>
                        </div>
                        <span class="text-xs font-medium px-1.5 py-0.5 bg-brand-softer text-fg-brand rounded-full">Sección {{ $seccion }}</span>
                    </li>
                @endforeach
            </ul>
        </x-ui.card>
    </div>
@endsection