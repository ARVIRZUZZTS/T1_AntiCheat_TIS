{{--
    @file    gestion-auxiliares.blade.php
    @author  David E. Chavez T. <virzuzz12345@gmail.com>
    @created 2026-10-19
    @updated 2026-11-08
    @updated 2026-11-09
    @updated 2026-11-24

    @description
     Vista del componente Livewire GestionAuxiliares: listado de todas las
     cuentas de auxiliar con filtro de estado y búsqueda. "Crear Auxiliar" ya no abre un
    modal: enlaza a la página de alta (CrearAuxiliar) y el aviso de éxito del
    alta llega por la sesión al volver. La acción «Ver detalle» de cada fila
    abre un modal con todos los cursos a los que pertenece el auxiliar.

    @changelog
     - 2026-11-15  [Diego Tejerina]  refactor: se quita el modal de alta; "Crear
       Auxiliar" enlaza a la página propia y el éxito viaja por la sesión.
     - 2026-11-24  [Equipo T1]  refactor: se quita el filtro Baja; el estado es
       Activo si hay asignación a un curso e Inactivo si solo existe la cuenta.
    - 2026-10-19  [ARVIRZUZZTS]  feat: creación inicial de la vista.
    - 2026-11-08  [David E. Chavez T.]  style: mover la creación a un modal
      con inputs verticales y botones Cancelar/Guardar.
    - 2026-11-09  [OchoaCesar]  style: el botón "Crear Auxiliar" sale de la
      tarjeta (queda encima, a la derecha) y se agregan los desplegables
      Estado y Curso en la cabecera de la tarjeta; por defecto ambos en
      "Todos" muestran todos los auxiliares.
    - 2026-11-09  [OchoaCesar]  fix: los filtros suben a la barra superior
      junto al botón (en la cabecera de la tarjeta deformaban el espacio de
      la tabla) y la tabla vacía muestra "No hay auxiliares registrados"
      dentro del cuerpo (prop `empty` de x-ui.table).
    - 2026-11-24  [OchoaCesar]  style: Se retiro el filtro de cursos de la
      pestaña. Ahora se verán todos los cursos a los que pertenece un auxiliar
      en el botón de ver detalle.
    - 2026-11-24  [OchoaCesar]  style: se retira la columna Curso de la tabla
      y los cursos pasan al modal «Ver detalle» (columna Acciones).

    @see  app/Livewire/Auxiliares/GestionAuxiliares.php
    @see  resources/views/components/ui/modal-detalle-auxiliar.blade.php
--}}

@section('title', 'Auxiliares')

