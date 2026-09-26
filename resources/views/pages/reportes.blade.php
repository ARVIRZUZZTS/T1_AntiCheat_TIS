@extends('layouts.app')

@section('title', 'Reportes')

@section('content')
    <p class="text-body">
        Generación y exportación de reportes. Datos mockeados: vendrán del endpoint
        <code class="text-fg-brand">/reportes</code>.
    </p>

    <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ([
            ['Reporte de sesiones', 'Sesiones activas y finalizadas por examen.'],
            ['Reporte de anomalías', 'Eventos de trampa por severidad y estudiante.'],
            ['Reporte de rendimiento', 'Métricas generales del curso.'],
        ] as [$titulo, $descripcion])
            <div class="bg-neutral-primary-soft p-6 border border-default rounded-base shadow-xs">
                <h3 class="font-semibold text-heading">{{ $titulo }}</h3>
                <p class="mt-1 text-sm text-body">{{ $descripcion }}</p>
                <x-ui.button variant="secondary" href="#" size="sm" class="mt-4">Exportar</x-ui.button>
            </div>
        @endforeach
    </div>
@endsection