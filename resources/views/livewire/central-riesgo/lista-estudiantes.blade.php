{{--
    @file    lista-estudiantes.blade.php
    @author  Valery D. Ortuno P. <valerydariana98@gmail.com>
    @created 2026-10-10
    @updated 2026-10-10

    @description
    Vista del componente Livewire ListaEstudiantes (HU 10): tabs de alcance
    (Toda la institución / Mis materias), buscador por nombre o código SIS,
    interruptor "Ordenar A-Z" y la lista de estudiantes sancionados, en cards
    en móvil y en tabla en desktop, con paginación de 8 registros.

    Al elegir un estudiante se abre el detalle de su incidencia. En móvil ocupa
    la pantalla (la lista se oculta) y se vuelve con la flecha; en desktop se
    abre a la derecha de la lista, con la pantalla dividida, y se cierra con la X.

    @changelog
    - 2026-10-10  [Valery D. Ortuno P]  feat: creación inicial de la vista, a
      partir del diseño que estaba mockeado en pages/central-riesgo.blade.php.
    - 2026-10-11  [Valery D. Ortuno P]  feat: "Toda la institución" pasa a ser el
      primer tab (y el alcance por defecto), con "Mis materias" a su derecha.
    - 2026-10-11  [Valery D. Ortuno P]  fix: se quita el banner de "Bloqueo
      automático" (ya no existe) y el botón "Registrar incidencia" (el registro
      se hace desde Monitor en vivo).

    @see  App\Livewire\CentralRiesgo\ListaEstudiantes
    @see  resources/views/partials/busqueda-estudiante.blade.php
--}}

@php
    use App\Models\Rol;
    use App\Services\CentralRiesgo\ListarIncidenciasTramposoService;
    use Illuminate\Support\Carbon;

    $alcances = [
        ListarIncidenciasTramposoService::ALCANCE_INSTITUCION => 'Toda la institución',
        ListarIncidenciasTramposoService::ALCANCE_MIS_MATERIAS => 'Mis materias',
    ];

    $estudiantes = $this->estudiantes;
    $detalle = $this->detalle;

    // Distingue la lista vacía por búsqueda de la central sin estudiantes.
    $sinCoincidencias = trim($busqueda) !== '' && $estudiantes->total() === 0;

    $tituloLista = $alcance === ListarIncidenciasTramposoService::ALCANCE_MIS_MATERIAS
        ? 'Estudiantes en mis materias'
        : 'Estudiantes de toda la institución';

    $etiquetaReportante = ($detalle['reportado_por_rol'] ?? null) === Rol::NOMBRE_AUXILIAR
        ? 'Auxiliar que reportó'
        : 'Docente que reportó';
@endphp

