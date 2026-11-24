{{--
    @file    index.blade.php
    @author  David E. Chavez T. <virzuzz12345@gmail.com>
    @created 2026-11-08
    @updated 2026-11-08
    @updated 2026-11-22
    @updated 2026-11-23

    @description
    Vista del componente Livewire Invitaciones\Index. Cada subopción del sidebar
    (Enviar / Aceptar invitación) es su propia ruta y fija `$tab`; no hay
    conmutador.

     - Enviar (HU 131): curso activo propio con examenes programados, examen
       futuro (con fecha y hora), busqueda del docente mientras se escribe (el
       elegido queda con borde azul), "Enviar invitacion", el aviso
       "Invitacion enviada con exito" con el resumen y la lista de invitaciones
       del examen con "Cancelar" en las pendientes.
     - Aceptar (HU 132): una sola lista. Las pendientes tienen Aceptar /
       Rechazar; Aceptar abre directo el modal para elegir los cursos activos a
       los que se asocia el examen. Las aceptadas que aun no tienen curso
       muestran "Asociar a un curso" con el mismo modal.

    @changelog
    - 2026-11-08  [David E. Chavez T.]  feat: creación inicial de la vista.
    - 2026-11-15  [Diego Tejerina]  refactor: se separan Enviar y Aceptar
      invitación en dos páginas; "Recibir" pasa a "Aceptar invitación" y se
      agrega el orden de las recibidas.
    - 2026-11-22  [Valery D. Ortuno P]  feat: Enviar con examenes futuros
      fechados, busqueda en vivo, resumen del envio e invitaciones enviadas con
      Cancelar (HU 131); Aceptar pide los cursos del examen y permite asociar
      las aceptadas sin curso (HU 132). Textos sin acentos y diseño responsive.
    - 2026-11-23  [Valery D. Ortuno P]  fix: Enviar lista solo cursos activos con
      examenes programados; sin ninguno, lo avisa.
    - 2026-11-23  [Valery D. Ortuno P]  refactor: una sola lista en Aceptar
      (pendientes y aceptadas sin curso); Aceptar pide el curso en el mismo
      paso y se quitan las explicaciones largas.
    - 2026-11-23  [Valery D. Ortuno P]  refactor: Rechazar y Cancelar piden
      confirmacion en un modal ("Seguro que quieres...?") y muestran el aviso
      "Invitacion rechazada." / "Invitacion cancelada." en otro, en vez del
      confirm del navegador.

    @see  app/Livewire/Invitaciones/Index.php
--}}

@section('title', $tab === 'enviar' ? 'Enviar invitacion' : 'Aceptar invitacion')

@php
    $estados = [
        'pendiente' => ['Pendiente', 'warning'],
        'aceptado' => ['Aceptada', 'success'],
        'rechazado' => ['Rechazada', 'danger'],
        'cancelado' => ['Cancelada', 'gray'],
    ];
@endphp

