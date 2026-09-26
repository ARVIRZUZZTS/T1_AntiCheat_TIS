{{--
    @file    registrar-incidencia.blade.php
    @author  Valery D. Ortuno P. <valerydariana98@gmail.com>
    @created 2026-09-25
    @updated 2026-09-26

    @description
    Vista del formulario de registro de una incidencia en la central de riesgos.
    Una sola plantilla sirve para escritorio y móvil: las tarjetas se apilan en
    una columna por debajo del breakpoint `sm`. La primera tarjeta lleva el
    buscador con el que se puede cambiar el estudiante recibido desde el monitor.
    La segunda concentra el estado, el motivo, la materia, la fecha y hora del
    registro y la descripción del hecho.

    Los campos de solo lectura conservan el fondo gris de fábrica y los
    editables se ponen en blanco, para que se distingan sin textos de ayuda.

    @see  \App\Livewire\Monitoreo\RegistrarIncidencia

    @changelog
    - 2026-09-25  [Valery D. Ortuno P]  feat: creación inicial de la vista.
    - 2026-09-26  [Valery D. Ortuno P]  feat: buscador de estudiantes, motivos del
      equipo, vista responsive de escritorio y móvil, y estado derivado del rol
      recibido por la URL; se quitan los textos de ayuda de cada campo.
--}}

@section('title', 'Registrar incidencia')

@php
    $resultados = $this->resultadosBusqueda;
@endphp

<div class="mx-auto w-full max-w-4xl space-y-5 sm:space-y-6">
    <nav aria-label="Ruta de navegación">
        <a href="{{ route('monitoreo') }}"
           class="inline-flex items-center gap-1.5 text-sm font-medium text-fg-brand hover:underline">
            <svg class="w-4 h-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m15 19-7-7 7-7"/></svg>
            Volver al monitor en vivo
        </a>
    </nav>

    {{-- Tarjeta 1: quién es el estudiante. El buscador va fuera del <form> de
         registro porque `x-ui.search-input` ya emite su propio <form>.

         Los campos de solo lectura (nombre y código SIS) conservan el fondo
         gris de fábrica del design system; los editables llevan
         `bg-neutral-primary-soft!` para ponerse en blanco y así distinguirse
         de un vistazo. El `!` es necesario porque los componentes de `x-ui`
         fijan el fondo con su propia utilidad de Tailwind. --}}
    <section class="bg-neutral-primary-soft border border-default rounded-base shadow-xs p-4 sm:p-6">
        <h2 class="text-lg font-semibold text-heading">Datos del estudiante</h2>

        <div class="mt-4">
            <x-ui.search-input
                name="busqueda"
                id="busqueda"
                placeholder="Buscar por nombre o código SIS..."
                wire:model.live.debounce.300ms="busqueda"
                wire:submit.prevent="buscarEstudiantes"
                :show-button="false"
                class="bg-neutral-primary-soft!"
            />

            @if (filled($busqueda) && $resultados->isNotEmpty())
                <ul class="absolute z-10 mt-1 w-full overflow-hidden rounded-base border border-default bg-surface-page shadow-xs"
                    role="listbox"
                    aria-label="Estudiantes encontrados">
                    @foreach ($resultados as $resultado)
                        <li role="option" aria-selected="false">
                            <button type="button"
                                    wire:click="seleccionarEstudiante('{{ $resultado->sis_estudiante }}')"
                                    class="flex w-full flex-col items-start gap-0.5 border-b border-default px-3 py-2 text-start text-sm last:border-b-0 hover:bg-neutral-secondary-soft focus:bg-neutral-secondary-soft focus:outline-none">
                                <span class="font-medium text-heading">
                                    {{ $resultado->nombre_estudiante }} {{ $resultado->apellido_estudiante }}
                                </span>
                                <span class="text-xs text-body">{{ $resultado->sis_estudiante }}</span>
                            </button>
                        </li>
                    @endforeach
                </ul>
            @elseif (filled($busqueda) && $resultados->isEmpty())
                <p class="mt-2 text-sm text-body">
                    Ningún estudiante coincide con «{{ trim($busqueda) }}».
                </p>
            @endif
        </div>

        <div class="mt-4 grid gap-4 sm:grid-cols-2">
            <x-ui.input
                label="Nombre"
                name="nombreEstudiante"
                :value="$nombreEstudiante"
                wire:model="nombreEstudiante"
                readonly
            />

            <x-ui.input
                label="Código SIS"
                name="codigoSis"
                :value="$codigoSis"
                wire:model="codigoSis"
                :error="$errors->first('codigoSis')"
                readonly
            />
        </div>
    </section>

    {{-- Tarjeta 2: qué pasó. --}}
    <form wire:submit="registrar"
          class="bg-neutral-primary-soft border border-default rounded-base shadow-xs p-4 sm:p-6">
        <h2 class="text-lg font-semibold text-heading">Detalles de la incidencia</h2>

        <div class="mt-4 grid gap-4 sm:grid-cols-2">
            <div>
                <span class="block mb-2.5 text-sm font-medium text-heading">Estado de la incidencia</span>
                {{-- La insignia ocupa todo el ancho de la columna y replica el
                     alto de los campos vecinos (`py-2.5 text-sm` mas el borde)
                     para que no se vea mas pequena que ellos. --}}
                <x-ui.badge
                    :type="$this->tipoEstado"
                    class="w-full! justify-center! px-3! py-2.5! text-sm!"
                >{{ $this->etiquetaEstado }}</x-ui.badge>
            </div>

            <x-ui.input
                label="Fecha y hora del registro"
                name="fechaHoraRegistro"
                :value="$fechaHoraRegistro"
                readonly
            />
        </div>

        <div class="mt-4 grid gap-4 sm:grid-cols-2">
            <x-ui.select
                label="Motivo de la incidencia *"
                name="tipoIncidencia"
                wire:model.live="tipoIncidencia"
                :options="$this->tiposIncidencia"
                :selected="$tipoIncidencia"
                :error="$errors->first('tipoIncidencia')"
                placeholder="Seleccione un motivo"
                class="bg-neutral-primary-soft!"
            />

            <x-ui.select
                label="Materia *"
                name="materia"
                wire:model.live="materia"
                :options="$this->materias"
                :selected="$materia"
                :error="$errors->first('materia')"
                placeholder="Seleccione una materia"
                class="bg-neutral-primary-soft!"
            />
        </div>

        <div class="mt-4">
            <x-ui.textarea
                :label="$this->descripcionEsObligatoria ? 'Descripción del hecho *' : 'Descripción del hecho'"
                name="descripcion"
                rows="4"
                maxlength="300"
                wire:model.live="descripcion"
                :error="$errors->first('descripcion')"
                placeholder="Cuente la anomalía observada durante el examen."
                class="bg-neutral-primary-soft!"
            />

            <p class="mt-1 text-end text-sm text-body" aria-live="polite">{{ $this->contadorDescripcion }}</p>
        </div>

        <div class="mt-5 flex flex-col-reverse gap-3 border-t border-default pt-5 sm:flex-row sm:items-center sm:justify-end">
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
