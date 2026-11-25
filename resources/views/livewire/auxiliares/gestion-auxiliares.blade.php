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
    @if (session('mensaje'))
        <x-ui.alert type="success" title="Listo">{{ session('mensaje') }}</x-ui.alert>
    @endif

    {{-- Buscador automatico + filtros. "Crear auxiliar" ya no vive aca: es una
         subopcion del sidebar. --}}
    <div class="bg-neutral-primary-soft border border-default rounded-base shadow-xs p-3 flex flex-col gap-3 lg:flex-row lg:items-center">
        <div class="flex-1 min-w-0">
            <x-ui.search-input name="busqueda-auxiliar" placeholder="Buscar por nombre o codigo SIS"
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

    <div class="bg-neutral-primary-soft border border-default rounded-base shadow-xs p-6">
        <h3 class="text-lg font-semibold text-heading mb-4">Auxiliares</h3>

        @php
            $filas = $auxiliares->map(fn ($a) => [
                ['heading' => true, 'value' => $a->nombre_usuario.' '.$a->apellido],
                $a->cod_sis,
                $a->estado,
                ['html' => '<button type="button" wire:click="verDetalle('.$a->id_usuario.')" aria-label="Ver detalle de '.e($a->nombre_usuario.' '.$a->apellido).'" aria-haspopup="dialog" class="inline-flex min-h-11 shrink-0 items-center px-2 text-sm font-medium text-body-subtle underline-offset-2 hover:text-heading hover:underline focus:rounded-base focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-medium">Ver detalle</button>'],
            ])->toArray();
        @endphp

        <x-ui.table compact empty="No hay auxiliares registrados" :headers="['Auxiliar', 'Código SIS', 'Estado', 'Acciones']" :rows="$filas" />
    </div>

    <x-ui.modal-detalle-auxiliar
        :abierto="$detalleAbierto"
        :cursos="$detalleCursos"
    />
</div>
