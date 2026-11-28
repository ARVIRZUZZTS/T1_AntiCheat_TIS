{{--
    @file    eliminar-auxiliar.blade.php
    @author  Diego Tejerina <josediegotejerinamolina@gmail.com>
    @created 2026-11-27

    @description
    Vista de la subsección "Eliminar auxiliar": búsqueda dinámica, filtro por
    curso, filtro por fecha de creación (desde/hasta) y botones de estado
    (Todos / Habilitados / Deshabilitados), sobre una lista paginada (8 por
    página) de los auxiliares que el usuario puede eliminar. Cada fila solo tiene
    "Eliminar", que abre el modal de confirmación.

    @see  app/Livewire/Auxiliares/EliminarAuxiliar.php
    @see  resources/views/components/ui/modal-eliminar-auxiliar.blade.php
--}}

@section('title', 'Eliminar auxiliar')

<div class="flex flex-col gap-6 px-[2vh] mt-[2vh]">
    {{-- Buscador dinamico + filtros (se combinan entre si). --}}
    <div class="bg-neutral-primary-soft border border-default rounded-base shadow-xs p-3 flex flex-col gap-3 lg:flex-row lg:items-end">
        <div class="flex-1 min-w-0">
            <x-ui.search-input name="busqueda-eliminar-auxiliar"
                placeholder="Buscar por nombre, correo, SIS o carnet"
                :show-button="false" pill
                wire:model.live.debounce.350ms="busqueda" />
        </div>

        <div class="w-full lg:w-64">
            <x-ui.select name="filtroCurso" label="Materia"
                wire:model.live="filtroCurso"
                placeholder="Todas las materias"
                :options="$cursos"
                :selected="$filtroCurso" />
        </div>

        <div class="flex gap-2 overflow-x-auto lg:shrink-0" role="group" aria-label="Filtro de estado">
            @foreach (['todos' => 'Todos', 'Habilitado' => 'Habilitados', 'Deshabilitado' => 'Deshabilitados'] as $valor => $etiqueta)
                <x-ui.button type="button" variant="{{ $filtroEstado === $valor ? 'default' : 'secondary' }}"
                             wire:click="filtrarEstado('{{ $valor }}')">
                    {{ $etiqueta }}
                </x-ui.button>
            @endforeach
        </div>
    </div>

    <div class="bg-neutral-primary-soft border border-default rounded-base shadow-xs p-4 md:p-6">
        <h3 class="text-lg font-semibold text-heading mb-4">Auxiliares</h3>

        @php
            $filas = $auxiliares->map(function (array $a) {
                $nombreHtml = '<span class="font-medium text-heading break-words">'.e($a['nombreCompleto']).'</span>';
                $correoHtml = '<span class="break-all">'.($a['correo'] !== '' ? e($a['correo']) : '—').'</span>';
                $documento = $a['documento'] !== '' ? e($a['documento']) : '—';

                $asignacionesHtml = $a['asignaciones'] === []
                    ? '<span class="text-muted">Sin asignaciones</span>'
                    : '<ul class="space-y-0.5">'.implode('', array_map(fn (string $t): string => '<li>'.e($t).'</li>', $a['asignaciones'])).'</ul>';

                $eliminar = '<button type="button" wire:click="abrirModal('.$a['id'].')" aria-label="Eliminar a '.e($a['nombreCompleto']).'" aria-haspopup="dialog" class="inline-flex min-h-11 shrink-0 items-center px-2 text-sm font-medium text-fg-danger-strong underline-offset-2 hover:underline focus:rounded-base focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-medium">Eliminar</button>';

                return [
                    ['html' => '<div class="min-w-[9rem] max-w-[14rem]">'.$nombreHtml.'</div>'],
                    ['html' => '<div class="max-w-[14rem]">'.$correoHtml.'</div>'],
                    $a['tipo'],
                    $documento,
                    ['html' => '<div class="max-w-[16rem]">'.$asignacionesHtml.'</div>'],
                    $a['estado'],
                    ['html' => $eliminar],
                ];
            })->toArray();
        @endphp

        <x-ui.table compact empty="No se encontraron auxiliares con esos filtros."
            :headers="['Auxiliar', 'Correo', 'Tipo', 'SIS / Carnet', 'Asignaciones', 'Estado', 'Acciones']"
            :rows="$filas" />

        @if ($totalPaginas > 1)
            <nav class="mt-4 flex items-center justify-between gap-3" aria-label="Paginación">
                <p class="text-sm text-body">Página {{ $pagina }} de {{ $totalPaginas }} · {{ $total }} auxiliares</p>
                <div class="flex gap-2">
                    <x-ui.button type="button" variant="secondary"
                                 wire:click="irAPagina({{ $pagina - 1 }})"
                                 :disabled="$pagina <= 1">
                        Anterior
                    </x-ui.button>
                    <x-ui.button type="button" variant="secondary"
                                 wire:click="irAPagina({{ $pagina + 1 }})"
                                 :disabled="$pagina >= $totalPaginas">
                        Siguiente
                    </x-ui.button>
                </div>
            </nav>
        @endif
    </div>

    @if ($mostrarExito)
        <div role="status" aria-live="polite" class="fixed top-4 end-4 z-[60] w-[calc(100%-2rem)] max-w-sm">
            <div class="flex items-center gap-3 rounded-base border border-default bg-neutral-primary-soft p-4 shadow-xs">
                <span class="inline-flex shrink-0 items-center justify-center w-8 h-8 rounded bg-success-soft text-fg-success-strong">
                    <svg class="w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 11.917 9.724 16.5 19 7.5"/></svg>
                </span>
                <p class="min-w-0 flex-1 text-sm text-heading">{{ $mensajeExito }}</p>
                <button type="button" wire:click="cerrarAviso"
                    class="inline-flex shrink-0 items-center justify-center w-8 h-8 rounded-base text-body hover:bg-neutral-secondary-medium hover:text-heading">
                    <span class="sr-only">Cerrar aviso</span>
                    <svg class="w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 17.94 6M18 18 6.06 6"/></svg>
                </button>
            </div>
        </div>
    @endif

    <x-ui.modal-eliminar-auxiliar
        id="modal-eliminar-auxiliar"
        :abierto="$modalAbierto"
        :nombre="$auxiliarNombre"
        :eliminando="$eliminando"
        :error="$errorEliminar"
    />
</div>
