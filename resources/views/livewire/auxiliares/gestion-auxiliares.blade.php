{{--
    @file    gestion-auxiliares.blade.php
    @author  David E. Chavez T. <virzuzz12345@gmail.com>
    @created 2026-10-19
    @updated 2026-11-08
    @updated 2026-11-09

    @description
     Vista del componente Livewire GestionAuxiliares: listado de todas las
     cuentas de auxiliar con filtros de estado y curso. "Crear Auxiliar" ya no abre un
    modal: enlaza a la página de alta (CrearAuxiliar) y el aviso de éxito del
    alta llega por la sesión al volver.

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

    @see  app/Livewire/Auxiliares/GestionAuxiliares.php
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

        <div class="lg:w-56 lg:shrink-0">
            <x-ui.select name="filtroCurso" wire:model.live="filtroCurso"
                :options="['todos' => 'Todos los cursos'] + $cursos->pluck('nombre_curso', 'id_curso')->all()"
                :selected="$filtroCurso" />
        </div>
    </div>

    <div class="bg-neutral-primary-soft border border-default rounded-base shadow-xs p-6">
        <h3 class="text-lg font-semibold text-heading mb-4">Auxiliares</h3>
        <x-ui.table compact empty="No hay auxiliares registrados" :headers="['Auxiliar', 'Código SIS', 'Curso', 'Estado']" :rows="$auxiliares->map(fn($a) => [
            ['heading' => true, 'value' => $a->nombre_usuario.' '.$a->apellido],
            $a->cod_sis,
            $a->nombre_curso !== '' ? $a->nombre_curso : '—',
            $a->estado,
        ])->toArray()" />
    </div>
</div>
