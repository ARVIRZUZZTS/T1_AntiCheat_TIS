{{--
    @file    index.blade.php
    @author  Alisson D. Alvarado <alvaradoalissondalet@gmail.com>
    @created 2026-10-10
    @updated 2026-10-10

    @description
    Vista del componente Livewire de la central de riesgos: tabs de alcance
    (Mis materias / Institución), buscador por nombre o código SIS, la lista de
    estudiantes con incidencia `tramposo` **confirmada** (cards en móvil, tabla
    en desktop) y el banner de bloqueo automático. Los datos salen de
    ListarIncidenciasTramposoService: ya no hay mocks.

    La tabla de desktop replica la especificación: columnas ESTUDIANTE (~40%),
    SIS, MATERIA y ESTADO (todas centradas salvo Estudiante); en la celda del
    estudiante van el avatar con iniciales, el nombre y el texto "Toca para ver
    el detalle". En móvil ese texto se omite y, en su lugar, cada card lleva
    abajo el enlace rojo "Ver detalle ›".

    El título se declara como sección porque el componente delega el layout con
    `->extends('layouts.app')`; el `<div>` raíz va solo, sin `@section('content')`,
    porque Livewire envuelve el cuerpo del componente en esa sección.

    @changelog
    - 2026-10-10  [Alisson D. Alvarado]  feat: la vista consume el servicio real
      y se corrigen los errores estéticos: tabs y buscador conectados.
    - 2026-10-10  [Alisson D. Alvarado]  style: más aire entre el encabezado, las
      pestañas y el buscador, y contador de resultados en la lista.
    - 2026-10-11  [Alisson D. Alvarado]  feat: la tabla vuelve a la
      especificación (ESTUDIANTE, SIS, MATERIA, ESTADO) usando el `estado` real
      del endpoint; el "Ver detalle" apunta a `incidencias.detalle` en vez de
      `#` y, en móvil, aparece como enlace rojo al pie de cada card.
    - 2026-10-11  [Alisson D. Alvarado]  fix: solo se listan los registros
      confirmados (el componente filtra por estado "Confirmado").

    @see  App\Livewire\CentralRiesgo\Index
    @see  App\Services\CentralRiesgo\ListarIncidenciasTramposoService
--}}

@section('title', 'Central de riesgos')

@php
    use App\Services\CentralRiesgo\ListarIncidenciasTramposoService;

    $alcanceActual = $this->alcance;
    $incidencias = $this->incidencias;
    $hayBusqueda = trim($busqueda) !== '';
    $total = $incidencias->count();

    $alcances = [
        ListarIncidenciasTramposoService::ALCANCE_MIS_MATERIAS => 'Mis materias',
        ListarIncidenciasTramposoService::ALCANCE_INSTITUCION => 'Institución',
    ];

    // Título de la lista según el alcance; el texto coincide con la pestaña
    // activa para que el panel no quede con un encabezado fijo equivocado.
    $tituloLista = $alcanceActual === ListarIncidenciasTramposoService::ALCANCE_MIS_MATERIAS
        ? 'Estudiantes en mis materias'
        : 'Estudiantes en la institución';

    // Iniciales para el avatar; se calculan una sola vez por nombre.
    $iniciales = static function (string $nombre): string {
        return implode('', array_map(
            fn (string $parte) => mb_strtoupper(mb_substr($parte, 0, 1)),
            preg_split('/\s+/', trim($nombre)) ?: []
        ));
    };

    // Color de la píldora de estado: rojo para "Confirmado" y neutro para el
    // resto de estados (p. ej. "Pendiente").
    $estadoTipo = static fn (string $estado): string => $estado === 'Confirmado'
        ? 'danger'
        : 'status-pendiente';

    $filas = $incidencias->map(static function (array $incidencia) use ($iniciales): array {
        $nombre = trim(($incidencia['nombre'] ?? '').' '.($incidencia['apellido'] ?? ''));

        return [
            'id' => $incidencia['id'] ?? null,
            'nombre' => $nombre,
            'iniciales' => $iniciales($nombre),
            'sis' => $incidencia['sis'] ?? '—',
            'materia' => $incidencia['materia'] ?? '—',
            'estado' => (string) ($incidencia['estado'] ?? '') ?: 'Pendiente',
        ];
    })->all();
@endphp