<div class="space-y-4 px-4 pt-[2vh] pb-8 sm:px-6 lg:px-[2vh]"
     x-data="{
         modal: false,
         modo: 'aceptar',
         invitacion: null,
         elegidos: [],
         error: '',
         guardando: false,
         abrir(modo, invitacion) {
             this.modo = modo;
             this.invitacion = invitacion;
             this.elegidos = [];
             this.error = '';
             this.modal = true;
         },
         // Confirmar rechazar (recibidas) o cancelar (enviadas), con modal.
         confirmacion: null,
         confirmando: false,
         errorConfirmacion: '',
         aviso: '',
         pedir(tipo, datos) {
             this.confirmacion = { tipo: tipo, id: datos.id, detalle: datos.detalle };
             this.errorConfirmacion = '';
         },
         cerrarConfirmacion() {
             if (! this.confirmando) {
                 this.confirmacion = null;
             }
         },
         async ejecutar() {
             if (this.confirmando || ! this.confirmacion) {
                 return;
             }
             this.confirmando = true;
             this.errorConfirmacion = '';
             const tipo = this.confirmacion.tipo;
             try {
                 const error = tipo === 'rechazar'
                     ? await $wire.rechazar(this.confirmacion.id)
                     : await $wire.cancelar(this.confirmacion.id);
                 if (error) {
                     this.errorConfirmacion = error;
                 } else {
                     this.confirmacion = null;
                     this.aviso = tipo === 'rechazar' ? 'Invitacion rechazada.' : 'Invitacion cancelada.';
                 }
             } catch (e) {
                 this.errorConfirmacion = 'No se pudo conectar con el servidor.';
             } finally {
                 this.confirmando = false;
             }
         },
         async confirmar() {
             if (this.guardando || this.elegidos.length === 0) {
                 return;
             }
             this.guardando = true;
             this.error = '';
             try {
                 const ids = this.elegidos.map(Number);
                 const error = this.modo === 'aceptar'
                     ? await $wire.aceptar(this.invitacion.id, ids)
                     : await $wire.asociar(this.invitacion.id, ids);
                 if (error) {
                     this.error = error;
                 } else {
                     this.modal = false;
                 }
             } catch (e) {
                 this.error = 'No se pudo conectar con el servidor.';
             } finally {
                 this.guardando = false;
             }
         },
     }">
    @if ($mensajeExito !== '')
        <div class="rounded-base border border-success-subtle bg-success-soft p-4 text-sm text-fg-success-strong" role="status">
            <p class="font-semibold">{{ $mensajeExito }}</p>
            @if ($resumenEnvio !== [])
                <dl class="mt-2 grid grid-cols-1 gap-x-6 gap-y-1 sm:grid-cols-3">
                    <div><dt class="text-xs uppercase">Curso</dt><dd class="font-medium wrap-break-word">{{ $resumenEnvio['curso'] }}</dd></div>
                    <div><dt class="text-xs uppercase">Examen</dt><dd class="font-medium wrap-break-word">{{ $resumenEnvio['examen'] }}</dd></div>
                    <div><dt class="text-xs uppercase">Docente invitado</dt><dd class="font-medium wrap-break-word">{{ $resumenEnvio['docente'] }}</dd></div>
                </dl>
            @endif
        </div>
    @endif
    @if ($mensajeError !== '')
        <x-ui.alert type="danger" title="No se pudo completar">{{ $mensajeError }}</x-ui.alert>
    @endif

    @if ($tab === 'recibir')
        <div class="space-y-4 rounded-base border border-default bg-neutral-primary-soft p-4 shadow-xs sm:p-6">
            <h3 class="text-lg font-semibold text-heading">Invitaciones recibidas</h3>

            @if ($invitaciones === [])
                <p class="text-sm text-body">No tienes invitaciones pendientes.</p>
            @else
                <div class="grid grid-cols-1 gap-3 lg:grid-cols-2">
                    @foreach ($invitaciones as $invitacion)
                        @php
                            $pendiente = $invitacion['estado'] === 'pendiente';
                            $datosModal = \Illuminate\Support\Js::from([
                                'id' => $invitacion['id'],
                                'examen' => $invitacion['examen'].($invitacion['fecha_examen'] ? ' · '.$invitacion['fecha_examen'] : ''),
                            ]);
                            $datosRechazo = \Illuminate\Support\Js::from([
                                'id' => $invitacion['id'],
                                'detalle' => $invitacion['examen'].($invitacion['fecha_examen'] ? ' · '.$invitacion['fecha_examen'] : '').' - '.$invitacion['invitador'],
                            ]);
                        @endphp
                        <div wire:key="invitacion-{{ $invitacion['id'] }}" class="flex flex-col gap-3 rounded-base border border-default p-4">
                            <div class="space-y-1 text-sm">
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="font-semibold text-heading wrap-break-word">{{ $invitacion['examen'] }}</p>
                                    @unless ($pendiente)
                                        <x-ui.badge type="success">Aceptada</x-ui.badge>
                                    @endunless
                                </div>
                                <p class="text-body">Fecha del examen: {{ $invitacion['fecha_examen'] ?? '—' }}</p>
                                <p class="text-body">Invitado por: {{ $invitacion['invitador'] }}{{ $invitacion['curso'] ? ' ('.$invitacion['curso'].')' : '' }}</p>
                                <p class="text-muted">Enviada el {{ $invitacion['enviada_en'] ?? '—' }}</p>
                            </div>
                            <div class="flex flex-col gap-2 sm:flex-row">
                                @if ($pendiente)
                                    <x-ui.button type="button" variant="default" class="justify-center"
                                                 @click="abrir('aceptar', {{ $datosModal }})">
                                        Aceptar
                                    </x-ui.button>
                                    <x-ui.button-cancelar type="button" class="justify-center"
                                                          @click="pedir('rechazar', {{ $datosRechazo }})">
                                        Rechazar
                                    </x-ui.button-cancelar>
                                @else
                                    <x-ui.button type="button" variant="default" class="justify-center"
                                                 @click="abrir('asociar', {{ $datosModal }})">
                                        Asociar a un curso
                                    </x-ui.button>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    @else
        <div class="space-y-4 rounded-base border border-default bg-neutral-primary-soft p-4 shadow-xs sm:p-6">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <label for="invitar-curso" class="mb-1 block text-sm font-medium text-heading">Curso</label>
                    <select id="invitar-curso" wire:model.live="idCurso" class="w-full rounded-base border border-default-medium bg-neutral-secondary-medium px-3 py-2.5 text-sm text-heading focus:border-brand focus:ring-brand">
                        <option value="0">Seleccione un curso</option>
                        @foreach ($cursos as $curso)
                            <option value="{{ $curso['id'] }}">{{ $curso['nombre'] }}</option>
                        @endforeach
                    </select>
                    @if ($cursos === [])
                        <p class="mt-1 text-xs text-body">No tienes cursos activos con examenes programados.</p>
                    @endif
                </div>

                <div>
                    <label for="invitar-examen" class="mb-1 block text-sm font-medium text-heading">Examen</label>
                    <select id="invitar-examen" wire:model.live="idExamen" @disabled($idCurso <= 0)
                            class="w-full rounded-base border border-default-medium bg-neutral-secondary-medium px-3 py-2.5 text-sm text-heading focus:border-brand focus:ring-brand disabled:opacity-50">
                        <option value="0">Seleccione un examen</option>
                        @foreach ($examenes as $examen)
                            <option value="{{ $examen['id'] }}">{{ $examen['nombre'] }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <label for="invitar-busqueda" class="mb-1 block text-sm font-medium text-heading">Buscar docente</label>
                <input id="invitar-busqueda" type="text" wire:model.live.debounce.400ms="busquedaDocente" @disabled($idExamen <= 0)
                       autocomplete="off" placeholder="Nombre o codigo SIS (minimo 2 letras)"
                       class="w-full rounded-base border border-default-medium bg-neutral-secondary-medium px-3 py-2.5 text-sm text-heading placeholder:text-body focus:border-brand focus:ring-brand disabled:opacity-50" />
            </div>

            @if (count($docentes) > 0)
                <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                    @foreach ($docentes as $docente)
                        <button type="button" wire:key="docente-{{ $docente['id'] }}" wire:click="seleccionarDocente({{ $docente['id'] }})"
                                aria-pressed="{{ $docenteSeleccionado === $docente['id'] ? 'true' : 'false' }}"
                                @class([
                                    'rounded-base border p-3 text-start text-sm',
                                    'border-brand ring-2 ring-brand' => $docenteSeleccionado === $docente['id'],
                                    'border-default hover:bg-neutral-secondary-medium' => $docenteSeleccionado !== $docente['id'],
                                ])>
                            <span class="block font-medium text-heading">{{ $docente['nombre'] }}</span>
                            <span class="block text-xs text-body">Codigo SIS: {{ $docente['sis'] ?? '—' }}</span>
                        </button>
                    @endforeach
                </div>
            @elseif ($idExamen > 0 && mb_strlen(trim($busquedaDocente)) >= 2)
                <p class="text-sm text-body">No se encontraron docentes habilitados.</p>
            @endif

            <div class="flex justify-end">
                <x-ui.button type="button" variant="default" class="w-full justify-center sm:w-auto" wire:click="enviar"
                             wire:loading.attr="disabled" wire:target="enviar" :disabled="$docenteSeleccionado === null">
                    <span wire:loading.remove wire:target="enviar">Enviar invitacion</span>
                    <span wire:loading wire:target="enviar">Enviando...</span>
                </x-ui.button>
            </div>
        </div>

        @if ($idExamen > 0)
            <div class="space-y-3 rounded-base border border-default bg-neutral-primary-soft p-4 shadow-xs sm:p-6">
                <h3 class="text-lg font-semibold text-heading">Invitaciones de este examen</h3>
                @if ($enviadas === [])
                    <p class="text-sm text-body">Todavia no invitaste a nadie a este examen.</p>
                @else
                    <ul class="space-y-2">
                        @foreach ($enviadas as $enviada)
                            @php
                                [$etiqueta, $tipoBadge] = $estados[$enviada['estado']] ?? [$enviada['estado'], 'gray'];
                                $datosCancelacion = \Illuminate\Support\Js::from(['id' => $enviada['id'], 'detalle' => $enviada['invitado']]);
                            @endphp
                            <li wire:key="enviada-{{ $enviada['id'] }}" class="flex flex-col gap-2 rounded-base border border-default px-3 py-2 sm:flex-row sm:items-center sm:justify-between">
                                <div class="min-w-0 text-sm">
                                    <p class="font-medium text-heading wrap-break-word">{{ $enviada['invitado'] }}</p>
                                    <p class="text-xs text-body">Enviada el {{ $enviada['enviada_en'] }}</p>
                                </div>
                                <div class="flex shrink-0 items-center gap-3">
                                    <x-ui.badge :type="$tipoBadge">{{ $etiqueta }}</x-ui.badge>
                                    @if ($enviada['estado'] === 'pendiente')
                                        <button type="button" @click="pedir('cancelar', {{ $datosCancelacion }})"
                                                class="text-xs font-bold text-fg-danger-strong hover:underline">
                                            Cancelar
                                        </button>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        @endif
    @endif

    {{-- Modal: confirmar rechazar o cancelar una invitacion. --}}
    <div x-show="confirmacion !== null" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-black/50 p-4"
         role="dialog" aria-modal="true" aria-labelledby="titulo-confirmacion"
         @click.self="cerrarConfirmacion()" @keydown.escape.window="cerrarConfirmacion()">
        <div class="w-full max-w-md rounded-xl border border-neutral-quaternary bg-neutral-primary-soft shadow-lg">
            <div class="border-b border-neutral-quaternary px-4 py-3">
                <h2 id="titulo-confirmacion" class="text-[15px] font-semibold text-fg-brand"
                    x-text="confirmacion?.tipo === 'rechazar' ? 'Rechazar invitacion' : 'Cancelar invitacion'"></h2>
            </div>
            <div class="space-y-2 p-4 text-sm">
                <p class="text-heading" x-text="confirmacion?.tipo === 'rechazar' ? '¿Seguro que quieres rechazar esta invitacion?' : '¿Seguro que quieres cancelar esta invitacion?'"></p>
                <p class="font-semibold text-heading wrap-break-word" x-text="confirmacion?.detalle"></p>
                <p x-show="errorConfirmacion !== ''" x-text="errorConfirmacion" class="text-fg-danger-strong"></p>
            </div>
            <div class="flex flex-col gap-2 border-t border-neutral-quaternary px-4 py-3 sm:flex-row sm:justify-end">
                <button type="button" @click="cerrarConfirmacion()" :disabled="confirmando"
                        class="inline-flex items-center justify-center rounded-base border border-default px-4 py-2 text-sm font-medium text-body hover:bg-neutral-secondary-medium disabled:opacity-50">
                    Volver
                </button>
                <button type="button" @click="ejecutar()" :disabled="confirmando"
                        class="inline-flex items-center justify-center rounded-base bg-danger px-4 py-2 text-sm font-medium text-white hover:bg-danger-strong disabled:cursor-not-allowed disabled:opacity-50">
                    <span x-text="confirmando ? 'Guardando...' : (confirmacion?.tipo === 'rechazar' ? 'Rechazar' : 'Cancelar invitacion')"></span>
                </button>
            </div>
        </div>
    </div>

    {{-- Modal: aviso de exito despues de rechazar o cancelar. --}}
    <div x-show="aviso !== ''" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-black/50 p-4"
         role="dialog" aria-modal="true" aria-labelledby="titulo-aviso-invitacion"
         @click.self="aviso = ''" @keydown.escape.window="aviso = ''">
        <div class="w-full max-w-sm rounded-xl border border-neutral-quaternary bg-neutral-primary-soft p-6 text-center shadow-lg">
            <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-success-soft text-fg-success-strong">
                <svg class="h-6 w-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.5 11.5 11 14l4.5-5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
            </span>
            <p id="titulo-aviso-invitacion" class="mt-4 text-sm text-heading" x-text="aviso"></p>
            <button type="button" @click="aviso = ''"
                    class="mt-5 inline-flex w-full items-center justify-center rounded-base bg-brand px-4 py-2 text-sm font-medium text-white hover:bg-brand-strong">
                Aceptar
            </button>
        </div>
    </div>

    {{-- Modal: elegir los cursos a los que se asocia el examen. --}}
    <div x-show="modal" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-black/50 p-4"
         role="dialog" aria-modal="true" aria-labelledby="titulo-asociar-curso"
         @click.self="! guardando && (modal = false)" @keydown.escape.window="! guardando && (modal = false)">
        <div class="w-full max-w-lg rounded-xl border border-neutral-quaternary bg-neutral-primary-soft shadow-lg">
            <div class="border-b border-neutral-quaternary px-4 py-3">
                <h2 id="titulo-asociar-curso" class="text-[15px] font-semibold text-fg-brand">Asociar a un curso</h2>
                <p class="mt-1 text-sm text-body wrap-break-word" x-text="invitacion?.examen"></p>
            </div>

            <div class="max-h-[60vh] space-y-2 overflow-y-auto p-4 text-sm">
                @if ($cursos === [])
                    <p class="text-body">No tienes cursos activos.</p>
                @else
                    @foreach ($cursos as $curso)
                        <label class="flex items-center gap-3 rounded-base border border-default px-3 py-2 text-heading"
                               :class="elegidos.includes('{{ $curso['id'] }}') ? 'border-brand ring-2 ring-brand' : ''">
                            <input type="checkbox" value="{{ $curso['id'] }}" x-model="elegidos"
                                   class="h-4 w-4 rounded-sm border-default-medium text-brand focus:ring-brand">
                            <span class="wrap-break-word">{{ $curso['nombre'] }}</span>
                        </label>
                    @endforeach
                @endif
                <p x-show="error !== ''" x-text="error" class="text-fg-danger-strong"></p>
            </div>

            <div class="flex flex-col gap-2 border-t border-neutral-quaternary px-4 py-3 sm:flex-row sm:justify-end">
                <button type="button" @click="modal = false" :disabled="guardando"
                        class="inline-flex items-center justify-center rounded-base border border-default px-4 py-2 text-sm font-medium text-body hover:bg-neutral-secondary-medium disabled:opacity-50">
                    Cancelar
                </button>
                <button type="button" @click="confirmar()" :disabled="guardando || elegidos.length === 0"
                        class="inline-flex items-center justify-center rounded-base bg-brand px-4 py-2 text-sm font-medium text-white hover:bg-brand-strong disabled:cursor-not-allowed disabled:opacity-50">
                    <span x-text="guardando ? 'Guardando...' : (modo === 'aceptar' ? 'Aceptar invitacion' : 'Asociar')"></span>
                </button>
            </div>
        </div>
    </div>
</div>
