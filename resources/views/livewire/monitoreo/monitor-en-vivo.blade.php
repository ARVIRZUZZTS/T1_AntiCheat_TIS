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
            '__rowAttrs' => [
                'data-estado' => $estudiante['estado_asistencia'],
                'data-tramposo' => ($incidencia['tipo'] ?? null) === \App\Enums\TipoInfraccion::Tramposo->value ? '1' : '0',
                'data-termino' => \Illuminate\Support\Str::lower(trim($estudiante['sis'].' '.$estudiante['nombre'].' '.$estudiante['apellido'])),
            ],
            ['heading' => true, 'value' => $nombreCompleto],
            $estudiante['sis'],
            $horaIngreso,
            $estudiante['registrador'] ?? '—',
            ['html' => $badge($estadoVisual)],
            ['html' => $boton($datos, $evento, $etiqueta)],
        ];
    }
@endphp

{{-- Header propio, con `@hasSection('header')` que ya soporta el layout
     (`layouts/app.blade.php`): se replica el header por defecto y solo se le
     añade el subtítulo con la materia y el tipo de examen. El `<h1>` sigue
     leyendo `@yield('title')`, así que `<title>` y el h1 no cambian. `@endsection`
     no emite salida, así que la raíz del componente sigue siendo un único `<div>`. --}}
@section('header')
    <header class="bg-neutral-primary-soft border-b border-default mb-[2vh]">
        <div class="px-6 py-4">
            <h1 class="text-2xl font-semibold text-heading">@yield('title', config('app.name'))</h1>
            @if ($subtituloExamen !== '')
                <p class="mt-1 text-sm text-muted truncate">{{ $subtituloExamen }}</p>
            @endif
        </div>
    </header>
@endsection

