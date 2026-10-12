{{--
    @file    monitor-en-vivo.blade.php
    @author  David E. Chavez T. <virzuzz12345@gmail.com>
    @created 2026-09-29
    @updated 2026-10-10

    @description
    Vista del monitor en vivo: tarjetas de conteo, tabla de estudiantes del
    examen en curso y los cinco modales del control de ingreso. Cada fila pinta
    el estado real (Ingresó / Rechazado / Sospechoso / C. de Riesgos / Pendiente)
    y su botón abre el modal que corresponde al estado, con los datos reales del
    examen (materia, aula, material permitido) y del registro o de la incidencia.

    El botón despacha el evento del modal por el payload; los modales emiten los
    eventos de acción (`confirmar-ingreso`, `rechazar-ingreso`, `permitir-ingreso`,
    `registrar-tramposo`) que escucha `MonitorEnVivo`.

    Es la versión componente de la antigua `pages/monitoreo.blade.php`. El título
    se declara como sección porque el componente delega el layout con
    `->extends('layouts.app')`; el `<div>` raíz va solo, sin `@section('content')`,
    porque Livewire envuelve el cuerpo del componente en esa sección.

    @see  \App\Livewire\Monitoreo\MonitorEnVivo
    @see  resources/views/components/ui/modal-registro-ingreso.blade.php

    @changelog
    - 2026-09-24  [David E. Chavez T.]  feat: creación inicial de la vista mock.
    - 2026-10-10  [David E. Chavez T.]  feat: la vista pasa al componente Livewire
      del monitor; los estados, la materia, el aula y el material salen del
      examen real, y el botón de cada fila arma el payload del modal con esos
      datos en vez de valores fijos.
    - 2026-10-10  [T1]  feat: subtítulo en el header con la materia y el tipo de
      examen, y el título de la tabla ("Registro de ingresos"), el buscador y los
      filtros pasan a una sola fila. Antes eran dos filas separadas dentro del
      card y la materia estaba al lado del título, sin el tipo de examen.
    - 2026-10-11  [Valery D. Ortuno P]  feat: el botón antes llamado
      "Registrar ingreso" pasa a ser "Registrar incidencia". Solo cambia el
      nombre y se quita el ícono "+"; el enlace y el resto del monitor no se
      tocan.
    - 2026-10-12  [Diego Tejerina]  fix: la tabla fija un ancho mínimo
      (min-w-[640px]) para que en mobile las columnas no se aplasten; el
      scroll horizontal queda dentro de la tabla, no en la página.
    - 2026-10-12  [Diego Tejerina]  fix: la barra de "Registro de ingresos"
      (título + buscador + filtros) pasa de `flex flex-wrap` suelto a un
      apilado explícito en mobile (`flex-col`, `sm:flex-row` desde ahí). Con
      flex-wrap solo, el min-w-[220px] del buscador no contaba para la
      decisión de ajustar a una línea (esa usa el tamaño natural del
      contenido) y el botón "Tramposos" terminaba empujado fuera de la
      pantalla en vez de bajar de línea.
    - 2026-10-12  [Diego Tejerina]  feat: en mobile (`lg:hidden`) las 6 cards
      apiladas (estado + 5 métricas) se reemplazan por una sola card
      compacta: el estado arriba (punto + "En curso" + horario) y una grilla
      de 3 columnas con las mismas 5 métricas debajo. Desde `lg:` sigue
      siendo la fila de 6 cards de siempre, sin cambios.
    - 2026-10-12  [Diego Tejerina]  fix: las 6 cards pasan a x-ui.stat-card
      (mismo componente y mismo grid de 2/6 columnas que /materias/{curso}):
      el recuadro del ícono no tenía `shrink-0`, así que con texto largo
      ("Deshabilitados", o el horario de "En curso") se aplastaba en vez de
      quedarse cuadrado. Se quita la card compacta de mobile, ya no hace
      falta. El `@section('header')` hecho a mano se reemplaza por
      @section('subtitle') + @section('header-actions') (con "Registrar
      incidencia" ahí, azul relleno): esta vista se había quedado con el
      header viejo cuando se armó x-ui.page-header, y por eso no tenía
      hamburguesa en mobile. La tabla pasa a `compact` (misma altura de fila
      que la de /materias/{curso}) y la búsqueda deja el botón "Buscar" por
      `wire:model.live.debounce.350ms` con reseteo de página en cada tecla.
--}}

@section('title', 'Monitor en vivo')

@php
    use Illuminate\View\ComponentAttributeBag;
    use Illuminate\View\ComponentSlot;

    $examen = $this->examen;
    $finExamen = \Carbon\Carbon::parse($examen->hora_inicio)->addMinutes((int) $examen->duracion)->format('H:i');
    $materia = $examen->cursos->sortBy('id_curso')->first()?->nombre_curso ?? '—';
    $aula = $examen->ambientes->first()?->nombre_ambiente ?? '—';
    $materialPermitido = $examen->materialesPermitidos->pluck('descripcion')->implode(', ');

    // Subtítulo del header, bajo el "Monitor en vivo": la materia del examen y
    // su tipo de examen en una sola línea gris. El tipo sale del catálogo
    // (`Examen::tipoExamen`) con la misma etiqueta que usa el listado de
    // exámenes de la materia (`TipoExamen::etiquetaDe()`). El `filter()` deja
    // fuera la parte que falte, así un examen sin curso o sin tipo no muestra
    // un separador huérfano.
    $subtituloExamen = collect([
        $examen->cursos->sortBy('id_curso')->first()?->nombre_curso,
        \App\Enums\TipoExamen::etiquetaDe($examen->tipoExamen?->nombre_tipo_examen),
    ])->filter()->implode(' · ');

    // Insignia de estado: se rinde con el componente del design system para no
    // duplicar el mapa de colores de `x-ui.badge`. `ComponentSlot` implementa
    // `Htmlable`, así que el texto se escapa antes de construirlo.
    $badge = function (string $estado): string {
        [$tipo, $texto] = match ($estado) {
            'Ingresó' => ['status-habilitado', 'Ingresó'],
            'Rechazado' => ['status-no-habilitado', 'Rechazado'],
            'Sospechoso' => ['status-en-revision', 'Sospechoso'],
            'C. de Riesgos' => ['status-central-riesgos', 'C. de Riesgos'],
            default => ['status-pendiente', 'Pendiente'],
        };

        return view('components.ui.badge', [
            'type' => $tipo,
            'slot' => new ComponentSlot(e($texto)),
            'attributes' => new ComponentAttributeBag(),
        ])->render();
    };

    // Botón de la fila: es el `x-ui.button` del sistema (mismo juego de clases)
    // pero armado como HTML porque la fila viaja dentro de `x-ui.table`. El
    // payload va como JSON escapado para que quepa en el atributo y Alpine lo
    // reciba ya decodificado.
    $clasesBoton = 'inline-flex items-center justify-center box-border border border-transparent focus:ring-4 focus:ring-brand-medium shadow-xs font-medium leading-5 rounded-base focus:outline-none text-white bg-brand hover:bg-brand-strong px-3 py-1.5 text-xs';

    $boton = function (array $datos, string $evento, string $etiqueta) use ($clasesBoton): string {
        return sprintf(
            '<button type="button" class="%s" @click="$dispatch(\'%s\', %s)">%s</button>',
            $clasesBoton,
            e($evento),
            e(json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)),
            e($etiqueta),
        );
    };

    $filas = [];
    $tarjetas = [];

    foreach ($this->estudiantes as $estudiante) {
        $incidencia = $estudiante['incidencia'] ?? null;

        // Estado visual y botón que abre el modal según lo que la fila tenga:
        // ingresó -> M2, rechazado -> M3, tramposo -> M4, resto -> M1.
        $estadoVisual = match ($estudiante['estado_asistencia']) {
            'presente' => 'Ingresó',
            'rechazado' => 'Rechazado',
            'riesgo' => ($incidencia['tipo'] ?? null) === \App\Enums\TipoInfraccion::Tramposo->value
                ? 'C. de Riesgos'
                : 'Sospechoso',
            default => 'Pendiente',
        };

        [$evento, $etiqueta] = match ($estadoVisual) {
            'Ingresó' => ['abrir-modal-ingresado', 'Reportar'],
            'Rechazado' => ['abrir-modal-no-habilitado', 'Ingreso'],
            'C. de Riesgos' => ['abrir-modal-central-riesgos', 'Reportar'],
            default => ['abrir-modal-ingreso', 'Ingreso'],
        };

        $nombreCompleto = trim($estudiante['nombre'].' '.$estudiante['apellido']);
        $horaIngreso = $estudiante['hora_ingreso'] ? substr($estudiante['hora_ingreso'], 0, 5) : '—';
        $horaModal = $estudiante['hora_ingreso']
            ? substr($estudiante['hora_ingreso'], 0, 8)
            : substr((string) $examen->hora_inicio, 0, 8);

        $datos = [
            'nombre' => $nombreCompleto,
            'sis' => $estudiante['sis'],
            'materia' => $materia,
            'aula' => $aula,
            'aulaActual' => $aula,
            'habilitacion' => $estadoVisual === 'Rechazado' ? 'No habilitado' : 'Habilitada',
            'horaRegistro' => $horaModal,
            'materialPermitido' => $materialPermitido,
            'registrador' => $estudiante['registrador'] ?? '—',
            'registrado' => $incidencia['fecha'] ?? '',
            'materiaOrigen' => $materia,
            'estadoOrigen' => $incidencia['estado'] ?? '',
            'motivoRegistrado' => $incidencia['motivo_etiqueta'] ?? '',
        ];

        $filas[] = [
            ['heading' => true, 'value' => $nombreCompleto],
            $estudiante['sis'],
            $horaIngreso,
            $estudiante['registrador'] ?? '—',
            ['html' => $badge($estadoVisual)],
            ['html' => $boton($datos, $evento, $etiqueta)],
        ];

        $tarjetas[] = [
            'nombre' => $nombreCompleto,
            'sis' => $estudiante['sis'],
            'hora' => $horaIngreso,
            'registrador' => $estudiante['registrador'] ?? '—',
            'estadoHtml' => $badge($estadoVisual),
            'botonHtml' => $boton($datos, $evento, $etiqueta),
        ];
    }