<div class="px-4 pt-6 pb-8 sm:px-6 lg:px-[2vh]" x-data="@include('partials.busqueda-estudiante')">
    <div class="flex flex-wrap items-center justify-between gap-4">
        {{-- Tabs tipo píldora: cambian el alcance real del listado. --}}
        <div class="flex gap-2" role="tablist" aria-label="Ámbito de la central">
            @foreach ($alcances as $clave => $etiqueta)
                @php $activo = $alcanceActual === $clave; @endphp
                <button type="button" role="tab"
                        wire:click="cambiarAlcance('{{ $clave }}')"
                        aria-selected="{{ $activo ? 'true' : 'false' }}"
                        @class([
                            'inline-flex items-center justify-center rounded-full border px-5 py-2.5 text-sm font-semibold shadow-xs focus:outline-none focus:ring-4 focus:ring-brand-medium cursor-pointer',
                            'bg-brand text-white border-transparent' => $activo,
                            'bg-neutral-secondary-medium text-body border-neutral-quaternary' => ! $activo,
                        ])>
                    {{ $etiqueta }}
                </button>
            @endforeach
        </div>

        {{-- Acceso al registro de incidencia desde la central de riesgo: abre el
             formulario en blanco (origen=central-riesgo) para que la persona
             busque al estudiante con la lupa. --}}
        <x-ui.button :href="route('registrar-incidencia', ['origen' => 'central-riesgo'])" size="sm">
            Registrar incidencia
        </x-ui.button>
    </div>

    {{-- Buscador conectado a Livewire: el filtro lo aplica el servicio. --}}
    <div class="mt-8 max-w-xl">
        <x-ui.search-input
            name="busqueda"
            placeholder="Buscar por nombre o código SIS"
            :show-button="false"
            wire:model.lazy="busqueda"
            wireSubmit="buscar"
            @input="$event.target.value = sanitizarBusqueda($event.target.value)"
            x-bind:maxlength="maximoBusqueda()"
        />
    </div>

    @if ($mensajeError !== '')
        <x-ui.alert type="danger" title="Búsqueda inválida" class="mt-6">
            {{ $mensajeError }}
        </x-ui.alert>
    @endif

    @if ($total === 0)
        <p class="mt-8 rounded-xl border border-default bg-neutral-primary-soft p-8 text-center text-sm text-body">
            {{ $hayBusqueda
                ? 'No se encontró ningún estudiante para esa búsqueda.'
                : 'No hay estudiantes con incidencias de tipo tramposo.' }}
        </p>
    @else
        {{-- Móvil: cards de estudiantes. --}}
        <div class="mt-8 lg:hidden">
            <p class="text-xs font-medium uppercase tracking-wider text-muted">
                {{ $tituloLista }} · {{ $total }}
            </p>

            <div class="mt-4 space-y-3">
                @foreach ($filas as $fila)
                    <article class="rounded-xl border border-neutral-quaternary bg-neutral-primary-soft p-4">
                        <div class="flex items-center gap-3">
                            <span class="inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-status-central-riesgos-bg text-[10px] font-semibold text-status-central-riesgos-fg">
                                {{ $fila['iniciales'] }}
                            </span>
                            <div class="min-w-0">
                                <p class="truncate text-xs font-bold text-fg-brand">{{ $fila['nombre'] }}</p>
                                <p class="truncate text-[11px] text-body">{{ $fila['sis'] }} · {{ $fila['materia'] }}</p>
                            </div>
                        </div>
                        <div class="mt-3 flex items-center justify-between gap-3">
                            <x-ui.badge :type="$estadoTipo($fila['estado'])" class="font-semibold text-[11px]">
                                {{ $fila['estado'] }}
                            </x-ui.badge>
                            <a href="{{ route('incidencias.detalle', $fila['id']) }}"
                               class="text-xs font-bold text-fg-danger-strong hover:underline">
                                Ver detalle ›
                            </a>
                        </div>
                    </article>
                @endforeach
            </div>
        </div>

        {{-- Desktop: panel con tabla. --}}
        <div class="mt-8 hidden lg:block">
            <div class="overflow-hidden rounded-xl border border-neutral-quaternary bg-neutral-primary-soft shadow-xs">
                <div class="flex items-center justify-between border-b border-neutral-quaternary px-4 py-4">
                    <h2 class="text-[15px] font-semibold text-fg-brand">{{ $tituloLista }}</h2>
                    <span class="inline-flex items-center rounded-full bg-neutral-secondary-medium px-2.5 py-0.5 text-xs font-medium text-body">
                        {{ $total }}
                    </span>
                </div>
                <table class="w-full text-left text-sm text-body">
                    <thead>
                        <tr class="border-b border-neutral-quaternary">
                            <th scope="col" class="w-[40%] px-4 py-3 text-[10px] font-medium uppercase tracking-wider text-body">Estudiante</th>
                            <th scope="col" class="px-4 py-3 text-center text-[10px] font-medium uppercase tracking-wider text-body">SIS</th>
                            <th scope="col" class="px-4 py-3 text-center text-[10px] font-medium uppercase tracking-wider text-body">Materia</th>
                            <th scope="col" class="px-4 py-3 text-center text-[10px] font-medium uppercase tracking-wider text-body">Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($filas as $fila)
                            <tr class="h-[60px] border-b border-neutral-quaternary last:border-b-0">
                                <td class="px-4">
                                    <a href="{{ route('incidencias.detalle', $fila['id']) }}" class="flex items-center gap-3">
                                        <span class="inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-status-central-riesgos-bg text-[10px] font-semibold text-status-central-riesgos-fg">
                                            {{ $fila['iniciales'] }}
                                        </span>
                                        <span class="min-w-0">
                                            <span class="block truncate text-xs font-bold text-fg-brand">{{ $fila['nombre'] }}</span>
                                            <span class="block text-[10px] text-muted">Toca para ver el detalle</span>
                                        </span>
                                    </a>
                                </td>
                                <td class="px-4 text-center font-mono text-[11px] text-body">{{ $fila['sis'] }}</td>
                                <td class="px-4 text-center text-[11px] text-body">{{ $fila['materia'] }}</td>
                                <td class="px-4 text-center">
                                    <x-ui.badge :type="$estadoTipo($fila['estado'])" class="font-semibold text-[11px]">
                                        {{ $fila['estado'] }}
                                    </x-ui.badge>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Banner de alerta al final. --}}
        <div class="mt-8 rounded-xl border border-danger-subtle bg-danger-soft p-4">
            <p class="font-semibold text-fg-danger-strong">Bloqueo automático activo</p>
            <p class="mt-1 text-sm text-fg-danger">
                {{ $alcanceActual === ListarIncidenciasTramposoService::ALCANCE_MIS_MATERIAS
                    ? 'Estos estudiantes no podrán ingresar a tus exámenes.'
                    : 'Estos estudiantes no podrán ingresar a los exámenes de la institución.' }}
            </p>
        </div>
    @endif
</div>