<div class="flex flex-col gap-6 px-[2vh] mt-[2vh]">
    @if ($mensajeExito !== '')
        <x-ui.alert type="success" title="Listo">{{ $mensajeExito }}</x-ui.alert>
    @endif

    @if ($mensajeError !== '')
        <x-ui.alert type="danger" title="No se pudo completar">{{ $mensajeError }}</x-ui.alert>
    @endif

    <div class="flex flex-wrap items-center justify-between gap-4">
        <a href="{{ route('monitoreo.registro-ingreso') }}"
           class="inline-flex items-center justify-center gap-1.5 rounded-base border border-transparent bg-brand px-3 py-1.5 text-xs font-medium leading-5 text-white shadow-xs hover:bg-brand-strong focus:outline-none focus:ring-4 focus:ring-brand-medium">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
            Registrar ingreso
        </a>
    </div>

    <div class="flex flex-wrap gap-[2vh]">
        <div class="flex-1 min-w-[200px] flex items-center justify-between gap-3 bg-neutral-primary-soft p-6 border border-default rounded-base shadow-xs">
            <div>
                <p class="text-sm text-body">En curso</p>
                <p class="text-3xl font-semibold text-fg-brand">{{ substr($examen->hora_inicio, 0, 5) }} – {{ $finExamen }}</p>
            </div>
            <span class="flex items-center justify-center w-12 h-12 rounded-base bg-neutral-secondary-soft text-fg-brand">
                <svg class="w-6 h-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
            </span>
        </div>
        <div class="flex-1 min-w-[200px] flex items-center justify-between gap-3 bg-neutral-primary-soft p-6 border border-default rounded-base shadow-xs">
            <div>
                <p class="text-3xl font-semibold text-fg-brand">{{ $this->conteos['inscritos'] }}</p>
                <p class="text-sm text-body">Inscritos</p>
            </div>
            <span class="flex items-center justify-center w-12 h-12 rounded-base bg-neutral-secondary-soft text-fg-brand">
                <svg class="w-6 h-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-width="2" d="M16 19h4a1 1 0 0 0 1-1v-1a3 3 0 0 0-3-3h-2m-2.236-4a3 3 0 1 0 0-4M3 18v-1a3 3 0 0 1 3-3h4a3 3 0 0 1 3 3v1a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1Zm8-10a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>
            </span>
        </div>
        <div class="flex-1 min-w-[200px] flex items-center justify-between gap-3 bg-neutral-primary-soft p-6 border border-default rounded-base shadow-xs">
            <div>
                <p class="text-3xl font-semibold text-fg-success">{{ $this->conteos['habilitados'] }}</p>
                <p class="text-sm text-body">Habilitados</p>
            </div>
            <span class="flex items-center justify-center w-12 h-12 rounded-base bg-neutral-secondary-soft text-fg-success">
                <svg class="w-6 h-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18Zm-3-9 2 2 4-4"/></svg>
            </span>
        </div>
        <div class="flex-1 min-w-[200px] flex items-center justify-between gap-3 bg-neutral-primary-soft p-6 border border-default rounded-base shadow-xs">
            <div>
                <p class="text-3xl font-semibold text-fg-danger-strong">{{ $this->conteos['deshabilitados'] }}</p>
                <p class="text-sm text-body">Deshabilitados</p>
            </div>
            <span class="flex items-center justify-center w-12 h-12 rounded-base bg-neutral-secondary-soft text-fg-danger-strong">
                <svg class="w-6 h-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 17.94 6M18 18 6.06 6"/></svg>
            </span>
        </div>
        <div class="flex-1 min-w-[200px] flex items-center justify-between gap-3 bg-neutral-primary-soft p-6 border border-default rounded-base shadow-xs">
            <div>
                <p class="text-3xl font-semibold text-fg-warning">{{ $this->conteos['sospechosos'] }}</p>
                <p class="text-sm text-body">Sospechosos</p>
            </div>
            <span class="flex items-center justify-center w-12 h-12 rounded-base bg-neutral-secondary-soft text-fg-warning">
                <svg class="w-6 h-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-width="2" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/><path stroke="currentColor" stroke-width="2" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>
            </span>
        </div>
        <div class="flex-1 min-w-[200px] flex items-center justify-between gap-3 bg-neutral-primary-soft p-6 border border-default rounded-base shadow-xs">
            <div>
                <p class="text-3xl font-semibold text-fg-danger-strong">{{ $this->conteos['tramposos'] }}</p>
                <p class="text-sm text-body">Tramposos</p>
            </div>
            <span class="flex items-center justify-center w-12 h-12 rounded-base bg-neutral-secondary-soft text-fg-danger-strong">
                <svg class="w-6 h-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3 5 6v5c0 4.2 2.8 7.6 7 9 4.2-1.4 7-4.8 7-9V6l-7-3Z"/><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.5 9.5l5 5m0-5-5 5"/></svg>
            </span>
        </div>
    </div>

    <div class="bg-neutral-primary-soft border border-default rounded-base shadow-xs p-6">
        {{-- Una sola fila: título de la tabla, buscador y filtros, en ese orden.
             El buscador crece para ocupar el hueco (`flex-1`) y `flex-wrap` hace
             que en pantallas chicas se apilen sin romperse. Los `id` del buscador
             y de los filtros no cambian: el `<script>` de filtrado de más abajo
             los usa para engancharse. --}}
        <div class="flex flex-wrap items-center gap-x-4 gap-y-3">
            <h2 class="text-lg font-semibold text-heading shrink-0">Registro de ingresos</h2>

            <div class="flex-1 min-w-[220px] sm:min-w-[280px]">
                <x-ui.search-input
                    name="monitor-busqueda"
                    id="monitor-busqueda"
                    placeholder="Buscar por nombre o código SIS..."
                    :show-button="true"
                    buttonLabel="Buscar"
                />
            </div>

            <div class="flex flex-wrap gap-2 shrink-0" id="monitor-filtros">
                <x-ui.button variant="default" data-filtro="todos">Todos</x-ui.button>
                <x-ui.button variant="secondary" data-filtro="pendientes">Pendientes</x-ui.button>
                <x-ui.button variant="secondary" data-filtro="tramposos">Tramposos</x-ui.button>
            </div>
        </div>

        <div class="mt-4">
            <div id="monitor-tabla">
                <x-ui.table
                    :headers="['Estudiante', 'Código SIS', 'Hora', 'Registro', 'Estado', 'Registrar']"
                    :rows="$filas"
                />

                <p id="monitor-sin-coincidencias"
                   class="mt-4 text-center text-sm text-body"
                   hidden>
                    No se encontraron estudiantes con ese criterio.
                </p>
            </div>
        </div>

        <div class="mt-6 flex justify-end">
            <x-ui.pagination
                :interactive="true"
                :current="$this->estudiantes->currentPage()"
                :total="$this->estudiantes->lastPage()"
            />
        </div>

        <x-ui.modal-registro-ingreso />
        <x-ui.modal-estudiante-ingresado />
        <x-ui.modal-no-habilitado />
        <x-ui.modal-central-riesgos />
        <x-ui.modal-registrar-tramposo />
    </div>

    {{-- Filtro y buscador de la tabla del monitor.

         El filtrado se hace en el navegador sobre las filas ya renderizadas,
         con la misma lógica que el buscador de registro de ingreso
         (`buscador-registro.blade.php`) pero sin su vista ni sus botones: sin
         Alpine ni wire:model, porque este proyecto solo carga el bundle de
         Livewire y `x-model` no enlaza el input que emite `x-ui.search-input`.
         La diferencia es que acá los resultados son las propias filas de la
         tabla, marcadas con `data-estado`, `data-tramposo` y `data-termino`,
         en vez de una lista aparte: así no se duplica el render de la fila ni
         se rompe el payload de los modales.

         El botón activo se pinta intercambiando las clases de las variantes
         `default` y `secondary` de `x-ui.button`, que son las que Blade ya
         aplicó al servidor. --}}
    <script>
        (function () {
            'use strict';

            var BUSQUEDA_MINIMO = 2;

            var CLASE_ACTIVA = 'inline-flex items-center justify-center box-border border focus:ring-4 shadow-xs font-medium leading-5 focus:outline-none text-white bg-brand border-transparent hover:bg-brand-strong focus:ring-brand-medium rounded-base px-4 py-2.5 text-sm';
            var CLASE_INACTIVA = 'inline-flex items-center justify-center box-border border focus:ring-4 shadow-xs font-medium leading-5 focus:outline-none text-body bg-neutral-secondary-medium border-default-medium hover:bg-neutral-tertiary-medium hover:text-heading focus:ring-neutral-tertiary rounded-base px-4 py-2.5 text-sm';

            var contenedor = document.getElementById('monitor-filtros');
            var input = document.getElementById('monitor-busqueda');
            var cuerpo = document.querySelector('#monitor-tabla tbody');
            var aviso = document.getElementById('monitor-sin-coincidencias');

            var filtroActivo = 'todos';

            function coincideFiltro(fila) {
                if (filtroActivo === 'pendientes') {
                    return fila.dataset.estado === 'pendiente';
                }

                if (filtroActivo === 'tramposos') {
                    return fila.dataset.tramposo === '1';
                }

                return true;
            }

            function coincideBusqueda(fila, termino) {
                if (termino.length < BUSQUEDA_MINIMO) {
                    return true;
                }

                return fila.dataset.termino.indexOf(termino) !== -1;
            }

            function pintarBotonActivo() {
                contenedor.querySelectorAll('[data-filtro]').forEach(function (boton) {
                    var activo = boton.dataset.filtro === filtroActivo;
                    boton.className = activo ? CLASE_ACTIVA : CLASE_INACTIVA;
                    boton.setAttribute('aria-pressed', activo ? 'true' : 'false');
                });
            }

            function aplicar() {
                var termino = input.value.trim().toLowerCase();
                var visibles = 0;

                cuerpo.querySelectorAll('tr').forEach(function (fila) {
                    if (!fila.dataset.estado) {
                        return;
                    }

                    var mostrar = coincideFiltro(fila) && coincideBusqueda(fila, termino);
                    fila.hidden = !mostrar;

                    if (mostrar) {
                        visibles++;
                    }
                });

                if (aviso !== null) {
                    aviso.hidden = visibles > 0;
                }
            }

            contenedor.querySelectorAll('[data-filtro]').forEach(function (boton) {
                boton.addEventListener('click', function () {
                    filtroActivo = boton.dataset.filtro;
                    pintarBotonActivo();
                    aplicar();
                });
            });

            input.addEventListener('input', aplicar);

            // El form que emite x-ui.search-input recargaría la página con Enter
            // y dejaría el buscador en blanco; se cancela el envío y se filtra.
            input.form.addEventListener('submit', function (evento) {
                evento.preventDefault();
                aplicar();
            });

            var botonBuscar = input.form.querySelector('button');
            if (botonBuscar !== null) {
                botonBuscar.addEventListener('click', function (evento) {
                    evento.preventDefault();
                    aplicar();
                });
            }

            pintarBotonActivo();

            // Una acción del componente (confirmar o rechazar un ingreso,
            // registrar una incidencia) re-renderiza la tabla y las filas
            // vuelven sin el `hidden` que puso este script. Se reaplica el
            // filtro para que la selección del usuario no se pierda.
            if (window.Livewire && typeof window.Livewire.hook === 'function') {
                window.Livewire.hook('morph.updated', function () {
                    aplicar();
                });
            }
        })();
    </script>
</div>