<div class="flex flex-col gap-6 px-[2vh] mt-[2vh]">
    {{-- Aviso de exito al volver de "Crear auxiliar" (viaja por la sesion). --}}
    @if (session('auxiliarCreado'))
        @php $creado = session('auxiliarCreado'); @endphp
        <div x-data="{ visible: true }" x-show="visible">
            @unless ($creado['credencialesEnviadas'])
                <x-ui.alert type="warning" title="Correo no enviado" class="mb-3">
                    No se pudo enviar el correo. Puedes reenviar las credenciales desde la lista de auxiliares.
                </x-ui.alert>
            @endunless

            <x-ui.alert type="success" title="Auxiliar creado">
                <p>Recuerda asignarlo a un curso o examen: sin asignación no podrá ver ningún examen.</p>
                <div class="mt-3 flex flex-col gap-2 sm:flex-row">
                    <x-ui.button variant="default" class="justify-center"
                                 :href="route('auxiliares.asignar', ['auxiliar' => $creado['id']])">
                        Asignar ahora
                    </x-ui.button>
                    <x-ui.button variant="secondary" class="justify-center" x-on:click="visible = false">
                        Volver a la lista
                    </x-ui.button>
                </div>
            </x-ui.alert>
        </div>
    @endif

    {{-- Buscador automatico + filtros. "Crear auxiliar" ya no vive aca: es una
         subopcion del sidebar. --}}
    <div class="bg-neutral-primary-soft border border-default rounded-base shadow-xs p-3 flex flex-col gap-3 lg:flex-row lg:items-center">
        <div class="flex-1 min-w-0">
            <x-ui.search-input name="busqueda-auxiliar" placeholder="Buscar por nombre, usuario, correo o carnet"
                :show-button="false" pill wire:model.live.debounce.300ms="busqueda" />
        </div>

        <div class="flex gap-2 overflow-x-auto lg:shrink-0" role="group" aria-label="Filtro de estado">
            @foreach (['todos' => 'Todos', 'Activo' => 'Activo', 'Inactivo' => 'Inactivo'] as $valor => $etiqueta)
                <x-ui.button type="button" variant="{{ $filtroEstado === $valor ? 'default' : 'secondary' }}"
                             wire:click="filtrarEstado('{{ $valor }}')">
                    {{ $etiqueta }}
                </x-ui.button>
            @endforeach
        </div>
    </div>

    <div class="bg-neutral-primary-soft border border-default rounded-base shadow-xs p-4 md:p-6">
        <h3 class="text-lg font-semibold text-heading mb-4">Auxiliares</h3>

        @php
            // Celdas largas (usuario, correo, asignaciones) con ancho acotado y
            // quiebre de texto para que la tabla no desborde el ancho.
            $filas = $auxiliares->map(function (array $a) {
                $badge = $a['coordinador']
                    ? '<span class="ms-2 inline-flex items-center rounded-full bg-brand-softer px-2 py-0.5 text-xs font-medium text-fg-brand align-middle">Coordinador</span>'
                    : '';
                $sisHtml = $a['codigoSis'] !== ''
                    ? '<span class="mt-0.5 block text-xs text-body-subtle">Código: '.e($a['codigoSis']).'</span>'
                    : '';
                $nombreHtml = '<span class="font-medium text-heading break-words">'.e($a['nombreCompleto']).'</span>'.$badge.$sisHtml;

                $usuarioHtml = '<span class="break-all">'.e($a['usuario']).'</span>';
                $correoHtml = '<span class="break-all">'.($a['correo'] !== '' ? e($a['correo']) : '—').'</span>';

                $asignaciones = array_merge(
                    array_map(fn (string $c): string => 'Curso: '.e($c), $a['cursos']),
                    array_map(fn (string $x): string => 'Examen: '.e($x), $a['examenes']),
                );
                $asignacionesHtml = $asignaciones === []
                    ? '<span class="text-muted">Sin asignaciones</span>'
                    : '<ul class="space-y-0.5">'.implode('', array_map(fn (string $t): string => '<li>'.$t.'</li>', $asignaciones)).'</ul>';

                $verDetalle = '<button type="button" wire:click="verDetalle('.$a['id'].')" aria-label="Ver detalle de '.e($a['nombreCompleto']).'" aria-haspopup="dialog" class="inline-flex min-h-11 shrink-0 items-center px-2 text-sm font-medium text-fg-brand underline-offset-2 hover:underline focus:rounded-base focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-medium">Ver detalle</button>';

                return [
                    ['html' => '<div class="min-w-[9rem] max-w-[14rem]">'.$nombreHtml.'</div>'],
                    ['html' => '<div class="max-w-[12rem]">'.$usuarioHtml.'</div>'],
                    $a['carnet'] !== '' ? $a['carnet'] : '—',
                    ['html' => '<div class="max-w-[14rem]">'.$correoHtml.'</div>'],
                    ['html' => '<div class="max-w-[16rem]">'.$asignacionesHtml.'</div>'],
                    $a['estadoEtiqueta'],
                    ['html' => $verDetalle],
                ];
            })->toArray();
        @endphp

        <x-ui.table compact empty="No hay auxiliares registrados"
            :headers="['Auxiliar', 'Usuario', 'Carnet', 'Correo', 'Asignaciones', 'Estado', 'Acciones']"
            :rows="$filas" />
    </div>

    <x-ui.modal-detalle-auxiliar
        :abierto="$detalleAbierto"
        :cursos="$detalleCursos"
    />
</div>