@endphp

{{-- El título y el subtítulo los pinta x-ui.page-header (layouts/app.blade.php),
     igual que materia-estudiantes.blade.php: esta vista tenía su propio
     @section('header') hecho a mano desde antes de que existiera ese
     componente compartido, y por eso se quedó sin la hamburguesa cuando se
     agregó ahí (el `@hasSection('header')` del layout hacía que esta vista
     nunca pasara por x-ui.page-header). --}}
@section('subtitle', $subtituloExamen)

@section('header-actions')
    <a href="{{ route('monitoreo.registro-ingreso') }}"
       aria-label="Registrar incidencia"
       class="inline-flex items-center justify-center gap-1.5 rounded-full border border-transparent bg-brand px-3 py-2.5 sm:px-4 text-sm font-medium leading-5 text-white shadow-xs hover:bg-brand-strong focus:outline-none focus:ring-4 focus:ring-brand-medium">
        <svg class="w-4 h-4 shrink-0" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        <span class="hidden sm:inline">Registrar incidencia</span>
    </a>
@endsection

<div class="flex flex-col gap-6 px-[2vh] mt-[2vh]">
    {{-- Los dos avisos se autodescartan a los 4s: antes se quedaban en
         pantalla para siempre hasta la próxima acción. `limpiarMensajes()`
         siempre vacía los dos mensajes ANTES de fijar uno nuevo (ver
         ejecutarAccion()), así que cada aviso nuevo pasa por "oculto" antes
         de "visible" y el `x-init` de abajo arranca de cero en cada uno,
         sin necesidad de un wire:key especial. --}}
    @if ($mensajeExito !== '')
        <div x-data x-init="setTimeout(() => $wire.limpiarMensajes(), 4000)">
            <x-ui.alert type="success" title="Listo">{{ $mensajeExito }}</x-ui.alert>
        </div>
    @endif

    @if ($mensajeError !== '')
        <div x-data x-init="setTimeout(() => $wire.limpiarMensajes(), 4000)">
            <x-ui.alert type="danger" title="No se pudo completar">{{ $mensajeError }}</x-ui.alert>
        </div>
    @endif

    {{-- Tarjetas de conteo: mismo componente y mismo grid (2 columnas en
         mobile, 6 en desktop) que las 5 de /materias/{curso} — antes el
         mobile tenía su propia card compacta distinta a la de desktop. --}}
    <div class="grid grid-cols-2 gap-4 lg:grid-cols-6">
        {{-- Sin ícono a propósito: "08:00 – 09:30" + el recuadro del ícono
             no entran juntos en el ancho de la card con 6 (o 2) columnas. --}}
        <x-ui.stat-card :value="substr($examen->hora_inicio, 0, 5) . ' – ' . $finExamen" label="En curso" tone="brand" label-first nowrap />

        <x-ui.stat-card :value="$this->conteos['inscritos']" label="Inscritos" tone="brand">
            <x-slot:icon>
                <svg class="w-5 h-5 sm:w-6 sm:h-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-width="2" d="M16 19h4a1 1 0 0 0 1-1v-1a3 3 0 0 0-3-3h-2m-2.236-4a3 3 0 1 0 0-4M3 18v-1a3 3 0 0 1 3-3h4a3 3 0 0 1 3 3v1a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1Zm8-10a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>
            </x-slot:icon>
        </x-ui.stat-card>

        <x-ui.stat-card :value="$this->conteos['habilitados']" label="Habilitados" tone="success">
            <x-slot:icon>
                <svg class="w-5 h-5 sm:w-6 sm:h-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18Zm-3-9 2 2 4-4"/></svg>
            </x-slot:icon>
        </x-ui.stat-card>

        <x-ui.stat-card :value="$this->conteos['deshabilitados']" label="Deshabilitados" tone="danger">
            <x-slot:icon>
                <svg class="w-5 h-5 sm:w-6 sm:h-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 17.94 6M18 18 6.06 6"/></svg>
            </x-slot:icon>
        </x-ui.stat-card>

        <x-ui.stat-card :value="$this->conteos['sospechosos']" label="Sospechosos" tone="warning">
            <x-slot:icon>
                <svg class="w-5 h-5 sm:w-6 sm:h-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-width="2" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/><path stroke="currentColor" stroke-width="2" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>
            </x-slot:icon>
        </x-ui.stat-card>

        <x-ui.stat-card :value="$this->conteos['tramposos']" label="Tramposos" tone="danger">
            <x-slot:icon>
                <svg class="w-5 h-5 sm:w-6 sm:h-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3 5 6v5c0 4.2 2.8 7.6 7 9 4.2-1.4 7-4.8 7-9V6l-7-3Z"/><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.5 9.5l5 5m0-5-5 5"/></svg>
            </x-slot:icon>
        </x-ui.stat-card>
    </div>

    <div class="bg-neutral-primary-soft border border-default rounded-base shadow-xs p-6">
        {{-- Una sola fila: título de la tabla, buscador y filtros, en ese
             orden. El buscador crece para ocupar el hueco (`flex-1`) y
             `flex-wrap` hace que en pantallas chicas se apilen sin romperse.
             Filtro y búsqueda viajan al servidor (MonitorEnVivo::filtrar()/
             buscar()) y se aplican antes de paginar; antes se hacían acá con
             un script que ocultaba filas ya pintadas, por eso la página
             traía los 50 inscritos de una sola vez. --}}
        <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center sm:gap-x-4 sm:gap-y-3">
            <h2 class="text-lg font-semibold text-heading sm:shrink-0">Registro de ingresos</h2>

            <div class="flex-1 min-w-0 sm:min-w-[280px]">
                {{-- Sin botón "Buscar": filtra solo con lo que se escribe
                     (debounce de 350ms). wireSubmit se mantiene nada más
                     para que Enter no dispare el submit nativo del <form>
                     (recargaría la página) — busqueda ya viaja sola por
                     wire:model.live. Solo letras (con tildes/ñ), dígitos y
                     espacios, igual que los demás buscadores del sistema. --}}
                <x-ui.search-input
                    name="monitor-busqueda"
                    id="monitor-busqueda"
                    placeholder="Buscar por nombre o código SIS..."
                    :show-button="false"
                    wire:model.live.debounce.350ms="busqueda"
                    wireSubmit="buscar"
                    @input="$event.target.value = $event.target.value.replace(/[^A-Za-zÁÉÍÓÚÜÑáéíóúüñ0-9 ]/g, '')"
                />
            </div>

            <div class="flex flex-wrap gap-2 sm:shrink-0">
                <x-ui.button :variant="$filtro === 'todos' ? 'default' : 'secondary'" wire:click="filtrar('todos')">Todos</x-ui.button>
                <x-ui.button :variant="$filtro === 'pendientes' ? 'default' : 'secondary'" wire:click="filtrar('pendientes')">Pendientes</x-ui.button>
                <x-ui.button :variant="$filtro === 'tramposos' ? 'default' : 'secondary'" wire:click="filtrar('tramposos')">Tramposos</x-ui.button>
            </div>
        </div>

        <div class="mt-4">
            @if ($this->estudiantes->total() === 0)
                <p class="text-center text-sm text-body py-6">No se encontraron estudiantes con ese criterio.</p>
            @else
                {{-- Mobile: una tarjeta por estudiante con scroll vertical
                     normal, igual que la lista de estudiantes del curso.
                     Desde lg: vuelve a ser la tabla. --}}
                <div class="space-y-3 lg:hidden">
                    @foreach ($tarjetas as $tarjeta)
                        <div class="bg-neutral-primary-soft border border-default rounded-base shadow-xs p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="font-semibold text-heading truncate">{{ $tarjeta['nombre'] }}</p>
                                    <p class="text-sm text-muted">{{ $tarjeta['sis'] }}</p>
                                </div>
                                {!! $tarjeta['estadoHtml'] !!}
                            </div>

                            <p class="mt-2 text-xs text-muted">Hora {{ $tarjeta['hora'] }} · Registró {{ $tarjeta['registrador'] }}</p>

                            <div class="mt-3 border-t border-default pt-3 text-end">
                                {!! $tarjeta['botonHtml'] !!}
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="hidden lg:block">
                    <x-ui.table
                        compact
                        min-width="min-w-[640px]"
                        :headers="['Estudiante', 'Código SIS', 'Hora', 'Registro', 'Estado', 'Registrar']"
                        :rows="$filas"
                    />
                </div>
            @endif
        </div>

        {{-- Mobile: apilado (texto arriba, paginación abajo, ambos
             centrados). Desde sm: layout de escritorio (texto a la izquierda
             en absolute, paginación centrada en la misma fila). Mismo patrón
             que la lista de estudiantes del curso. Con 9 páginas, los
             botones (9 números + 2 flechas) no entran en el ancho de la
             card en mobile y se salían del borde redondeado hacia los dos
             lados; el `overflow-x-auto` los deja con scroll propio en vez
             de desbordar la card. --}}
        @if ($this->estudiantes->total() > 0)
            <div class="mt-6 flex flex-col items-center gap-2 sm:relative sm:flex-row sm:items-center sm:justify-center sm:gap-0 sm:min-h-[2rem]">
                <p class="text-sm text-muted text-center sm:absolute sm:left-0 sm:text-left">
                    Mostrando {{ $this->estudiantes->count() }} de {{ $this->estudiantes->total() }} estudiantes
                </p>

                @if ($this->estudiantes->lastPage() > 1)
                    <div class="max-w-full overflow-x-auto">
                        <x-ui.pagination
                            :interactive="true"
                            :active-filled="true"
                            size="sm"
                            :current="$this->estudiantes->currentPage()"
                            :total="$this->estudiantes->lastPage()"
                        />
                    </div>
                @endif
            </div>
        @endif

        <x-ui.modal-registro-ingreso />
        <x-ui.modal-estudiante-ingresado />
        <x-ui.modal-no-habilitado />
        <x-ui.modal-central-riesgos />
        <x-ui.modal-registrar-tramposo />
    </div>

</div>
