<div wire:init="cargar" class="px-4 py-4 sm:px-6 lg:px-8" aria-busy="{{ $cargando ? 'true' : 'false' }}">
    <section aria-label="Lista de auxiliares" class="mx-auto max-w-6xl">
        @if ($cargando)
            <div role="status" aria-live="polite" class="flex items-center justify-center gap-3 rounded-base border border-default bg-neutral-primary-soft p-8 text-sm text-body shadow-xs">
                <svg class="h-5 w-5 animate-spin text-fg-brand" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4Z"></path>
                </svg>
                <span>Cargando auxiliares...</span>
            </div>
        @elseif ($mensajeError !== '')
            <div class="space-y-4">
                <x-ui.alert type="danger" title="Error">
                    {{ $mensajeError }}
                </x-ui.alert>

                <x-ui.button type="button" variant="default" wire:click="cargar" wire:loading.attr="disabled" wire:target="cargar">
                    Reintentar
                </x-ui.button>
            </div>
        @elseif (count($auxiliares) === 0)
            <p class="rounded-base border border-default bg-neutral-primary-soft p-8 text-center text-sm text-body shadow-xs">
                No tienes auxiliares registrados
            </p>
        @else
            <ul class="space-y-3 lg:hidden">
                @foreach ($auxiliares as $auxiliar)
                    @php
                        $nombreCompleto = trim(($auxiliar['nombre'] ?? '').' '.($auxiliar['apellido'] ?? ''));
                        $esActivo = ($auxiliar['estado'] ?? '') === 'ACTIVO';
                    @endphp
                    <li wire:key="auxiliar-card-{{ $auxiliar['id'] }}" class="rounded-base border border-default bg-neutral-primary-soft p-4 shadow-xs">
                        <p class="break-words text-sm font-semibold text-heading">{{ $nombreCompleto }}</p>

                        <dl class="mt-3 space-y-2 text-sm">
                            <div>
                                <dt class="text-xs font-medium text-muted">Código SIS</dt>
                                <dd class="break-all font-mono text-body">{{ $auxiliar['codigoSis'] }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium text-muted">Curso</dt>
                                <dd class="break-words text-body">{{ $auxiliar['curso'] }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium text-muted">Estado</dt>
                                <dd class="mt-1">
                                    <x-ui.badge :type="$esActivo ? 'success' : 'gray'">
                                        {{ $esActivo ? 'Activo' : 'Baja' }}
                                    </x-ui.badge>
                                </dd>
                            </div>
                        </dl>
                    </li>
                @endforeach
            </ul>

            <div class="hidden lg:block">
                <x-ui.table :headers="['Nombre completo', 'Código SIS', 'Curso', 'Estado']">
                    <x-slot:body>
                        @foreach ($auxiliares as $auxiliar)
                            @php
                                $nombreCompleto = trim(($auxiliar['nombre'] ?? '').' '.($auxiliar['apellido'] ?? ''));
                                $esActivo = ($auxiliar['estado'] ?? '') === 'ACTIVO';
                            @endphp
                            <tr wire:key="auxiliar-fila-{{ $auxiliar['id'] }}" @class([
                                'bg-neutral-primary',
                                'border-b border-default' => ! $loop->last,
                            ])>
                                <th scope="row" class="px-6 py-4 font-medium text-heading">{{ $nombreCompleto }}</th>
                                <td class="px-6 py-4 font-mono text-body">{{ $auxiliar['codigoSis'] }}</td>
                                <td class="px-6 py-4 text-body">{{ $auxiliar['curso'] }}</td>
                                <td class="px-6 py-4">
                                    <x-ui.badge :type="$esActivo ? 'success' : 'gray'">
                                        {{ $esActivo ? 'Activo' : 'Baja' }}
                                    </x-ui.badge>
                                </td>
                            </tr>
                        @endforeach
                    </x-slot:body>
                </x-ui.table>
            </div>
        @endif
    </section>
</div>
