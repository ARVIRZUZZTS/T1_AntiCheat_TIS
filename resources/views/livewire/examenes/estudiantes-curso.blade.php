{{--
    @file    estudiantes-curso.blade.php
    @author  Diego Tejerina <josediegotejerinamolina@gmail.com>
    @created 2026-09-24
    @updated 2026-09-25

    @description
    Vista del componente Livewire EstudiantesCurso: encabezado del curso,
    buscador por nombre/código SIS, botones de filtro por estado con
    contadores y tabla de estudiantes con su estado de habilitación y
    observaciones de la central de riesgos. Toda la interacción ocurre
    vía Livewire sin recargar la página. En desktop la vista completa
    cabe en el viewport (h-screen/overflow-hidden heredado del layout);
    en mobile se mantiene el scroll natural de la lista.

    @see  App\Livewire\Examenes\EstudiantesCurso
--}}

@php
    use Illuminate\Support\Facades\Blade;

    $entradasValidas = $mensajeError === '';
    $sinResultados = $entradasValidas && $busqueda !== '' && $this->estudiantes->total() === 0;

    $docenteNombre = trim(($curso->docente?->nombre_usuario ?? '') . ' ' . ($curso->docente?->apellido ?? ''));
    $subtitulo = collect([
        $docenteNombre !== '' ? 'Docente: ' . $docenteNombre : null,
        $curso->estado === 'EnCurso' ? 'En curso' : 'Finalizado',
    ])->filter()->implode(' · ');

    $estadoBadges = [
        'habilitado' => ['status-habilitado', 'Habilitado'],
        'deshabilitado' => ['status-no-habilitado', 'Deshabilitado'],
    ];

    // Sospechoso/Tramposo reutilizan los mismos tokens de color que ya usa
    // pages/monitoreo.blade.php, para que ambas pantallas se vean consistentes.
    $observacionBadges = [
        'sospechoso' => ['status-en-revision', 'Sospechoso'],
        'tramposo' => ['status-central-riesgos', 'Tramposo'],
        'pendiente' => ['status-pendiente', 'Pendiente'],
        'aula equivocada' => ['gray', 'Aula equivocada'],
    ];

    $badge = function (?string $clave, array $mapa, string $vacio = 'Ninguna', string $tipoVacio = 'status-ya-registrado'): string {
        [$tipo, $texto] = ($clave !== null && isset($mapa[$clave])) ? $mapa[$clave] : [$tipoVacio, $vacio];

        return Blade::render('<x-ui.badge type="' . e($tipo) . '">' . e($texto) . '</x-ui.badge>');
    };

    $filas = [];
    if ($entradasValidas) {
        $filas = collect($this->estudiantes->items())
            ->map(function (array $estudiante, int $indice) use ($badge, $estadoBadges, $observacionBadges): array {
                $nombreCompleto = trim(($estudiante['nombre'] ?? '') . ' ' . ($estudiante['apellido'] ?? ''));
                $observacion = $estudiante['observacion'] ?? null;

                $tono = match ($observacion) {
                    'sospechoso' => 'sospechoso',
                    'tramposo' => 'tramposo',
                    default => 'default',
                };

                $rowClass = match ($observacion) {
                    'sospechoso' => 'bg-status-en-revision-bg',
                    'tramposo' => 'bg-status-central-riesgos-bg',
                    default => null,
                };

                $avatar = Blade::render(
                    '<x-ui.avatar name="' . e($nombreCompleto !== '' ? $nombreCompleto : 'na') . '" tone="' . e($tono) . '" size="sm" class="shrink-0" />'
                );

                return [
                    '__rowClass' => $rowClass,
                    ['value' => (string) ($this->estudiantes->firstItem() + $indice)],
                    [
                        'heading' => true,
                        'html' => '<span class="inline-flex items-center gap-3">' . $avatar . '<span class="font-semibold text-heading">' . e($nombreCompleto) . '</span></span>',
                    ],
                    ['html' => '<span class="font-mono">' . e($estudiante['sis'] ?? '—') . '</span>'],
                    ['html' => $badge($estudiante['estado'] ?? null, $estadoBadges, 'Sin estado', 'status-pendiente')],
                    $estudiante['motivo'] ?? '—',
                    ['html' => $badge($observacion, $observacionBadges)],
                ];
            })
            ->values()
            ->all();
    }
@endphp

