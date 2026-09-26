{{--
    @file    registrar-incidencia.blade.php
    @author  Valery D. Ortuno P. <valerydariana98@gmail.com>
    @created 2026-09-25
    @updated 2026-09-25

    @description
    Vista desktop del formulario de registro de una incidencia en la central
    de riesgos. Muestra los datos precargados del estudiante y del examen, el
    motivo, la fecha y hora del registro y la descripción del hecho.

    @see  \App\Livewire\Monitoreo\RegistrarIncidencia
--}}

@section('title', 'Registrar incidencia')

@php
    // Cada estado se apoya en la insignia de la paleta que ya usa el monitor.
    $tipoEstado = $rol === \App\Models\Rol::NOMBRE_AUXILIAR
        ? 'status-en-revision'
        : 'status-central-riesgos';
@endphp

<div class="max-w-4xl mx-auto space-y-6">
    <nav aria-label="Ruta de navegación">
        <a href="{{ route('monitoreo') }}"
           class="inline-flex items-center gap-1.5 text-sm font-medium text-fg-brand hover:underline">
            <svg class="w-4 h-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m15 19-7-7 7-7"/></svg>
            Volver al monitor en vivo
        </a>
    </nav>

    <section class="bg-neutral-primary-soft border border-default rounded-base shadow-xs p-6">
        <h2 class="text-lg font-semibold text-heading">Datos del registro</h2>
        <p class="mt-1 text-sm text-body">
            Estos campos se completan solos con la información del estudiante y del
            examen seleccionados, por eso no se pueden editar.
        </p>

        <div class="mt-5 grid gap-4 sm:grid-cols-2">
            <x-ui.input label="Estudiante" name="nombreEstudiante" :value="$nombreEstudiante" readonly />

            <x-ui.input label="Código SIS" name="codigoSis" :value="$codigoSis" readonly />

            <x-ui.input label="Materia" name="materia" :value="$materia" readonly />

            <div>
                <span class="block mb-2.5 text-sm font-medium text-heading">Estado de la incidencia</span>
                <x-ui.badge :type="$tipoEstado">{{ $this->etiquetaEstado }}</x-ui.badge>
                <p class="mt-2.5 text-sm text-body">
                    Un auxiliar registra la incidencia como sospechosa y un docente la confirma.
                </p>
            </div>
        </div>
    </section>

    <form wire:submit="registrar" class="bg-neutral-primary-soft border border-default rounded-base shadow-xs p-6">
        <h2 class="text-lg font-semibold text-heading">Descripción de la incidencia</h2>

        <div class="mt-5 grid gap-4 sm:grid-cols-2">
            <x-ui.select
                label="Motivo de la incidencia *"
                name="tipoIncidencia"
                wire:model.live="tipoIncidencia"
                :options="$this->tiposIncidencia"
                :selected="$tipoIncidencia"
                :error="$errors->first('tipoIncidencia')"
                placeholder="Seleccione un motivo"
            />

            <x-ui.input
                label="Fecha y hora del registro"
                name="fechaHoraRegistro"
                :value="$fechaHoraRegistro"
                helper="Se completa con la fecha y hora actuales."
                readonly
            />
        </div>

        <div class="mt-4">
            <x-ui.textarea
                label="Descripción del hecho *"
                name="descripcion"
                rows="4"
                maxlength="300"
                wire:model.live="descripcion"
                :error="$errors->first('descripcion')"
                placeholder="Describa la anomalía observada durante el examen."
            />

            <div class="mt-1 flex items-center justify-between gap-4">
                <p class="text-sm text-body">Máximo 300 caracteres.</p>
                <p class="text-sm text-body" aria-live="polite">{{ $this->contadorDescripcion }}</p>
            </div>
        </div>

        <div class="mt-6 flex items-center justify-end gap-3 border-t border-default pt-5">
            <x-ui.button variant="secondary" wire:click="cancelar" wire:loading.attr="disabled">
                Cancelar
            </x-ui.button>

            <x-ui.button type="submit" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="registrar">Registrar incidencia</span>
                <span wire:loading wire:target="registrar">Registrando…</span>
            </x-ui.button>
        </div>
    </form>
</div>
