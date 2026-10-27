{{--
    @file    monitor-en-vivo.blade.php
    @author  David E. Chavez T. <virzuzz12345@gmail.com>
    @created 2026-09-29
    @updated 2026-10-12

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
    - 2026-10-12  [T1]  refactor: el botón "Registrar incidencia" abre directo
      el formulario (antes pasaba por la pantalla intermedia "Registro ingreso")
      y le manda el examen en curso, el rol de docente y el usuario por la URL.
    - 2026-10-25  [Valery D. Ortuno P]  feat: se quita el botón oculto que
      llevaba a la página de registro (retirada); "Reportar SIS" pasa a
      "Registrar incidencia" y abre el modal del estudiante no inscrito, y se
      suman el modal de confirmación del registro y el estado (Confirmado o En
      revision) según el rol en los modales (HU 5).
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
    $badgeGenerico = function (string $tipo, string $texto): string {
        $clases = match ($tipo) {
            'status-habilitado' => 'inline-flex items-center px-2 py-1 text-xs font-medium rounded-full border border-green-200 bg-green-100 text-green-800',
            'ingreso' => 'inline-flex items-center px-2 py-1 text-xs font-medium rounded-full border border-blue-200 bg-blue-100 text-blue-800',
            'status-no-habilitado', 'rechazado', 'riesgo' => 'inline-flex items-center px-2 py-1 text-xs font-medium rounded-full border border-red-200 bg-red-100 text-red-800',
            'status-pendiente' => 'inline-flex items-center px-2 py-1 text-xs font-medium rounded-full border border-gray-200 bg-gray-100 text-gray-800',
            'status-en-revision' => 'inline-flex items-center px-2 py-1 text-xs font-medium rounded-full border border-yellow-200 bg-yellow-100 text-yellow-800',
            'status-central-riesgos' => 'inline-flex items-center px-2 py-1 text-xs font-medium rounded-full border border-red-200 bg-red-100 text-red-800',
            default => 'inline-flex items-center px-2 py-1 text-xs font-medium rounded-full border border-blue-200 bg-blue-100 text-blue-800',
        };

        return '<span class="'.$clases.'">'.e($texto).'</span>';
    };

    $badgeHabilitacion = fn (bool $habilitado): string => $badgeGenerico(
        $habilitado ? 'status-habilitado' : 'status-no-habilitado',
        $habilitado ? 'Habilitado' : 'Deshabilitado',
    );

    $badgeIngreso = function (array $estudiante, ?array $incidencia) use ($badgeGenerico): string {
        $estadoAsistencia = $estudiante['estado_asistencia'] ?? 'pendiente';

        if ($estadoAsistencia === 'presente') {
            return $badgeGenerico('ingreso', 'Ingresó');
        }

        if ($estadoAsistencia === 'rechazado') {
            return $badgeGenerico('status-no-habilitado', 'Rechazado');
        }

        if ($estadoAsistencia === 'riesgo') {
            return $badgeGenerico('status-en-revision', 'Sospechoso');
        }

        return $badgeGenerico('status-pendiente', 'Pendiente');
    };

    $badgeIncidencia = function (?array $incidencia) use ($badgeGenerico, $examen): string {
        if ($incidencia === null) {
            return '';
        }

        $motivosIngreso = array_map(
            fn ($m) => $m->value,
            \App\Enums\Motivo::paraIngreso()
        );

        // Incidencia preexistente de otro examen -> C. de Riesgos (rojo).
        if (($incidencia['examen_id'] ?? null) !== (int) $examen->id_examen) {
            return $badgeGenerico('status-central-riesgos', 'C. de Riesgos');
        }

        // Incidencia registrada durante el ingreso -> Incidencia (amarillo).
        if (in_array($incidencia['motivo'] ?? '', $motivosIngreso, true)) {
            return $badgeGenerico('status-en-revision', 'Incidencia');
        }

        // Incidencia registrada durante el monitor -> C. de Riesgos (rojo) si tramposo.
        if (($incidencia['tipo'] ?? null) === \App\Enums\TipoInfraccion::Tramposo->value) {
            return $badgeGenerico('status-central-riesgos', 'C. de Riesgos');
        }

        return $badgeGenerico('status-en-revision', 'Incidencia');
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
    $filasIngresados = [];
    $filasPendientes = [];
    $tarjetasIngresados = [];
    $tarjetasPendientes = [];

    foreach ($this->estudiantes as $estudiante) {
        $incidencia = $estudiante['incidencia'] ?? null;

        // Estado visual y botón que abre el modal según lo que la fila tenga:
        // ingresó -> M2, rechazado -> M3, tramposo -> M4, resto -> M1.
        $estadoVisual = match (true) {
            $incidencia !== null => 'C. de Riesgos',
            $estudiante['estado_asistencia'] === 'presente' => 'IngresA3',
            $estudiante['estado_asistencia'] === 'rechazado' => 'Rechazado',
            default => 'Pendiente',
        };

        [$evento, $etiqueta] = match ($estadoVisual) {
            'Ingresó' => ['abrir-modal-ingresado', 'Reporte'],
            'Rechazado' => ['abrir-modal-no-habilitado', 'Ingreso'],
            'C. de Riesgos' => ['abrir-modal-central-riesgos', 'Reporte'],
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

        $badgesHtml = $badgeHabilitacion((bool) ($estudiante['habilitado'] ?? true))
            .' '.$badgeIngreso($estudiante, $incidencia)
            .($incidencia !== null ? ' '.$badgeIncidencia($incidencia) : '')
            .' [dbg: '.$estadoVisual.'/'.$estudiante['estado_asistencia'].']';

        $fila = [
            ['heading' => true, 'value' => $nombreCompleto],
            $estudiante['sis'],
        ];

        if ($this->modoVista === 'monitor') {
            $fila[] = $horaIngreso;
            $fila[] = $estudiante['registrador'] ?? '—';
        }

        $fila[] = ['html' => $badgesHtml];
        $fila[] = ['html' => $boton($datos, $evento, $etiqueta)];

        if ($estadoVisual === 'Ingresó') {
            $filasIngresados[] = $fila;
            $tarjetasIngresados[] = [
                'nombre' => $nombreCompleto,
                'sis' => $estudiante['sis'],
                'hora' => $horaIngreso,
                'registrador' => $estudiante['registrador'] ?? '—',
                'estadoHtml' => $badgesHtml,
                'botonHtml' => $boton($datos, $evento, $etiqueta),
            ];
        } else {
            $filasPendientes[] = $fila;
            $tarjetasPendientes[] = [
                'nombre' => $nombreCompleto,
                'sis' => $estudiante['sis'],
                'hora' => $horaIngreso,
                'registrador' => $estudiante['registrador'] ?? '—',
                'estadoHtml' => $badgesHtml,
                'botonHtml' => $boton($datos, $evento, $etiqueta),
            ];
        }
    }

    $filas = $filasPendientes;
    $tarjetas = $tarjetasPendientes;
    if ($filasPendientes !== [] && $filasIngresados !== []) {
        $filas[] = ['__separator' => true];
        $tarjetas[] = ['__separador' => true];
    }
    $filas = array_merge($filas, $filasIngresados);
    $tarjetas = array_merge($tarjetas, $tarjetasIngresados);
@endphp

{{-- El título y el subtítulo los pinta x-ui.page-header (layouts/app.blade.php),
     igual que materia-estudiantes.blade.php: esta vista tenía su propio
     @section('header') hecho a mano desde antes de que existiera ese
     componente compartido, y por eso se quedó sin la hamburguesa cuando se
     agregó ahí (el `@hasSection('header')` del layout hacía que esta vista
     nunca pasara por x-ui.page-header). --}}
@section('subtitle', $this->textoProximosExamenes())

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
         mobile tenA-a su propia card compacta distinta a la de desktop. --}}
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

    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex gap-2">
            <x-ui.button :variant="$modoVista === 'ingreso' ? 'default' : 'secondary'" wire:click="cambiarVista('ingreso')">Ingreso</x-ui.button>
            <x-ui.button :variant="$modoVista === 'monitor' ? 'default' : 'secondary'" wire:click="cambiarVista('monitor')">Monitor</x-ui.button>
        </div>

        <div class="flex flex-wrap gap-2 text-xs">
            <x-ui.button variant="secondary" wire:click="ajustarExamenPrueba(30)">Iniciar en 30 min (mock)</x-ui.button>
            <x-ui.button variant="secondary" wire:click="ajustarExamenPrueba(15)">Iniciar en 15 min (mock)</x-ui.button>
            <x-ui.button variant="secondary" wire:click="ajustarExamenPrueba(5)">Iniciar en 5 min (mock)</x-ui.button>
            <x-ui.button variant="secondary" wire:click="ajustarExamenPrueba(0)">Iniciar ahora (mock)</x-ui.button>
        </div>
    </div>

    @if (in_array($this->estadoAcceso(), ['registro_abierto', 'en_curso']))
    <div class="bg-neutral-primary-soft border border-default rounded-base shadow-xs p-6">
        {{-- Una sola fila: título de la tabla, buscador y filtros, en ese
             orden. El buscador crece para ocupar el hueco (`flex-1`) y
             `flex-wrap` hace que en pantallas chicas se apilen sin romperse.
             Filtro y búsqueda viajan al servidor (MonitorEnVivo::filtrar()/
             buscar()) y se aplican antes de paginar; antes se hacían acá con
             un script que ocultaba filas ya pintadas, por eso la página
             traía los 50 inscritos de una sola vez. --}}
        <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center sm:gap-x-4 sm:gap-y-3">
            <h2 class="text-lg font-semibold text-heading sm:shrink-0">{{ $modoVista === 'monitor' ? 'Monitor en vivo' : 'Registro de ingresos' }}</h2>

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
                @if ($modoVista === 'ingreso')
                    <x-ui.button :variant="$filtro === 'todos' ? 'default' : 'secondary'" wire:click="filtrar('todos')">Todos</x-ui.button>
                    <x-ui.button :variant="$filtro === 'habilitados' ? 'default' : 'secondary'" wire:click="filtrar('habilitados')">Habilitados</x-ui.button>
                    <x-ui.button :variant="$filtro === 'deshabilitados' ? 'default' : 'secondary'" wire:click="filtrar('deshabilitados')">Deshabilitados</x-ui.button>
                @else
                    <x-ui.button :variant="$filtro === 'central-riesgos' ? 'default' : 'secondary'" wire:click="filtrar('central-riesgos')">Central de riesgos</x-ui.button>
                @endif
            </div>
        </div>

        <div class="mt-4">
            @if ($this->estudiantes->total() === 0)
                <p class="text-center text-sm text-body py-6">No se encontraron estudiantes con ese criterio.</p>
                {{-- Un estudiante que no está en la lista (no inscrito o no
                     registrado en la base) también se puede registrar: abre el
                     modal del no inscrito con el SIS buscado precargado. --}}
                <div class="text-center pb-6">
                    <x-ui.button wire:click="abrirRegistroNoInscrito" variant="danger">Registrar incidencia</x-ui.button>
                </div>
            @else
                {{-- Mobile: una tarjeta por estudiante con scroll vertical
                     normal, igual que la lista de estudiantes del curso.
                     Desde lg: vuelve a ser la tabla. --}}
                <div class="space-y-3 lg:hidden">
                    @foreach ($tarjetas as $tarjeta)
                        @if (isset($tarjeta['__separador']))
                            <hr class="border-default my-2">
                            @continue
                        @endif
                        <div class="bg-neutral-primary-soft border border-default rounded-base shadow-xs p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="font-semibold text-heading truncate">{{ $tarjeta['nombre'] }}</p>
                                    <p class="text-sm text-muted">{{ $tarjeta['sis'] }}</p>
                                </div>
                                {!! $tarjeta['estadoHtml'] !!}
                            </div>

                            @if ($modoVista === 'monitor')
                                <p class="mt-2 text-xs text-muted">Hora {{ $tarjeta['hora'] }} · Registró {{ $tarjeta['registrador'] }}</p>
                            @endif

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
                        :headers="$this->modoVista === 'monitor'
                            ? ['Estudiante', 'Código SIS', 'Hora', 'Registro', 'Estados', 'Acción']
                            : ['Estudiante', 'Código SIS', 'Estados', 'Acción']"
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
    </div>

    @else
        <div class="flex flex-col gap-3">
            <h2 class="text-lg font-semibold text-heading">Próximos exámenes</h2>
            <ul class="flex flex-col gap-4">
                @foreach ($this->proximosExamenes() as $examenProximo)
                    <li class="flex cursor-pointer flex-col gap-4 border bg-neutral-primary-soft rounded-base shadow-xs px-5 py-6 sm:px-8
                        {{ $examenProximo['id'] === $this->idExamen ? 'border-brand ring-2 ring-brand' : 'border-default hover:border-brand-subtle' }}"
                        role="button" tabindex="0"
                        wire:click="seleccionarExamen({{ $examenProximo['id'] }})">
                        <div style="display:flex; justify-content:space-between; width:100%; gap:1rem; flex-wrap:wrap; align-items:center;">
                        <div class="flex items-center gap-4 lg:w-72">
                            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-lg bg-brand-softer text-fg-brand-strong">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-6 w-6" aria-hidden="true"><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/><path d="M10 9H8"/><path d="M16 13H8"/><path d="M16 17H8"/></svg>
                            </div>
                            <div class="flex min-w-0 flex-col items-start gap-1">
                                <h3 class="text-xl font-bold text-heading">{{ $examenProximo['tipo'] }}</h3>
                                <span class="inline-flex items-center px-1.5 py-0.5 text-xs font-medium rounded-full border bg-neutral-secondary-medium border-default-medium text-heading">
                                    {{ $examenProximo['estado'] }}
                                </span>
                            </div>
                        </div>

                        <div class="font-bold text-heading">
                            Faltan: {{ $examenProximo['tiempo_restante'] }}
                        </div>

                        <div class="flex flex-col gap-1 text-sm text-body">
                            <span>Fecha: {{ \Carbon\Carbon::parse($examenProximo['fecha'])->format('d-m-Y') }}</span>
                            <span>Horario: {{ $examenProximo['hora_inicio'] }}{{ $examenProximo['hora_fin'] ? ' - ' . $examenProximo['hora_fin'] : '' }}</span>
                            <span>Ambientes: {{ $examenProximo['ambientes'] ?: '-' }}</span>
                        </div>
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    <x-ui.modal-registro-ingreso />
    <x-ui.modal-estudiante-ingresado />
    <x-ui.modal-no-habilitado />
    <x-ui.modal-central-riesgos />
    <x-ui.modal-registrar-tramposo :contexto="$modoVista" :como-auxiliar="$this->registraComoAuxiliar" />
    <x-ui.modal-registrar-no-inscrito :contexto="$modoVista" :como-auxiliar="$this->registraComoAuxiliar" />
    <x-ui.modal-opciones-rechazo />

    @if ($incidenciaRegistrada)
        <x-ui.modal-incidencia-registrada />
    @endif

</div>
