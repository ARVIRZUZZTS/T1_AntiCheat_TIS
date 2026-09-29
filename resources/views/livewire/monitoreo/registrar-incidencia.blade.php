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

    El enlace de la cabecera y el botón de cancelar devuelven a la pantalla desde
    la que se abrió el formulario: el monitor en vivo o la central de riesgo.

    @see  \App\Livewire\Monitoreo\RegistrarIncidencia

    @changelog
    - 2026-09-25  [Valery D. Ortuno P]  feat: creación inicial de la vista.
    - 2026-09-26  [Valery D. Ortuno P]  feat: buscador de estudiantes, motivos del
      equipo, vista responsive de escritorio y móvil, y estado derivado del rol
      recibido por la URL; se quitan los textos de ayuda de cada campo.
 * - 2026-09-28  [Candy]  feat: el enlace de vuelta sigue a la pantalla desde la
 *   que se abrió el formulario, para que al entrar desde la central de riesgo
 *   no devuelva al monitor en vivo.
 * - 2026-09-28  [Valery D. Ortuno P]  fix: nombre y apellido en campos
 *   separados, materia precargada del monitor como solo lectura (o texto libre
 *   si no llega), motivos acordados con el equipo y textos sin acentos
 *   pedidos; más espacio entre tarjetas, campos y botones, materia junto a la
 *   fecha del registro y estado como insignia compacta (#66).
 * - 2026-09-28  [Valery D. Ortuno P]  fix: nombre, apellido y código SIS
 *   editables (fondo blanco) para registrar estudiantes que no estén en la
 *   base, con indicación de "solo letras" y de "9 números" (#66).
 * - 2026-09-28  [Candy]  feat: modal de confirmación tras registrar, con el
 *   resumen del estudiante y de la incidencia y un único botón Aceptar que
 *   termina el proceso y vuelve a la pantalla de origen.
 * - 2026-09-28  [Candy]  feat: el resumen muestra también el número del registro
 *   y quién lo registró, que ya están guardados en la base de datos (#70).
 * - 2026-09-29  [Candy]  feat: el modal toma las clases del design system, como
 *   los demás modales de la aplicación, y muestra el número del registro y la
 *   materia real del examen, que la base deriva de `id_examen` (#70).
--}}

@section('title', 'Registrar incidencia')

@php
    $resultados = $this->resultadosBusqueda;
@endphp

{{-- El hueco entre las dos tarjetas es el mismo `space-y-6` que usa el monitor
     en vivo entre las suyas, para que las dos pantallas se vean parejas. --}}
<div class="mx-auto w-full max-w-4xl space-y-6">
    <nav aria-label="Ruta de navegación">
        <a href="{{ $this->rutaVolver }}"
           class="inline-flex items-center gap-1.5 text-sm font-medium text-fg-brand hover:underline">
            <svg class="w-4 h-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m15 19-7-7 7-7"/></svg>
            {{ $this->etiquetaVolver }}
        </a>
    </nav>

    {{-- Tarjeta 1: quién es el estudiante. El buscador va fuera del <form> de
         registro porque `x-ui.search-input` ya emite su propio <form>.

         Los campos de solo lectura (nombre y código SIS) conservan el fondo
         gris de fábrica del design system; los editables llevan
         `bg-neutral-primary-soft!` para ponerse en blanco y así distinguirse
         de un vistazo. El `!` es necesario porque los componentes de `x-ui`
         fijan el fondo con su propia utilidad de Tailwind. --}}
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

{{--
    Modal de confirmación del registro.

    A diferencia de `x-ui.modal-habilitar` y `x-ui.modal-deshabilitar`, este NO
    se cierra con clic fuera ni con Esc: la incidencia ya quedó registrada, así
    que la única salida es el botón Aceptar, que termina el proceso y devuelve a
    la pantalla desde la que se abrió el formulario. Cerrarlo por otra vía dejaría
    el registro hecho sin avisar a la persona.

    Los datos se leen de `$resumen`, la copia que el componente guarda al validar,
    y no de los campos del formulario, que pueden haber cambiado mientras tanto.
    La materia que aparece es la del examen registrado, no la escrita en el
    formulario: la base la deriva de `id_examen` (#70).
--}}
@if ($confirmacionVisible)
    <div class="overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 flex justify-center items-center w-full md:inset-0 h-full max-h-full bg-overlay-modal/50"
         role="dialog" aria-modal="true" aria-labelledby="titulo-confirmacion">
        <div class="relative p-4 w-full max-w-md max-h-full">
            <div class="relative bg-neutral-primary-soft border border-default rounded-base shadow-sm p-4 md:p-6">
                <div class="flex items-center gap-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-status-habilitado-bg text-status-habilitado-fg">
                        <svg class="w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m5 13 4 4L19 7"/></svg>
                    </span>
                    <h3 id="titulo-confirmacion" class="text-lg font-medium text-heading">
                        Estudiante agregado a la central de riesgos con éxito
                    </h3>
                </div>

                <dl class="grid grid-cols-3 gap-x-3 gap-y-2 py-4 text-sm">
                    <dt class="font-medium text-heading">N° de registro</dt>
                    <dd class="col-span-2 text-body">{{ $resumen['codigo'] }}</dd>

                    <dt class="font-medium text-heading">Estudiante</dt>
                    <dd class="col-span-2 text-body">{{ $resumen['estudiante'] }}</dd>

                    <dt class="font-medium text-heading">Código SIS</dt>
                    <dd class="col-span-2 text-body">{{ $resumen['codigoSis'] }}</dd>

                    <dt class="font-medium text-heading">Materia</dt>
                    <dd class="col-span-2 text-body">{{ $resumen['materia'] }}</dd>

                    <dt class="font-medium text-heading">Motivo</dt>
                    <dd class="col-span-2 text-body">{{ $resumen['motivo'] }}</dd>

                    <dt class="font-medium text-heading">Estado</dt>
                    <dd class="col-span-2 text-body">{{ $resumen['estado'] }}</dd>

                    <dt class="font-medium text-heading">Registrado por</dt>
                    <dd class="col-span-2 text-body">{{ $resumen['registrador'] }}</dd>

                    <dt class="font-medium text-heading">Fecha y hora</dt>
                    <dd class="col-span-2 text-body">{{ $resumen['fechaHora'] }}</dd>

                    {{-- El detalle solo se escribe con el motivo "Otro", que es el
                         único que obliga a describirlo. --}}
                    @if ($resumen['descripcion'] !== '')
                        <dt class="font-medium text-heading">Descripción</dt>
                        <dd class="col-span-2 text-body">{{ $resumen['descripcion'] }}</dd>
                    @endif
                </dl>

                <div class="flex items-center justify-end border-t border-default pt-4">
                    <x-ui.button wire:click="aceptarRegistro" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="aceptarRegistro">Aceptar</span>
                        <span wire:loading wire:target="aceptarRegistro">Volviendo…</span>
                    </x-ui.button>
                </div>
            </div>
        </div>
    </div>
@endif
