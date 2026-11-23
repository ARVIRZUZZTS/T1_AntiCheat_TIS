{{--
    @file    index.blade.php
    @author  David E. Chavez T. <virzuzz12345@gmail.com>
    @created 2026-11-08
    @updated 2026-11-08
    @updated 2026-11-22

    @description
    Vista del componente Livewire Invitaciones\Index. Cada subopción del sidebar
    (Enviar / Aceptar invitación) es su propia ruta y fija `$tab`; no hay
    conmutador.

     - Enviar (HU 131): curso activo propio, examen futuro (con fecha y hora),
       busqueda del docente mientras se escribe (el elegido queda con borde
       azul), "Enviar invitacion", el aviso "Invitacion enviada con exito" con
       el resumen y la lista de invitaciones del examen con "Cancelar" en las
       pendientes.
     - Aceptar (HU 132): recibidas pendientes con orden reciente / antigua.
       "Aceptar" abre un modal en pasos: confirmar, asociar el examen a uno o
       varios cursos activos propios (o "Mas tarde") y listo. Las aceptadas que
       quedaron sin curso se listan en "Examenes por asociar a un curso" con el
       mismo paso de asociar.

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
    - 2026-11-22  [Valery D. Ortuno P]  refactor: "Aceptar" abre un modal en pasos
      (confirmar, asociar el examen a un curso o "Mas tarde", y listo) con
      mensajes de lo que esta pasando; "Rechazar" pide confirmacion. Las
      recibidas muestran la fecha del examen.

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
         // Modal de aceptar en pasos: confirmar -> cursos -> listo.
         modal: false,
         paso: 'confirmar',
         invitacion: null,
         elegidos: [],
         cursosAsociados: '',
         error: '',
         trabajando: false,
         abrirAceptar(invitacion) {
             this.invitacion = invitacion;
             this.paso = 'confirmar';
             this.elegidos = [];
             this.error = '';
             this.modal = true;
         },
         abrirAsociar(invitacion) {
             this.invitacion = { ...invitacion, yaAceptada: true };
             this.paso = 'cursos';
             this.elegidos = [];
             this.error = '';
             this.modal = true;
         },
         async aceptar() {
             if (this.trabajando) {
                 return;
             }
             this.trabajando = true;
             this.error = '';
             try {
                 const error = await $wire.aceptar(this.invitacion.id);
                 if (error) {
                     this.error = error;
                 } else {
                     this.paso = 'cursos';
                 }
             } catch (e) {
                 this.error = 'No se pudo conectar con el servidor.';
             } finally {
                 this.trabajando = false;
             }
         },
         async asociar() {
             if (this.trabajando || this.elegidos.length === 0) {
                 return;
             }
             this.trabajando = true;
             this.error = '';
             try {
                 const resultado = await $wire.asociarCursos(this.invitacion.id, this.elegidos.map(Number));
                 if (resultado.error) {
                     this.error = resultado.error;
                 } else {
                     this.cursosAsociados = resultado.cursos;
                     this.paso = 'listo';
                 }
             } catch (e) {
                 this.error = 'No se pudo conectar con el servidor.';
             } finally {
                 this.trabajando = false;
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
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h3 class="text-lg font-semibold text-heading">Invitaciones pendientes</h3>
                @if (count($invitacionesRecibidas) > 1)
                    <div class="flex gap-2" role="group" aria-label="Ordenar invitaciones">
                        <x-ui.button type="button" variant="{{ $ordenRecibidas === 'reciente' ? 'default' : 'secondary' }}" wire:click="ordenarRecibidas('reciente')">
                            Mas reciente
                        </x-ui.button>
                        <x-ui.button type="button" variant="{{ $ordenRecibidas === 'antigua' ? 'default' : 'secondary' }}" wire:click="ordenarRecibidas('antigua')">
                            Mas antigua
                        </x-ui.button>
                    </div>
                @endif
            </div>

            @if (count($invitacionesRecibidas) === 0)
                <p class="text-sm text-body">No tienes invitaciones pendientes.</p>
            @else
                <div class="grid grid-cols-1 gap-3 lg:grid-cols-2">
                    @foreach ($invitacionesRecibidas as $invitacion)
                        <div wire:key="recibida-{{ $invitacion['id'] }}" class="flex flex-col gap-3 rounded-base border border-default p-4">
                            <div class="space-y-1 text-sm">
                                <p class="font-semibold text-heading wrap-break-word">{{ $invitacion['examen']['nombre'] }}</p>
                                <p class="text-body">Fecha del examen: {{ $invitacion['examen']['fecha'] ?? '—' }}{{ ! empty($invitacion['examen']['hora_inicio']) ? ' '.$invitacion['examen']['hora_inicio'] : '' }}</p>
                                <p class="text-body">Curso del que invita: {{ $invitacion['curso'] ?? '—' }}</p>
                                <p class="text-body">Invitado por: {{ $invitacion['invitador']['nombre'] }}</p>
                                <p class="text-muted">Enviada el {{ $invitacion['enviada_en'] ? \Carbon\Carbon::parse($invitacion['enviada_en'])->format('d/m/Y') : '—' }}</p>
                            </div>
                            <div class="flex flex-col gap-2 sm:flex-row">
                                <x-ui.button type="button" variant="default" class="justify-center"
                                             @click="abrirAceptar({{ \Illuminate\Support\Js::from([
                                                 'id' => $invitacion['id'],
                                                 'examen' => $invitacion['examen']['nombre'].(! empty($invitacion['examen']['fecha']) ? ' · '.$invitacion['examen']['fecha'] : ''),
                                                 'invitador' => $invitacion['invitador']['nombre'],
                                             ]) }})">
                                    Aceptar
                                </x-ui.button>
                                <x-ui.button-cancelar type="button" class="justify-center" wire:click="rechazar({{ $invitacion['id'] }})"
                                                      wire:confirm="Rechazar la invitacion? El docente que te invito sera notificado.">
                                    Rechazar
                                </x-ui.button-cancelar>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        @if ($aceptadasSinCurso !== [])
            {{-- Aceptadas antes de que se pidiera el curso: sin curso propio el
                 examen no tiene a tus estudiantes. --}}
            <div class="space-y-3 rounded-base border border-warning-subtle bg-neutral-primary-soft p-4 shadow-xs sm:p-6">
                <div>
                    <h3 class="text-lg font-semibold text-heading">Examenes por asociar a un curso</h3>
                    <p class="text-sm text-body">Aceptaste estas invitaciones, pero el examen todavia no esta en ninguno de tus cursos. Asocialo a un curso para que tus estudiantes puedan rendirlo.</p>
                </div>
                <ul class="space-y-2">
                    @foreach ($aceptadasSinCurso as $aceptada)
                        <li wire:key="sin-curso-{{ $aceptada['id'] }}" class="flex flex-col gap-2 rounded-base border border-default px-3 py-2 sm:flex-row sm:items-center sm:justify-between">
                            <div class="min-w-0 text-sm">
                                <p class="font-medium text-heading wrap-break-word">{{ $aceptada['examen'] }}</p>
                                <p class="text-xs text-body">Invitado por: {{ $aceptada['invitador'] }}</p>
                            </div>
                            <x-ui.button type="button" variant="secondary" class="shrink-0 justify-center"
                                         @click="abrirAsociar({{ \Illuminate\Support\Js::from(['id' => $aceptada['id'], 'examen' => $aceptada['examen'], 'invitador' => $aceptada['invitador']]) }})">
                                Asociar curso
                            </x-ui.button>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
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
                    @if ($idCurso > 0 && $examenes === [])
                        <p class="mt-1 text-xs text-body">Este curso no tiene examenes futuros.</p>
                    @endif
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
                             wire:loading.attr="disabled" :disabled="$docenteSeleccionado === null">
                    Enviar invitacion
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
                            @php [$etiqueta, $tipoBadge] = $estados[$enviada['estado']] ?? [$enviada['estado'], 'gray']; @endphp
                            <li wire:key="enviada-{{ $enviada['id'] }}" class="flex flex-col gap-2 rounded-base border border-default px-3 py-2 sm:flex-row sm:items-center sm:justify-between">
                                <div class="min-w-0 text-sm">
                                    <p class="font-medium text-heading wrap-break-word">{{ $enviada['invitado']['nombre'] }}</p>
                                    <p class="text-xs text-body">Enviada el {{ \Carbon\Carbon::parse($enviada['enviada_en'])->format('d/m/Y') }}</p>
                                </div>
                                <div class="flex shrink-0 items-center gap-3">
                                    <x-ui.badge :type="$tipoBadge">{{ $etiqueta }}</x-ui.badge>
                                    @if ($enviada['estado'] === 'pendiente')
                                        <button type="button" wire:click="cancelar({{ $enviada['id'] }})" wire:confirm="Cancelar la invitacion?"
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

    {{-- Modal de aceptar: 1) confirmar, 2) elegir los cursos (o "Mas tarde"),
         3) listo. Cada paso dice que esta pasando. --}}
    <div x-show="modal" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-black/50 p-4"
         role="dialog" aria-modal="true" aria-labelledby="titulo-aceptar-invitacion"
         @click.self="! trabajando && (modal = false)" @keydown.escape.window="! trabajando && (modal = false)">
        <div class="w-full max-w-lg rounded-xl border border-neutral-quaternary bg-neutral-primary-soft shadow-lg">
            <div class="border-b border-neutral-quaternary px-4 py-3">
                <h2 id="titulo-aceptar-invitacion" class="text-[15px] font-semibold text-fg-brand"
                    x-text="paso === 'confirmar' ? 'Aceptar invitacion' : (paso === 'cursos' ? 'Asociar el examen a un curso' : 'Listo')"></h2>
                <p class="mt-1 text-sm text-body wrap-break-word" x-text="invitacion?.examen"></p>
                <p class="text-xs text-muted" x-show="invitacion?.invitador" x-text="'Invitado por ' + (invitacion?.invitador || '')"></p>
            </div>

            <div class="max-h-[60vh] space-y-3 overflow-y-auto p-4 text-sm">
                {{-- Paso 1: confirmar. --}}
                <div x-show="paso === 'confirmar'" class="space-y-2 text-body">
                    <p>Al aceptar, este examen se compartira contigo. Tendras tu propia configuracion de reglas y materiales, sin cambiar la del otro docente.</p>
                    <p>Despues te pediremos a que curso tuyo pertenece el examen, para inscribir a tus estudiantes.</p>
                </div>

                {{-- Paso 2: cursos. --}}
                <div x-show="paso === 'cursos'" class="space-y-3">
                    <div class="rounded-base border border-success-subtle bg-success-soft px-3 py-2 text-fg-success-strong" x-show="invitacion && ! invitacion.yaAceptada">
                        Invitacion aceptada.
                    </div>
                    @if ($cursos === [])
                        <p class="text-body">No tienes cursos activos. Podras asociar el examen cuando tengas uno; queda en "Examenes por asociar a un curso".</p>
                    @else
                        <p class="text-body">Elige a que cursos tuyos pertenece este examen. Sus estudiantes quedaran inscritos y apareceran en el Monitor y el Ingreso del examen.</p>
                        <div class="space-y-2">
                            @foreach ($cursos as $curso)
                                <label class="flex items-center gap-3 rounded-base border border-default px-3 py-2 text-heading"
                                       :class="elegidos.includes('{{ $curso['id'] }}') ? 'border-brand ring-2 ring-brand' : ''">
                                    <input type="checkbox" value="{{ $curso['id'] }}" x-model="elegidos"
                                           class="h-4 w-4 rounded-sm border-default-medium text-brand focus:ring-brand">
                                    <span class="wrap-break-word">{{ $curso['nombre'] }}</span>
                                </label>
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- Paso 3: listo. --}}
                <div x-show="paso === 'listo'" class="space-y-2">
                    <div class="rounded-base border border-success-subtle bg-success-soft px-3 py-2 text-fg-success-strong">
                        El examen quedo en: <span class="font-semibold" x-text="cursosAsociados"></span>.
                    </div>
                    <p class="text-body">Tus estudiantes ya estan inscritos. Ya puedes verlo en Ver examenes y controlarlo desde Monitor en vivo.</p>
                </div>

                <p x-show="trabajando" class="text-body"
                   x-text="paso === 'confirmar' ? 'Aceptando la invitacion...' : 'Asociando el examen e inscribiendo a tus estudiantes, esto puede tardar unos segundos...'"></p>
                <p x-show="error !== ''" x-text="error" class="text-fg-danger-strong"></p>
            </div>

            <div class="flex flex-col gap-2 border-t border-neutral-quaternary px-4 py-3 sm:flex-row sm:justify-end">
                <template x-if="paso === 'confirmar'">
                    <div class="flex flex-col gap-2 sm:flex-row">
                        <button type="button" @click="modal = false" :disabled="trabajando"
                                class="inline-flex items-center justify-center rounded-base border border-default px-4 py-2 text-sm font-medium text-body hover:bg-neutral-secondary-medium disabled:opacity-50">
                            Cancelar
                        </button>
                        <button type="button" @click="aceptar()" :disabled="trabajando"
                                class="inline-flex items-center justify-center rounded-base bg-brand px-4 py-2 text-sm font-medium text-white hover:bg-brand-strong disabled:cursor-not-allowed disabled:opacity-50">
                            <span x-text="trabajando ? 'Aceptando...' : 'Aceptar invitacion'"></span>
                        </button>
                    </div>
                </template>
                <template x-if="paso === 'cursos'">
                    <div class="flex flex-col gap-2 sm:flex-row">
                        <button type="button" @click="modal = false" :disabled="trabajando"
                                class="inline-flex items-center justify-center rounded-base border border-default px-4 py-2 text-sm font-medium text-body hover:bg-neutral-secondary-medium disabled:opacity-50">
                            Mas tarde
                        </button>
                        <button type="button" @click="asociar()" :disabled="trabajando || elegidos.length === 0"
                                class="inline-flex items-center justify-center rounded-base bg-brand px-4 py-2 text-sm font-medium text-white hover:bg-brand-strong disabled:cursor-not-allowed disabled:opacity-50">
                            <span x-text="trabajando ? 'Asociando...' : 'Asociar curso'"></span>
                        </button>
                    </div>
                </template>
                <template x-if="paso === 'listo'">
                    <button type="button" @click="modal = false"
                            class="inline-flex items-center justify-center rounded-base bg-brand px-4 py-2 text-sm font-medium text-white hover:bg-brand-strong">
                        Entendido
                    </button>
                </template>
            </div>
        </div>
    </div>
</div>
