{{--
    @file    registrar-incidencia.blade.php
    @author  Valery D. Ortuno P. <valerydariana98@gmail.com>
    @created 2026-09-25
    @updated 2026-09-28

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
    - 2026-09-28  [Valery D. Ortuno P]  fix: nombre y apellido en campos
      separados, materia precargada del monitor como solo lectura (o texto libre
      si no llega), motivos acordados con el equipo y textos sin acentos
      pedidos; más espacio entre tarjetas, campos y botones, materia junto a la
      fecha del registro y estado como insignia compacta (#66).
    - 2026-09-28  [Valery D. Ortuno P]  fix: nombre, apellido y código SIS
      editables (fondo blanco) para registrar estudiantes que no estén en la
      base, con indicación de "solo letras" y de "9 números" (#66).
--}}

@section('title', 'Registrar incidencia')

@php
    $resultados = $this->resultadosBusqueda;
@endphp

{{-- El hueco entre las dos tarjetas es el mismo `mt-6` que usa el monitor en
     vivo entre sus tarjetas, para que las dos pantallas se vean parejas. --}}
<div class="mx-auto w-full max-w-4xl space-y-6">
    <section class="bg-neutral-primary-soft border border-default rounded-base shadow-xs p-6 sm:p-8">
        <h2 class="text-lg font-semibold text-heading">Datos del estudiante</h2>

        <div class="mt-6">
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

        <div class="mt-6 grid gap-5 sm:grid-cols-3">
            {{-- Precargados desde el monitor o desde el buscador, pero editables:
                 si el estudiante no está en la base de datos se escribe a mano. --}}
            <x-ui.input
                label="Nombres *"
                name="nombreEstudiante"
                :value="$nombreEstudiante"
                wire:model="nombreEstudiante"
                :error="$errors->first('nombreEstudiante')"
                maxlength="50"
                placeholder="Nombres del estudiante"
                class="bg-neutral-primary-soft!"
            />

            <x-ui.input
                label="Apellidos *"
                name="apellidoEstudiante"
                :value="$apellidoEstudiante"
                wire:model="apellidoEstudiante"
                :error="$errors->first('apellidoEstudiante')"
                maxlength="50"
                placeholder="Apellidos del estudiante"
                class="bg-neutral-primary-soft!"
            />

            <x-ui.input
                label="Código SIS *"
                name="codigoSis"
                :value="$codigoSis"
                wire:model="codigoSis"
                :error="$errors->first('codigoSis')"
                maxlength="9"
                inputmode="numeric"
                placeholder="SIS del estudiante"
                class="bg-neutral-primary-soft!"
            />
        </div>
    </section>

    {{-- Tarjeta 2: qué pasó. --}}
    <form wire:submit="registrar"
          class="bg-neutral-primary-soft border border-default rounded-base shadow-xs p-6 sm:p-8">
        <h2 class="text-lg font-semibold text-heading">Detalles de la incidencia</h2>

        <div class="mt-6 grid gap-5 sm:grid-cols-2">
            @if ($this->materiaEsSoloLectura)
                {{-- La materia precargada desde el monitor en vivo es un dato de
                     solo lectura: no se puede cambiar en el formulario. --}}
                <x-ui.input
                    label="Materia"
                    name="materia"
                    :value="$materia"
                    readonly
                />
            @else
                {{-- Sin materia del monitor (acceso directo, por ejemplo desde
                     la central de riesgos) se escribe a mano: solo letras,
                     números y espacios. --}}
                <x-ui.input
                    label="Materia *"
                    name="materia"
                    wire:model.live="materia"
                    :error="$errors->first('materia')"
                    placeholder="Escriba la materia del examen"
                    class="bg-neutral-primary-soft!"
                />
            @endif

            <x-ui.input
                label="Fecha y hora del registro"
                name="fechaHoraRegistro"
                :value="$fechaHoraRegistro"
                readonly
            />
        </div>

        <div class="mt-6 grid gap-5 sm:grid-cols-2">
            <x-ui.select
                label="Motivo de la incidencia *"
                name="tipoIncidencia"
                wire:model.live="tipoIncidencia"
                :options="$this->tiposIncidencia"
                :selected="$tipoIncidencia"
                :error="$errors->first('tipoIncidencia')"
                placeholder="Seleccione el motivo de la incidencia"
                class="bg-neutral-primary-soft!"
            />

            <div>
                <span class="block mb-2.5 text-sm font-medium text-heading">Estado de la incidencia</span>
                {{-- Insignia compacta, como en el resto del sistema. --}}
                <x-ui.badge :type="$this->tipoEstado">{{ $this->etiquetaEstado }}</x-ui.badge>
            </div>
        </div>

        <div class="mt-6">
            <x-ui.textarea
                :label="$this->descripcionEsObligatoria ? 'Descripcion del hecho *' : 'Descripcion del hecho'"
                name="descripcion"
                rows="4"
                maxlength="300"
                wire:model.live="descripcion"
                :error="$errors->first('descripcion')"
                placeholder="Describa la incidencia observada durante el examen"
                class="bg-neutral-primary-soft!"
            />

            <p class="mt-1 text-end text-sm text-body" aria-live="polite">{{ $this->contadorDescripcion }}</p>
        </div>

        <div class="mt-8 flex flex-col-reverse gap-4 border-t border-default pt-6 sm:flex-row sm:items-center sm:justify-end">
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
