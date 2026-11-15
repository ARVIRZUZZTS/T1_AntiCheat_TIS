{{--
    @file    acceso-monitor.blade.php
    @author  David E. Chavez T. <virzuzz12345@gmail.com>
    @created 2026-10-19

    @description
    Vista Livewire de "Ingreso" (Monitor en vivo): la lista de próximos exámenes,
    con la misma línea de diseño que las tarjetas del monitor (mismo tamaño de
    letra y componentes). El que está por iniciar se resalta y, cuando el examen
    es de hoy y ya se puede registrar, muestra el botón "Iniciar", que abre la
    vista de registro de ingreso (el monitor en vivo) sin cambiar su
    funcionamiento. El layout lo pone el componente con
    render()->extends('layouts.app'); esta vista NO debe volver a extenderlo. El
    título lo muestra x-ui.page-header vía @section('title').

    @changelog
    - 2026-10-19  [David E. Chavez T.]  feat: creación de la vista de acceso.
    - 2026-11-15  [Diego Tejerina]  fix: se quita el @extends duplicado (sidebar
      doble) y el título repetido.
    - 2026-11-15  [Diego Tejerina]  refactor: la vista queda solo con los
      próximos exámenes en tarjetas con la línea del monitor (mismo tamaño y
      componentes); el examen de hoy muestra "Iniciar". Se quitan el detalle
      suelto y la lista de auxiliares, que no eran de Ingreso.

    @see  App\Livewire\Monitoreo\AccesoMonitor
    @see  resources/views/livewire/monitoreo/monitor-en-vivo.blade.php
--}}

@section('title', 'Ingreso')

@php
    use Illuminate\Support\Carbon;

    $proximos = $this->proximosExamenes();

    $etiquetasEstado = [
        'proximo' => 'Próximo',
        'registro_abierto' => 'Por iniciar',
        'en_curso' => 'En curso',
        'finalizado' => 'Finalizado',
    ];
@endphp

<div class="flex flex-col gap-6 px-[2vh] mt-[2vh]">
    {{-- Barra de simulación de hora: solo una ayuda de prueba para ver los
         estados sin esperar a la hora real. --}}
    <div class="flex justify-end gap-2">
        <a href="{{ route('monitoreo.ingreso', ['simular' => 30]) }}" class="px-2 py-1 text-xs text-white bg-brand rounded">30</a>
        <a href="{{ route('monitoreo.ingreso', ['simular' => 15]) }}" class="px-2 py-1 text-xs text-white bg-brand rounded">15</a>
        <a href="{{ route('monitoreo.ingreso', ['simular' => 5]) }}" class="px-2 py-1 text-xs text-white bg-brand rounded">5</a>
        <a href="{{ route('monitoreo.ingreso', ['simular' => 0]) }}" class="px-2 py-1 text-xs text-white bg-brand rounded">Ahora</a>
    </div>

    <h2 class="text-lg font-semibold text-heading">Próximos exámenes</h2>

    @if ($proximos->isEmpty())
        <p class="rounded-base border border-default bg-neutral-primary-soft p-8 text-center text-sm text-body shadow-xs">
            No hay exámenes próximos.
        </p>
    @else
        <ul class="flex flex-col gap-4">
            @foreach ($proximos as $examen)
                @php
                    $destacado = $examen['estado'] === 'registro_abierto';
                    $puedeIniciar = $examen['es_hoy'] && in_array($examen['estado'], ['registro_abierto', 'en_curso'], true);
                @endphp
                <li @class([
                        'flex flex-col gap-4 border bg-neutral-primary-soft rounded-base shadow-xs px-5 py-6 sm:px-8',
                        'border-brand ring-2 ring-brand' => $destacado,
                        'border-default' => ! $destacado,
                    ])>
                    <div style="display:flex; justify-content:space-between; width:100%; gap:1rem; flex-wrap:wrap; align-items:center;">
                        <div class="flex items-center gap-4 lg:w-72">
                            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-lg bg-brand-softer text-fg-brand-strong">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-6 w-6" aria-hidden="true"><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/><path d="M10 9H8"/><path d="M16 13H8"/><path d="M16 17H8"/></svg>
                            </div>
                            <div class="flex min-w-0 flex-col items-start gap-1">
                                <h3 class="text-xl font-bold text-heading">{{ $examen['tipo'] }}</h3>
                                <span class="inline-flex items-center px-1.5 py-0.5 text-xs font-medium rounded-full border bg-neutral-secondary-medium border-default-medium text-heading">
                                    {{ $etiquetasEstado[$examen['estado']] ?? '—' }}
                                </span>
                            </div>
                        </div>

                        <div class="font-bold text-heading">
                            Faltan: {{ $examen['tiempo_restante'] }}
                        </div>

                        <div class="flex flex-col gap-1 text-sm text-body">
                            <span>Materia: {{ $examen['materias'] ?: '—' }}</span>
                            <span>Fecha: {{ Carbon::parse($examen['fecha'])->format('d-m-Y') }}</span>
                            <span>Horario: {{ $examen['hora_inicio'] }}{{ $examen['hora_fin'] ? ' - '.$examen['hora_fin'] : '' }}</span>
                            <span>Ambientes: {{ $examen['ambientes'] ?: '—' }}</span>
                        </div>
                    </div>

                    @if ($puedeIniciar)
                        <div class="flex justify-end">
                            <x-ui.button variant="default" :href="route('monitoreo')">Iniciar</x-ui.button>
                        </div>
                    @endif
                </li>
            @endforeach
        </ul>
    @endif
</div>
