{{--
    @file    central-riesgo.blade.php
    @author  David E. Chavez T. <virzuzz12345@gmail.com>
    @created 2026-09-29
    @updated 2026-09-29

    @description
    Pantalla de la central de riesgo con las alertas abiertas. El botón de
    registrar incidencia abre el formulario sin datos precargados: no viaja
    ningún estudiante, así que quien lo usa busca al estudiante en la base de
    datos y escribe el motivo. El monitor en vivo, en cambio, precarga el
    estudiante de la fila desde la que se pulsa.

    @changelog
    - 2026-09-24  [David E. Chavez T.]  feat: creación inicial de la vista.
    - 2026-09-28  [Candy]  feat: botón de registrar incidencia con entrada a
      `origen=central-riesgo`, sin estudiante ni rol en la URL.
    - 2026-09-29  [David E. Chavez T.]  refactor: header y espaciado consistente.

    @see  \App\Livewire\Monitoreo\RegistrarIncidencia
--}}

@extends('layouts.app')

@section('title', 'Central de riesgo')

@section('content')
    <p class="text-body">
        Alertas y casos de riesgo. Datos mockeados: vendrán del endpoint
        <code class="text-fg-brand">/central-de-riesgo</code>.
    </p>

    <div class="mt-6 flex flex-wrap justify-end px-[2vh]">
        <x-ui.button :href="route('registrar-incidencia', ['origen' => 'central-riesgo', 'usuario' => 1])">
            Registrar incidencia
        </x-ui.button>
    </div>

    <div class="mt-6 px-[2vh]">
        <x-ui.card title="Alertas de riesgo">
            <ul class="divide-y divide-default">
                @forelse ($alertas as $alerta)
                    <li class="flex items-center justify-between py-3">
                        <div>
                            <p class="font-medium text-heading">{{ $alerta->estudiante_nombre }}</p>
                            <p class="text-sm text-body">{{ $alerta->detalle_motivo }}</p>
                        </div>
                        <span @class([
                            'text-xs font-medium px-1.5 py-0.5 rounded-full',
                            'bg-danger-soft text-fg-danger-strong' => $alerta->tipo_infraccion === 'Tramposo',
                            'bg-warning-soft text-fg-warning' => $alerta->tipo_infraccion === 'Sospechoso',
                            'bg-status-en-revision-bg text-status-en-revision-fg' => $alerta->tipo_infraccion === 'Pendiente',
                        ])>{{ $alerta->tipo_infraccion }}</span>
                    </li>
                @empty
                    <li class="text-sm text-body">No hay alertas de riesgo.</li>
                @endforelse
            </ul>
        </x-ui.card>
    </div>
@endsection
