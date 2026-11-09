{{--
    @file    index.blade.php
    @author  David E. Chavez T. <virzuzz12345@gmail.com>
    @created 2026-11-08
    @updated 2026-11-08

    @description
    Vista del componente Livewire Invitaciones\Index: pestañas para recibir y
    enviar invitaciones a exámenes compartidos.

    @changelog
    - 2026-11-08  [David E. Chavez T.]  feat: creación inicial de la vista.

    @see  app/Livewire/Invitaciones/Index.php
--}}

@section('title', 'Invitaciones')

<div class="flex flex-col gap-6 px-[2vh] mt-[2vh]">
    @if ($mensajeExito !== '')
        <x-ui.alert type="success" title="Listo">{{ $mensajeExito }}</x-ui.alert>
    @endif
    @if ($mensajeError !== '')
        <x-ui.alert type="danger" title="Error">{{ $mensajeError }}</x-ui.alert>
    @endif

    <div class="flex gap-2">
        <x-ui.button type="button" variant="{{ $tab === 'recibir' ? 'default' : 'secondary' }}" wire:click="cambiarTab('recibir')">
            Recibir
        </x-ui.button>
        <x-ui.button type="button" variant="{{ $tab === 'enviar' ? 'default' : 'secondary' }}" wire:click="cambiarTab('enviar')">
            Enviar
        </x-ui.button>
    </div>

    @if ($tab === 'recibir')
        <div class="bg-neutral-primary-soft border border-default rounded-base shadow-xs p-6 space-y-4">
            <h3 class="text-lg font-semibold text-heading">Invitaciones recibidas</h3>

            @if (count($invitacionesRecibidas) === 0)
                <p class="text-sm text-body">No tienes invitaciones pendientes.</p>
            @else
                @foreach ($invitacionesRecibidas as $invitacion)
                    <div class="border border-default rounded-base p-4 space-y-2">
                        <p class="font-medium text-heading">Curso: {{ $invitacion['curso'] ?? '—' }}</p>
                        <p class="text-sm text-body">Examen: {{ $invitacion['examen']['nombre'] }}</p>
                        <p class="text-sm text-body">Invitado por: {{ $invitacion['invitador']['nombre'] }}</p>
                        <p class="text-sm text-muted">{{ $invitacion['enviada_en'] ? \Carbon\Carbon::parse($invitacion['enviada_en'])->format('d-m-Y') : '' }}</p>
                        <div class="flex gap-2">
                            <x-ui.button type="button" variant="default" wire:click="aceptar({{ $invitacion['id'] }})">
                                Aceptar
                            </x-ui.button>
                            <x-ui.button-cancelar type="button" wire:click="rechazar({{ $invitacion['id'] }})">
                                Rechazar
                            </x-ui.button-cancelar>
                        </div>
                    </div>
                @endforeach
            @endif
        </div>
    @else
        <div class="bg-neutral-primary-soft border border-default rounded-base shadow-xs p-6 space-y-4">
            <h3 class="text-lg font-semibold text-heading">Enviar invitación</h3>

            <div>
                <label class="block text-sm font-medium text-heading mb-1">Curso</label>
                <select wire:model.live="idCurso" class="w-full rounded-base border border-default px-3 py-2">
                    <option value="0">Seleccione un curso</option>
                    @foreach ($cursos as $curso)
                        <option value="{{ $curso['id'] }}">{{ $curso['nombre'] }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-heading mb-1">Examen</label>
                <select wire:model="idExamen" class="w-full rounded-base border border-default px-3 py-2">
                    <option value="0">Seleccione un examen</option>
                    @foreach ($examenes as $examen)
                        <option value="{{ $examen['id'] }}">{{ $examen['nombre'] }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-heading mb-1">Buscar docente</label>
                <input type="text" wire:model="busquedaDocente" wire:change="buscarDocentes" class="w-full rounded-base border border-default px-3 py-2" placeholder="Nombre o código SIS" />
            </div>

            @if (count($docentes) > 0)
                <div class="space-y-2">
                    @foreach ($docentes as $docente)
                        <button type="button" wire:click="$set('docenteSeleccionado', {{ $docente['id'] }})" class="w-full text-left border border-default rounded-base p-3 {{ $docenteSeleccionado === $docente['id'] ? 'bg-brand-soft' : '' }}">
                            {{ $docente['nombre'] }} ({{ $docente['sis'] ?? '' }})
                        </button>
                    @endforeach
                </div>
            @endif

            <x-ui.button type="button" variant="default" wire:click="enviar">
                Enviar invitación
            </x-ui.button>
        </div>
    @endif
</div>