<div class="px-4 pt-[2vh] pb-8 sm:px-6 lg:px-[2vh]" x-data="{ @include('partials.busqueda-estudiante') }">
    <div class="lg:flex lg:items-start lg:gap-6">
        {{-- Lista. En móvil se oculta mientras el detalle está abierto, porque
             el detalle se ve "en otra pantalla"; en desktop queda a la izquierda. --}}
        <section @class(['min-w-0 lg:block lg:flex-1', 'hidden' => $detalle !== null]) aria-label="Lista de estudiantes">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="flex flex-wrap gap-2" role="tablist" aria-label="Ámbito de la central">
                    @foreach ($alcances as $clave => $etiqueta)
                        <button type="button" role="tab" wire:key="alcance-{{ $clave }}"
                                wire:click="cambiarAlcance('{{ $clave }}')"
                                aria-selected="{{ $alcance === $clave ? 'true' : 'false' }}"
                                @class([
                                    'inline-flex items-center justify-center rounded-full border px-5 py-2.5 text-sm font-semibold shadow-xs focus:outline-none focus:ring-4 focus:ring-brand-medium',
                                    'bg-brand text-white border-transparent' => $alcance === $clave,
                                    'bg-neutral-secondary-medium text-body border-neutral-quaternary' => $alcance !== $clave,
                                ])>
                            {{ $etiqueta }}
                        </button>
                    @endforeach
                </div>
            </div>

            <div class="mt-5 flex flex-col gap-3 sm:flex-row sm:items-center">
                <div class="relative min-w-0 flex-1">
                    <div class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-3">
                        <svg class="h-4 w-4 text-body" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-width="2" d="m21 21-3.5-3.5M17 10a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z"/></svg>
                    </div>
                    {{-- Se envía con $wire.set y no con wire:model para que al servidor
                         viaje el valor ya saneado, que es el que queda en el input. --}}
                    <input type="search" placeholder="Buscar por nombre o SIS" aria-label="Buscar por nombre o SIS"
                           value="{{ $busqueda }}"
                           @input="busqueda = sanitizarBusqueda($event.target.value); $event.target.value = busqueda"
                           @input.debounce.300ms="$wire.set('busqueda', busqueda)"
                           x-bind:maxlength="maximoBusqueda()"
                           class="w-full rounded-[10px] border border-neutral-tertiary bg-neutral-primary-soft py-3.5 ps-10 pe-4 text-sm text-heading shadow-xs placeholder:text-fg-disabled focus:border-brand focus:ring-brand focus:outline-none" />
                </div>

                <label class="inline-flex shrink-0 cursor-pointer items-center">
                    <input type="checkbox" class="peer sr-only" wire:model.live="ordenAlfabetico" />
                    <div class="relative h-5 w-9 rounded-full bg-neutral-quaternary peer-checked:bg-brand peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-brand-soft after:absolute after:start-[2px] after:top-[2px] after:h-4 after:w-4 after:rounded-full after:bg-white after:transition-all after:content-[''] peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full"></div>
                    <span class="ms-3 select-none text-sm font-medium text-heading">Ordenar A-Z</span>
                </label>
            </div>

            @if ($estudiantes->total() === 0)
                <p class="mt-6 rounded-xl border border-default bg-neutral-primary-soft p-8 text-center text-sm text-body">
                    {{ $sinCoincidencias ? 'No se encontró ninguna coincidencia' : 'No hay estudiantes registrados' }}
                </p>
            @else
                {{-- Móvil: cards de estudiantes. --}}
                <div class="mt-5 space-y-3 lg:hidden">
                    @foreach ($estudiantes as $estudiante)
                        @php $nombre = trim(($estudiante['nombre'] ?? '').' '.($estudiante['apellido'] ?? '')); @endphp
                        <article wire:key="card-{{ $estudiante['id_registro'] }}"
                                 wire:click="verDetalle({{ $estudiante['id_registro'] }})"
                                 class="cursor-pointer rounded-xl border border-neutral-quaternary bg-neutral-primary-soft p-4">
                            <div class="flex items-center gap-3">
                                <x-ui.avatar :name="$nombre" tone="tramposo" class="shrink-0" />
                                {{-- El texto envuelve en vez de truncarse: `truncate` no deja
                                     partir la línea y ensanchaba la página en pantallas chicas. --}}
                                <div class="min-w-0">
                                    <p class="break-words text-sm font-bold text-fg-brand">{{ $nombre }}</p>
                                    <p class="break-words text-[11px] text-body">{{ $estudiante['sis'] }} · {{ $estudiante['materia'] ?? 'Sin materia' }}</p>
                                </div>
                            </div>
                            <div class="mt-3 flex items-center justify-between gap-3">
                                <p class="min-w-0 break-words text-xs text-body">{{ $estudiante['motivo_etiqueta'] }}</p>
                                <button type="button" wire:click.stop="verDetalle({{ $estudiante['id_registro'] }})"
                                        class="shrink-0 cursor-pointer border-0 bg-transparent p-0 text-xs font-bold text-fg-danger-strong hover:underline">
                                    Ver detalle ›
                                </button>
                            </div>
                        </article>
                    @endforeach
                </div>

                {{-- Desktop: panel con tabla. --}}
                <div class="mt-6 hidden lg:block">
                    <div class="overflow-x-auto rounded-xl border border-neutral-quaternary bg-neutral-primary-soft shadow-xs">
                        <div class="border-b border-neutral-quaternary px-4 py-4">
                            <h2 class="text-[15px] font-semibold text-fg-brand">{{ $tituloLista }}</h2>
                        </div>
                        <table class="w-full text-left text-sm text-body">
                            <thead>
                                <tr class="border-b border-neutral-quaternary">
                                    <th scope="col" class="px-4 py-3 text-[10px] font-medium uppercase tracking-wider text-body">Estudiante</th>
                                    <th scope="col" class="px-4 py-3 text-[10px] font-medium uppercase tracking-wider text-body">Código SIS</th>
                                    <th scope="col" class="px-4 py-3 text-[10px] font-medium uppercase tracking-wider text-body">Materia</th>
                                    <th scope="col" class="px-4 py-3 text-[10px] font-medium uppercase tracking-wider text-body">Motivo</th>
                                    <th scope="col" class="px-4 py-3"><span class="sr-only">Acciones</span></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($estudiantes as $estudiante)
                                    @php $nombre = trim(($estudiante['nombre'] ?? '').' '.($estudiante['apellido'] ?? '')); @endphp
                                    <tr wire:key="fila-{{ $estudiante['id_registro'] }}"
                                        wire:click="verDetalle({{ $estudiante['id_registro'] }})"
                                        @class([
                                            'cursor-pointer border-b border-neutral-quaternary transition-colors last:border-b-0',
                                            'bg-status-central-riesgos-bg' => $idSeleccionado === $estudiante['id_registro'],
                                            'hover:bg-[#F7F8FC]' => $idSeleccionado !== $estudiante['id_registro'],
                                        ])>
                                        <td class="px-4 py-4">
                                            <div class="flex items-center gap-3">
                                                <x-ui.avatar :name="$nombre" size="sm" tone="tramposo" class="shrink-0" />
                                                <p class="text-sm font-bold text-fg-brand">{{ $nombre }}</p>
                                            </div>
                                        </td>
                                        <td class="px-4 py-4 font-mono text-body">{{ $estudiante['sis'] }}</td>
                                        <td class="px-4 py-4 text-body">{{ $estudiante['materia'] ?? 'Sin materia' }}</td>
                                        <td class="px-4 py-4 text-body">{{ $estudiante['motivo_etiqueta'] }}</td>
                                        <td class="px-4 py-4 text-end">
                                            <button type="button" wire:click.stop="verDetalle({{ $estudiante['id_registro'] }})"
                                                    class="cursor-pointer whitespace-nowrap border-0 bg-transparent p-0 text-xs font-bold text-fg-danger-strong hover:underline">
                                                Ver detalle
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="mt-4 flex flex-col items-center gap-2 sm:flex-row sm:justify-between">
                    <p class="text-sm text-muted">
                        Mostrando {{ $estudiantes->count() }} de {{ $estudiantes->total() }} estudiantes
                    </p>

                    @if ($estudiantes->lastPage() > 1)
                        <x-ui.pagination
                            :interactive="true"
                            :active-filled="true"
                            size="sm"
                            :current="$estudiantes->currentPage()"
                            :total="$estudiantes->lastPage()"
                        />
                    @endif
                </div>
            @endif
        </section>

        @if ($detalle !== null)
            <aside wire:key="detalle-{{ $detalle['id_registro'] }}"
                   class="lg:sticky lg:top-4 lg:w-[380px] lg:shrink-0 xl:w-[420px]"
                   aria-label="Detalles de la incidencia">
                <div class="rounded-xl border border-neutral-quaternary bg-neutral-primary-soft shadow-xs">
                    <div class="flex items-center gap-2 border-b border-neutral-quaternary px-4 py-3">
                        {{-- Móvil: vuelve a la lista, que conserva orden, filtro y página. --}}
                        <button type="button" wire:click="cerrarDetalle" aria-label="Volver a la lista"
                                class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-body hover:bg-neutral-secondary-medium hover:text-heading focus:outline-none lg:hidden">
                            <svg class="h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m15 19-7-7 7-7"/></svg>
                        </button>

                        <h2 class="min-w-0 flex-1 text-[15px] font-semibold text-fg-brand">Detalles de la incidencia</h2>

                        {{-- Desktop: cierra la vista dividida. --}}
                        <button type="button" wire:click="cerrarDetalle" aria-label="Cerrar detalles de la incidencia"
                                class="hidden h-9 w-9 shrink-0 items-center justify-center rounded-full text-body hover:bg-neutral-secondary-medium hover:text-heading focus:outline-none lg:inline-flex">
                            <svg class="h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 17.94 6M18 18 6.06 6"/></svg>
                        </button>
                    </div>

                    <div class="space-y-4 p-4">
                        <div class="rounded-xl border border-danger-subtle bg-danger-soft p-4">
                            <p class="font-semibold text-fg-danger-strong">{{ $detalle['estudiante_nombre'] ?? 'Estudiante desconocido' }}</p>
                            <p class="mt-1 text-sm text-fg-danger">Código SIS: <span class="font-mono">{{ $detalle['sis_estudiante'] }}</span></p>
                        </div>

                        <dl class="space-y-4 text-sm">
                            <div>
                                <dt class="text-[10px] font-medium uppercase tracking-wider text-body">Motivo</dt>
                                <dd class="mt-1 font-medium text-heading">{{ $detalle['motivo'] }}</dd>
                            </div>
                            <div>
                                <dt class="text-[10px] font-medium uppercase tracking-wider text-body">Materia</dt>
                                <dd class="mt-1 font-medium text-heading">{{ $detalle['materia'] ?? 'Sin materia' }}</dd>
                            </div>
                            <div>
                                <dt class="text-[10px] font-medium uppercase tracking-wider text-body">{{ $etiquetaReportante }}</dt>
                                <dd class="mt-1 font-medium text-heading">{{ $detalle['reportado_por'] ?? 'Sin registrar' }}</dd>
                            </div>
                            <div>
                                <dt class="text-[10px] font-medium uppercase tracking-wider text-body">Fecha y hora</dt>
                                <dd class="mt-1 font-medium text-heading">{{ Carbon::parse($detalle['fecha_registro'])->format('d/m/Y H:i') }}</dd>
                            </div>
                            <div>
                                <dt class="text-[10px] font-medium uppercase tracking-wider text-body">Estado</dt>
                                <dd class="mt-1">
                                    <x-ui.badge type="danger" class="font-semibold">{{ $detalle['estado'] }}</x-ui.badge>
                                </dd>
                            </div>
                            <div>
                                <dt class="text-[10px] font-medium uppercase tracking-wider text-body">Descripción del hecho</dt>
                                <dd class="mt-1 whitespace-pre-line break-words text-heading">{{ $detalle['descripcion'] ?: 'Sin descripción' }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </aside>
        @endif
    </div>
</div>
