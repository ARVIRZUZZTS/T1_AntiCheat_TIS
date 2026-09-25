@extends('layouts.app')

@section('title', 'Inicio')

@section('content')
    <p class="text-body">
        Vista general del panel. Los valores son mockeados: se conectarán a los endpoints
        (<code class="text-fg-brand">/dashboard/stats</code>, <code class="text-fg-brand">/examenes</code>).
    </p>

    <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="bg-neutral-primary-soft p-6 border border-default rounded-base shadow-xs">
            <p class="text-sm text-body">Exámenes programados</p>
            <p class="mt-1 text-3xl font-semibold text-fg-brand">3</p>
        </div>
        <div class="bg-neutral-primary-soft p-6 border border-default rounded-base shadow-xs">
            <p class="text-sm text-body">Estudiantes en línea</p>
            <p class="mt-1 text-3xl font-semibold text-fg-brand">148</p>
        </div>
        <div class="bg-neutral-primary-soft p-6 border border-default rounded-base shadow-xs">
            <p class="text-sm text-body">Alertas críticas</p>
            <p class="mt-1 text-3xl font-semibold text-fg-danger-strong">2</p>
        </div>
        <div class="bg-neutral-primary-soft p-6 border border-default rounded-base shadow-xs">
            <p class="text-sm text-body">Sesiones activas</p>
            <p class="mt-1 text-3xl font-semibold text-fg-brand">5</p>
        </div>
    </div>

    <div class="mt-6">
        <x-ui.card title="Próximos exámenes (mock)">
            <ul class="divide-y divide-default">
                @foreach ([
                    ['Parcial 1 Matemática', 'Matemática I', '2026-10-05', 'Habilitado'],
                    ['Examen Final Física', 'Física II', '2026-10-12', 'Habilitado'],
                    ['Recuperatorio Química', 'Química Orgánica', '2026-10-19', 'Pendiente'],
                ] as [$examen, $materia, $fecha, $estado])
                    <li class="flex items-center justify-between py-3">
                        <div>
                            <p class="font-medium text-heading">{{ $examen }}</p>
                            <p class="text-sm text-body">{{ $materia }} · {{ $fecha }}</p>
                        </div>
                        <span @class([
                            'text-xs font-medium px-1.5 py-0.5 rounded-full',
                            'bg-status-habilitado-bg text-status-habilitado-fg' => $estado === 'Habilitado',
                            'bg-status-pendiente-bg text-status-pendiente-fg' => $estado === 'Pendiente',
                        ])>{{ $estado }}</span>
                    </li>
                @endforeach
            </ul>
        </x-ui.card>
    </div>
@endsection