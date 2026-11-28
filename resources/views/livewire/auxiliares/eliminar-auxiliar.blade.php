{{--
    @file    eliminar-auxiliar.blade.php
    @author  Diego Tejerina <josediegotejerinamolina@gmail.com>
    @created 2026-11-27
    @updated 2026-11-28

    @description
    Vista de la subsección "Eliminar auxiliar", con el formato de "Eliminar
    examen": un buscador y la tabla AUXILIAR | CORREO | ASIGNACIONES | ACCIONES
    (8 por página). Cada fila muestra lo que el auxiliar tiene asociado (cursos
    activos, cursos anteriores y exámenes) y la acción "Eliminar". Si tiene algo
    asociado, al presionar "Eliminar" el aviso aparece dentro de su fila y no se
    abre el modal; si no, se abre el modal de confirmación y, al eliminar, el
    modal "Auxiliar eliminado con exito".

    @changelog
    - 2026-11-27  [Diego Tejerina]  feat: creación inicial.
    - 2026-11-28  [Valery D. Ortuno P]  refactor: formato de Eliminar examen
      (tabla con acciones y aviso de bloqueo en la fila), asignaciones activas y
      anteriores, modal de exito; se quitan los filtros de materia y estado.

    @see  app/Livewire/Auxiliares/EliminarAuxiliar.php
    @see  resources/views/components/ui/modal-eliminar-auxiliar.blade.php
    @see  resources/views/pages/examen-eliminar.blade.php
--}}

@section('title', 'Eliminar auxiliar')

<div class="flex flex-col gap-4 px-4 py-4 sm:px-6 sm:py-6 lg:px-8">
    {{-- Buscador: nombre, correo, SIS o carnet, o curso. --}}
    <div class="bg-neutral-primary-soft border border-default rounded-base shadow-xs p-3">
        <x-ui.search-input name="busqueda-eliminar-auxiliar"
            placeholder="Buscar por nombre, correo, SIS, carnet o curso"
            :show-button="false" pill
            wire:model.live.debounce.350ms="busqueda" />
    </div>

    <x-ui.table compact :headers="['AUXILIAR', 'CORREO', 'ASIGNACIONES', 'ACCIONES']">
        <x-slot:body>
            @forelse ($auxiliares as $auxiliar)
                <tr wire:key="eliminar-auxiliar-{{ $auxiliar['id'] }}"
                    @class(['bg-neutral-primary', 'border-b border-default' => ! $loop->last])>
                    <th scope="row" class="px-4 py-2.5 font-medium text-heading">
                        <span class="block wrap-break-word">{{ $auxiliar['nombreCompleto'] }}</span>
                        <span class="mt-0.5 block text-xs text-body">
                            {{ $auxiliar['tipo'] === 'UMSS' ? 'SIS' : 'Carnet' }}: {{ $auxiliar['documento'] !== '' ? $auxiliar['documento'] : '—' }}
                        </span>
                        @if ($avisoId === $auxiliar['id'])
                            <div class="mt-2">
                                <x-ui.alert type="danger" title="No se puede eliminar el auxiliar">
                                    {{ $avisoMensaje }}
                                </x-ui.alert>
                            </div>
                        @endif
                    </th>
                    <td class="px-4 py-2.5 text-body break-all">{{ $auxiliar['correo'] !== '' ? $auxiliar['correo'] : '—' }}</td>
                    <td class="px-4 py-2.5 text-body">
                        @if ($auxiliar['eliminable'])
                            <span class="text-muted">Sin asignaciones</span>
                        @else
                            <ul class="space-y-0.5">
                                @if ($auxiliar['cursosActivos'] !== [])
                                    <li>Cursos activos: {{ implode(', ', $auxiliar['cursosActivos']) }}</li>
                                @endif
                                @if ($auxiliar['cursosPasados'] !== [])
                                    <li>Cursos anteriores: {{ implode(', ', $auxiliar['cursosPasados']) }}</li>
                                @endif
                                @if ($auxiliar['examenes'] > 0)
                                    <li>Examenes asignados: {{ $auxiliar['examenes'] }}</li>
                                @endif
                            </ul>
                        @endif
                    </td>
                    <td class="px-4 py-2.5">
                        <button type="button" wire:click="abrirModal({{ $auxiliar['id'] }})"
                                aria-label="Eliminar a {{ $auxiliar['nombreCompleto'] }}" aria-haspopup="dialog"
                                class="inline-flex min-h-11 shrink-0 items-center px-2 text-sm font-medium text-fg-danger-strong underline-offset-2 hover:underline focus:rounded-base focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-medium">
                            Eliminar
                        </button>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="px-4 py-8 text-center text-sm text-body">
                        {{ $hayAuxiliares ? 'No se encontraron coincidencias' : 'No tienes auxiliares para eliminar.' }}
                    </td>
                </tr>
            @endforelse
        </x-slot:body>
    </x-ui.table>

    @if ($totalPaginas > 1)
        <nav class="flex items-center justify-between gap-3" aria-label="Paginacion">
            <p class="text-sm text-body">Pagina {{ $pagina }} de {{ $totalPaginas }} · {{ $total }} auxiliares</p>
            <div class="flex gap-2">
                <x-ui.button type="button" variant="secondary" wire:click="irAPagina({{ $pagina - 1 }})" :disabled="$pagina <= 1">
                    Anterior
                </x-ui.button>
                <x-ui.button type="button" variant="secondary" wire:click="irAPagina({{ $pagina + 1 }})" :disabled="$pagina >= $totalPaginas">
                    Siguiente
                </x-ui.button>
            </div>
        </nav>
    @endif

    <x-ui.modal-eliminar-auxiliar
        id="modal-eliminar-auxiliar"
        :abierto="$modalAbierto"
        :nombre="$auxiliarNombre"
        :eliminando="$eliminando"
        :error="$errorEliminar"
    />

    {{-- Modal de exito, como el de Eliminar examen. --}}
    @if ($mostrarExito)
        <div role="dialog" aria-modal="true" aria-labelledby="titulo-exito-eliminar-auxiliar"
             wire:click.self="cerrarAviso"
             class="overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 flex justify-center items-center w-full md:inset-0 h-full max-h-full bg-overlay-modal/50">
            <div class="relative p-4 w-full max-w-md max-h-full">
                <div class="relative bg-neutral-primary-soft border border-default rounded-base shadow-sm p-4 md:p-6 text-center">
                    <h3 id="titulo-exito-eliminar-auxiliar" class="text-lg font-medium text-heading">{{ $mensajeExito }}</h3>
                    <p class="mt-2 text-sm text-body">El auxiliar ya no podra ingresar al sistema.</p>
                    <div class="mt-4">
                        <button type="button" wire:click="cerrarAviso"
                                class="inline-flex items-center justify-center rounded-base border border-transparent bg-brand px-4 py-2 text-sm font-medium text-white hover:bg-brand-strong">
                            Aceptar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
