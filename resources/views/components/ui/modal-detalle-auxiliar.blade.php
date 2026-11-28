@props([
    'abierto' => false,
    'auxiliar' => [],
    'cursos' => [],
])

@if ($abierto)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <button type="button" wire:click="cerrarDetalle" aria-label="Cerrar detalle" class="absolute inset-0 cursor-default bg-overlay backdrop-blur-xs"></button>
        <div role="dialog" aria-modal="true" aria-label="Detalle del auxiliar" class="relative z-10 flex max-h-[85vh] w-full max-w-2xl flex-col rounded-xl border border-default bg-neutral-primary shadow-lg">
            <div class="flex items-start justify-between gap-3 border-b border-default px-6 py-4">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-body-subtle">Detalle del auxiliar</p>
                    <h2 class="mt-0.5 text-lg font-bold text-heading break-words">{{ $auxiliar['nombreCompleto'] ?? 'Auxiliar' }}</h2>
                </div>
                <button type="button" wire:click="cerrarDetalle" aria-label="Cerrar detalle"
                    class="inline-flex size-9 shrink-0 items-center justify-center rounded-base text-body-subtle transition-colors hover:bg-neutral-secondary hover:text-heading focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-medium">
                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" d="m6 6 12 12M18 6 6 18" />
                    </svg>
                </button>
            </div>

            <div class="space-y-4 overflow-y-auto px-6 py-4">
                @php
                    $datos = [
                        'Nombre completo' => $auxiliar['nombreCompleto'] ?? '—',
                        'Tipo de auxiliar' => ($auxiliar['tipo'] ?? '') !== '' ? $auxiliar['tipo'] : '—',
                        'Codigo SIS' => ($auxiliar['codigoSis'] ?? '') !== '' ? $auxiliar['codigoSis'] : '—',
                        'Carnet de identidad' => ($auxiliar['carnet'] ?? '') !== '' ? $auxiliar['carnet'] : '—',
                        'Correo electronico' => ($auxiliar['correo'] ?? '') !== '' ? $auxiliar['correo'] : '—',
                        'Usuario de acceso' => ($auxiliar['usuario'] ?? '') !== '' ? $auxiliar['usuario'] : '—',
                    ];
                @endphp

                <dl class="grid grid-cols-1 gap-x-4 gap-y-3 text-sm sm:grid-cols-2">
                    @foreach ($datos as $etiqueta => $valor)
                        <div class="min-w-0">
                            <dt class="text-xs font-medium text-muted">{{ $etiqueta }}</dt>
                            <dd class="mt-0.5 break-words text-body">{{ $valor }}</dd>
                        </div>
                    @endforeach
                </dl>

                @if (($cursos['activos'] ?? []) === [] && ($cursos['anteriores'] ?? []) === [])
                    <div class="border-t border-default pt-4">
                        <p class="text-sm font-bold text-heading">Cursos asignados</p>
                        <p class="mt-2 text-sm text-body-subtle">Sin cursos asignados.</p>
                    </div>
                @endif

                @foreach (['activos' => 'Cursos activos', 'anteriores' => 'Cursos anteriores'] as $clave => $titulo)
                    @if (($cursos[$clave] ?? []) !== [])
                        <div class="border-t border-default pt-4">
                            <p class="text-sm font-bold text-heading">{{ $titulo }}</p>
                            <ul class="mt-2 space-y-2">
                                @foreach ($cursos[$clave] as $curso)
                                    <li class="flex items-center justify-between gap-3 rounded-base border border-default bg-neutral-secondary-soft px-3 py-2 text-sm">
                                        <span class="break-words text-body">{{ $curso['etiqueta'] }}</span>
                                        <span class="inline-flex shrink-0 items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $curso['estado'] === 'Activo' ? 'bg-brand-softer text-fg-brand' : 'bg-neutral-tertiary text-body-subtle' }}">{{ $curso['estado'] }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                @endforeach

                @if (($auxiliar['examenes'] ?? []) !== [])
                    <div class="border-t border-default pt-4">
                        <p class="text-sm font-bold text-heading">Examenes asignados</p>
                        <ul class="mt-2 space-y-2">
                            @foreach ($auxiliar['examenes'] as $examen)
                                <li class="rounded-base border border-default bg-neutral-secondary-soft px-3 py-2 text-sm text-body">{{ $examen }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>

            <div class="flex justify-end border-t border-default px-6 py-3">
                <button type="button" wire:click="cerrarDetalle" class="inline-flex min-h-11 items-center rounded-base bg-neutral-tertiary px-4 text-sm font-medium text-body transition-colors hover:bg-neutral-secondary focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-medium">Cerrar</button>
            </div>
        </div>
    </div>
@else
    <div aria-hidden="true" class="hidden"></div>
@endif
