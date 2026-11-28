{{--
    @file    eliminar-estudiantes.blade.php
    @author  Valery D. Ortuno P <valerydariana98@gmail.com>
    @created 2026-11-28

    @description
    Vista de "Cursos > Eliminar estudiantes" (HU 34), con el formato de
    "Eliminar curso": buscador y tabla CURSO | GESTION | ESTUDIANTES | ACCIONES
    (8 por pagina) de los cursos activos con estudiantes cargados. Si la lista
    no se puede eliminar, el aviso sale dentro de la fila; si se puede, el
    modal de confirmacion y, al eliminar, el modal de exito.

    @see  app/Livewire/Cursos/EliminarEstudiantes.php
    @see  resources/views/pages/curso-eliminar.blade.php
--}}

@section('title', 'Eliminar estudiantes')

<div class="flex flex-col gap-4 px-4 py-4 sm:px-6 sm:py-6 lg:px-8">
    <div class="bg-neutral-primary-soft border border-default rounded-base shadow-xs p-3">
        <x-ui.search-input name="busqueda-eliminar-estudiantes"
            placeholder="Buscar por curso o gestion"
            :show-button="false" pill
            wire:model.live.debounce.350ms="busqueda" />
    </div>

    <x-ui.table compact :headers="['CURSO', 'GESTION', 'ESTUDIANTES', 'ACCIONES']">
        <x-slot:body>
            @forelse ($cursos as $curso)
                <tr wire:key="eliminar-estudiantes-{{ $curso['id'] }}"
                    @class(['bg-neutral-primary', 'border-b border-default' => ! $loop->last])>
                    <th scope="row" class="px-4 py-2.5 font-medium text-heading">
                        <span class="block wrap-break-word">{{ $curso['nombre'] }}</span>
                        @if ($avisoId === $curso['id'])
                            <div class="mt-2">
                                <x-ui.alert type="danger" title="No se puede eliminar la lista">
                                    {{ $avisoMensaje }}
                                </x-ui.alert>
                            </div>
                        @endif
                    </th>
                    <td class="px-4 py-2.5 text-body">{{ $curso['gestion'] ?? 'Gestion no registrada' }}</td>
                    <td class="px-4 py-2.5 text-body">{{ $curso['estudiantes'] }}</td>
                    <td class="px-4 py-2.5">
                        <button type="button" wire:click="abrirModal({{ $curso['id'] }})"
                                aria-label="Eliminar estudiantes de {{ $curso['nombre'] }}" aria-haspopup="dialog"
                                class="inline-flex min-h-11 shrink-0 items-center px-2 text-sm font-medium text-fg-danger-strong underline-offset-2 hover:underline focus:rounded-base focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-medium">
                            Eliminar estudiantes
                        </button>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="px-4 py-8 text-center text-sm text-body">
                        {{ $hayCursos ? 'No se encontraron coincidencias' : 'No tienes cursos activos con estudiantes cargados.' }}
                    </td>
                </tr>
            @endforelse
        </x-slot:body>
    </x-ui.table>

    @if ($totalPaginas > 1)
        <nav class="flex items-center justify-between gap-3" aria-label="Paginacion">
            <p class="text-sm text-body">Pagina {{ $pagina }} de {{ $totalPaginas }} · {{ $total }} cursos</p>
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

    {{-- Modal de confirmacion. --}}
    @if ($modalAbierto)
        <div tabindex="-1" role="dialog" aria-modal="true" aria-labelledby="titulo-eliminar-estudiantes"
             wire:click.self="cerrarModal" wire:keydown.escape.window="cerrarModal"
             class="overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 flex justify-center items-center w-full md:inset-0 h-full max-h-full bg-overlay-modal/50">
            <div class="relative p-4 w-full max-w-md max-h-full">
                <div class="relative max-h-[calc(100vh-2rem)] overflow-y-auto bg-neutral-primary-soft border border-default rounded-base shadow-sm p-4 md:p-6">
                    <div class="flex items-center justify-between border-b border-default pb-4 md:pb-5">
                        <h3 id="titulo-eliminar-estudiantes" class="text-lg font-medium text-heading">Eliminar estudiantes</h3>
                        <button type="button" wire:click="cerrarModal" wire:loading.attr="disabled" wire:target="eliminar"
                                class="text-body bg-transparent hover:bg-neutral-tertiary hover:text-heading rounded-base text-sm w-11 h-11 ms-auto inline-flex justify-center items-center cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed">
                            <svg class="w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 17.94 6M18 18 6.06 6"/></svg>
                            <span class="sr-only">Cerrar modal</span>
                        </button>
                    </div>

                    <div class="space-y-4 py-4">
                        <p class="text-sm text-body">¿Seguro que quieres eliminar la lista de estudiantes de este curso?</p>
                        <p class="text-sm font-semibold text-heading break-words">{{ $cursoNombre }}</p>
                        <p class="text-sm text-body">
                            Se quitaran {{ $cursoEstudiantes === 1 ? '1 estudiante' : $cursoEstudiantes.' estudiantes' }}
                            del curso y sus habilitaciones en los examenes del curso.
                        </p>
                        <p class="text-sm text-fg-danger-strong">Esta accion no se puede deshacer, pero puedes volver a cargar la lista con Cargar CSV.</p>

                        @if ($errorEliminar !== '')
                            <p role="alert" class="text-sm font-medium text-fg-danger-strong">{{ $errorEliminar }}</p>
                        @endif
                    </div>

                    <div class="flex flex-col gap-3 border-t border-default pt-4 sm:flex-row sm:items-center">
                        <x-ui.button variant="danger"
                            class="w-full min-h-11 justify-center disabled:opacity-50 disabled:cursor-not-allowed sm:w-auto"
                            wire:click="eliminar" wire:loading.attr="disabled" wire:target="eliminar">
                            <span wire:loading.remove wire:target="eliminar">Eliminar</span>
                            <span wire:loading wire:target="eliminar">Eliminando…</span>
                        </x-ui.button>
                        <x-ui.button-cancelar
                            class="w-full min-h-11 justify-center disabled:opacity-50 disabled:cursor-not-allowed sm:w-auto"
                            autofocus wire:click="cerrarModal" wire:loading.attr="disabled" wire:target="eliminar">
                            Cancelar
                        </x-ui.button-cancelar>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Modal de exito. --}}
    @if ($mostrarExito)
        <div role="dialog" aria-modal="true" aria-labelledby="titulo-exito-eliminar-estudiantes"
             wire:click.self="cerrarAviso"
             class="overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 flex justify-center items-center w-full md:inset-0 h-full max-h-full bg-overlay-modal/50">
            <div class="relative p-4 w-full max-w-md max-h-full">
                <div class="relative bg-neutral-primary-soft border border-default rounded-base shadow-sm p-4 md:p-6 text-center">
                    <h3 id="titulo-exito-eliminar-estudiantes" class="text-lg font-medium text-heading">{{ $mensajeExito }}</h3>
                    <p class="mt-2 text-sm text-body">Puedes volver a cargar la lista desde Cargar CSV.</p>
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
