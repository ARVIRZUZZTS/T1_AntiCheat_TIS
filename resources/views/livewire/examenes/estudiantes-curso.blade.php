{{--
    @file    estudiantes-curso.blade.php
    @author  Equipo T1 <dev@techone.local>
    @created 2026-09-24
    @updated 2026-09-24

    @description
    Vista del componente Livewire EstudiantesCurso: encabezado del curso,
    buscador por nombre/código SIS, botones de filtro por estado con
    contadores y tabla de estudiantes con su estado de habilitación y
    observaciones de la central de riesgos. Toda la interacción ocurre
    vía Livewire sin recargar la página.

    @see  App\Livewire\Examenes\EstudiantesCurso
--}}

@php
    use Illuminate\Support\Facades\Blade;

    $entradasValidas = $mensajeError === '';

    $sinResultados = $entradasValidas && $busqueda !== '' && $this->estudiantes->total() === 0;

    $estadoBadges = [
        'habilitado' => ['status-habilitado', 'Habilitado'],
        'deshabilitado' => ['status-no-habilitado', 'Deshabilitado'],
    ];

    $observacionBadges = [
        'tramposo' => ['status-central-riesgos', 'Tramposo'],
        'sospechoso' => ['status-central-riesgos', 'Sospechoso'],
        'pendiente' => ['status-pendiente', 'Pendiente'],
        'aula equivocada' => ['status-en-revision', 'Aula equivocada'],
    ];

    $badge = function (?string $clave, array $mapa): string {
        if ($clave === null || ! isset($mapa[$clave])) {
            return '<span class="text-muted">—</span>';
        }

        [$tipo, $texto] = $mapa[$clave];

        return Blade::render('<x-ui.badge type="' . e($tipo) . '">' . e($texto) . '</x-ui.badge>');
    };

    $filas = [];
    if ($entradasValidas) {
        $filas = collect($this->estudiantes->items())
            ->map(function (array $estudiante, int $indice) use ($badge, $estadoBadges, $observacionBadges): array {
                $nombreCompleto = trim(($estudiante['nombre'] ?? '') . ' ' . ($estudiante['apellido'] ?? ''));
                $avatar = Blade::render('<x-ui.avatar name="' . e($nombreCompleto !== '' ? $nombreCompleto : 'na') . '" />');

                return [
                    ['value' => (string) ($this->estudiantes->firstItem() + $indice)],
                    ['heading' => true, 'raw' => true, 'value' => '<span class="inline-flex items-center gap-3">' . $avatar . '<span>' . e($nombreCompleto) . '</span></span>'],
                    $estudiante['sis'] ?? '—',
                    ['value' => $badge($estudiante['estado'] ?? null, $estadoBadges), 'raw' => true],
                    $estudiante['motivo'] ?? '—',
                    ['value' => $badge($estudiante['observacion'] ?? null, $observacionBadges), 'raw' => true],
                ];
            })
            ->values()
            ->all();
    }
@endphp

<div class="p-4 sm:p-6 space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-2xl sm:text-3xl font-semibold text-heading">{{ $curso->nombre_curso }}</h1>
            <p class="text-sm text-muted mt-1">Estudiantes del curso y su estado en el examen actual</p>
        </div>
        <x-ui.button variant="secondary" wire:click="limpiar">Limpiar filtros</x-ui.button>
    </div>

    @if ($this->esAuxiliar)
        <x-ui.alert type="warning" title="Modo solo lectura">
            Como auxiliar puedes consultar los filtros; el estado se edita desde la vista de examen.
        </x-ui.alert>
    @endif

    @if ($mensajeError)
        <x-ui.alert type="danger" title="Elemento inválido">
            {{ $mensajeError }}
        </x-ui.alert>
    @elseif ($sinResultados)
        <x-ui.alert title="Sin resultados">
            No se encontraron resultados para la búsqueda
        </x-ui.alert>
    @endif

    <x-ui.search-input
        name="busqueda"
        label="Buscar estudiante"
        placeholder="Nombre o código SIS"
        buttonLabel="Buscar"
        wire:model.lazy="busqueda"
        wireSubmit="buscar"
    />

    <div class="flex flex-wrap gap-2">
        @foreach ($this->filtros as $clave => $etiqueta)
            <x-ui.button
                :key="$clave"
                :variant="$estado === $clave ? 'default' : 'secondary'"
                wire:click="filtrar('{{ $clave }}')"
            >
                {{ $etiqueta }} ({{ $this->conteos[$clave] }})
            </x-ui.button>
        @endforeach
    </div>

    @if ($entradasValidas)
        <x-ui.table
            :headers="['#', 'Estudiante', 'Código SIS', 'Estado', 'Motivo', 'Observación']"
            :rows="$filas"
        />

        @if ($this->estudiantes->total() === 0 && ! $sinResultados)
            <p class="text-sm text-muted">No hay estudiantes para mostrar.</p>
        @endif

        @if ($this->estudiantes->total() > 0 && $this->estudiantes->lastPage() > 1)
            <x-ui.pagination
                :interactive="true"
                :current="$this->estudiantes->currentPage()"
                :total="$this->estudiantes->lastPage()"
            />
        @endif
    @endif
</div>