@extends('layouts.app')

@section('title', 'Acceso al monitor')

@php
    $examen = $this->examen();
    $estado = $this->estado();
    $minutos = $this->minutosParaInicio();
@endphp

<div class="flex flex-col gap-6 px-[2vh] mt-[2vh]">
    <h2 class="text-xl font-semibold text-heading">Acceso al monitor en vivo</h2>

    <div class="flex justify-end gap-2">
        <a href="{{ route('monitoreo.acceso', ['simular' => 30]) }}" class="px-2 py-1 text-xs text-white bg-brand rounded">30</a>
        <a href="{{ route('monitoreo.acceso', ['simular' => 15]) }}" class="px-2 py-1 text-xs text-white bg-brand rounded">15</a>
        <a href="{{ route('monitoreo.acceso', ['simular' => 5]) }}" class="px-2 py-1 text-xs text-white bg-brand rounded">5</a>
        <a href="{{ route('monitoreo.acceso', ['simular' => 0]) }}" class="px-2 py-1 text-xs text-white bg-brand rounded">Ahora</a>
    </div>

    @if ($examen === null)
        <p class="text-body">No hay examen próximo.</p>
    @else
        <div class="bg-neutral-primary-soft border border-default rounded-base shadow-xs p-6">
            <p class="text-sm text-body mb-2">Examen: {{ $examen->cursos->pluck('nombre_curso')->implode(', ') }}</p>
            <p class="text-sm text-body mb-2">Inicio: {{ substr($examen->hora_inicio,0,5) }} · Duración: {{ $examen->duracion }} min</p>

            @if ($estado === 'proximo')
                <p class="text-heading font-semibold">Aún no comienza. Comienza en {{ $minutos }} minutos.</p>
            @elseif ($estado === 'registro_abierto')
                <p class="text-heading font-semibold">Registro de ingresos abierto. Examen en {{ $minutos }} minutos.</p>
            @elseif ($estado === 'en_curso')
                <p class="text-heading font-semibold">Examen en curso.</p>
            @else
                <p class="text-heading font-semibold">Examen finalizado.</p>
            @endif

            @if (in_array($estado, ['registro_abierto', 'en_curso'], true))
                <div class="mt-4">
                    <a href="{{ route('monitoreo') }}" class="px-3 py-1.5 text-xs text-white bg-brand rounded">Ir al monitor en vivo</a>
                </div>
            @endif
        </div>

        <div class="bg-neutral-primary-soft border border-default rounded-base shadow-xs p-6">
            <h3 class="text-lg font-semibold text-heading mb-4">Auxiliares asignados</h3>
            <ul class="list-disc pl-5 text-sm text-body">
                @foreach ($this->auxiliares() as $aux)
                    <li>{{ $aux->nombre_usuario }} {{ $aux->apellido }} ({{ $aux->cod_sis }}) — {{ $aux->nombre_curso }}</li>
                @endforeach
            </ul>
            <div class="mt-4">
                <a href="{{ route('auxiliares') }}" class="px-3 py-2 text-sm text-white bg-brand rounded">Gestionar auxiliares</a>
            </div>
        </div>

        <div class="bg-neutral-primary-soft border border-default rounded-base shadow-xs p-6">
            <h3 class="text-lg font-semibold text-heading mb-4">Próximos 5 exámenes</h3>
            <ul class="space-y-2 text-sm text-body">
                @foreach ($this->proximosExamenes() as $examen)
                    <li class="flex items-center justify-between gap-3">
                        <span>{{ $examen['materias'] ?: '—' }} · {{ $examen['fecha'] }} {{ $examen['hora'] }}</span>
                        <span class="text-xs text-muted">{{ $examen['auxiliares'] }} auxiliares</span>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
</div>