<div class="flex flex-col lg:h-full lg:overflow-hidden">
    {{-- Encabezado --}}
    <div class="shrink-0 flex items-start sm:items-center justify-between gap-3 px-4 sm:px-6 pb-4 border-b border-default bg-neutral-primary-soft" style="padding-top: max(1rem, env(safe-area-inset-top));">
        <div class="flex items-center gap-3 min-w-0">
            <a href="{{ route('materias') }}" class="shrink-0 inline-flex items-center justify-center w-9 h-9 rounded-full text-body hover:bg-neutral-secondary-medium hover:text-heading focus:outline-none" aria-label="Volver a materias">
                <svg class="w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m15 19-7-7 7-7"/></svg>
            </a>
            <div class="min-w-0">
                <h1 class="text-xl sm:text-2xl font-semibold text-heading truncate">{{ $curso->nombre_curso }}</h1>
                @if ($subtitulo !== '')
                    <p class="text-sm text-muted truncate">{{ $subtitulo }}</p>
                @endif
            </div>
        </div>

        <x-ui.button variant="tertiary" pill class="shrink-0">
            <svg class="w-4 h-4 me-1.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 16V4m0 0L8 8m4-4 4 4M4 16v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2"/></svg>
            Cargar CSV
        </x-ui.button>
    </div>

    {{-- Cuerpo --}}
    <div class="flex-1 lg:min-h-0 flex flex-col gap-4 p-4 sm:p-6 lg:overflow-hidden">
        {{-- Barra de busqueda + filtros --}}
        <div class="shrink-0 bg-neutral-primary-soft border border-default rounded-base shadow-xs p-3 flex flex-col sm:flex-row sm:items-center gap-3">
            <div class="flex-1 min-w-0">
                <x-ui.search-input
                    name="busqueda"
                    placeholder="Buscar por nombre o código SIS"
                    :show-button="false"
                    pill
                    wire:model.lazy="busqueda"
                    wireSubmit="buscar"
                />
            </div>

            <div class="flex flex-wrap gap-2 shrink-0">
                @foreach ($this->filtros as $clave => $etiqueta)
                    <x-ui.button
                        :key="$clave"
                        :variant="$estado === $clave ? 'default' : 'tertiary'"
                        pill
                        size="sm"
                        wire:click="filtrar('{{ $clave }}')"
                    >
                        {{ $etiqueta }} {{ $this->conteos[$clave] }}
                    </x-ui.button>
                @endforeach
            </div>
        </div>

        @if ($this->esAuxiliar)
            <x-ui.alert type="warning" title="Modo solo lectura" class="shrink-0">
                Como auxiliar puedes consultar los filtros; el estado se edita desde la vista de examen.
            </x-ui.alert>
        @endif

        @if ($mensajeError)
            <x-ui.alert type="danger" title="Elemento inválido" class="shrink-0">
                {{ $mensajeError }}
            </x-ui.alert>
        @elseif ($sinResultados)
            <x-ui.alert title="Sin resultados" class="shrink-0">
                No se encontraron resultados para la búsqueda
            </x-ui.alert>
        @endif

        @if ($entradasValidas)
            <div class="flex-1 lg:min-h-0 lg:overflow-y-auto">
                <x-ui.table
                    compact
                    :headers="['#', 'ESTUDIANTE', 'CÓDIGO SIS', 'ESTADO (EXAMEN ACTUAL)', 'MOTIVO', 'OBSERVACIONES']"
                    :rows="$filas"
                />

                @if ($this->estudiantes->total() === 0 && ! $sinResultados)
                    <p class="text-sm text-muted mt-3">No hay estudiantes para mostrar.</p>
                @endif
            </div>

            @if ($this->estudiantes->total() > 0)
                {{-- Mobile: apilado (texto arriba, paginacion abajo, ambos centrados).
                     Desde sm: vuelve al layout de escritorio (texto a la izquierda
                     en absolute, paginacion centrada en la misma fila). --}}
                <div class="shrink-0 flex flex-col items-center gap-2 sm:relative sm:flex-row sm:items-center sm:justify-center sm:gap-0 sm:min-h-[2rem]">
                    <p class="text-sm text-muted text-center sm:absolute sm:left-0 sm:text-left">
                        Mostrando {{ $this->estudiantes->count() }} de {{ $this->estudiantes->total() }} estudiantes
                    </p>

                    @if ($this->estudiantes->lastPage() > 1)
                        <x-ui.pagination
                            :interactive="true"
                            :active-filled="true"
                            size="sm"
                            :current="$this->estudiantes->currentPage()"
                            :total="$this->estudiantes->lastPage()"
                        />
                    @endif
                </div>
            @endif
        @endif
    </div>
</div>
