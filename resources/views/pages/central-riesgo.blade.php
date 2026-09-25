@extends('layouts.app')

@section('title', 'Central de riesgo')

@section('content')
    <p class="text-body">
        Alertas y casos de riesgo. Datos mockeados: vendrán del endpoint
        <code class="text-fg-brand">/central-de-riesgo</code>.
    </p>

    <div class="mt-6">
        <x-ui.card title="Alertas de riesgo (mock)">
            <ul class="divide-y divide-default">
                @foreach ([
                    ['Bruno Díaz', 'Cambio de pestaña x3', 'Crítica'],
                    ['Diego Soto', 'Pantalla extra detectada', 'Alta'],
                    ['Ernesto Vera', 'Tiempos de respuesta anómalos', 'Media'],
                ] as [$estudiante, $motivo, $severidad])
                    <li class="flex items-center justify-between py-3">
                        <div>
                            <p class="font-medium text-heading">{{ $estudiante }}</p>
                            <p class="text-sm text-body">{{ $motivo }}</p>
                        </div>
                        <span @class([
                            'text-xs font-medium px-1.5 py-0.5 rounded-full',
                            'bg-danger-soft text-fg-danger-strong' => $severidad === 'Crítica',
                            'bg-warning-soft text-fg-warning' => $severidad === 'Alta',
                            'bg-status-en-revision-bg text-status-en-revision-fg' => $severidad === 'Media',
                        ])>{{ $severidad }}</span>
                    </li>
                @endforeach
            </ul>
        </x-ui.card>
    </div>
@endsection