{{--
    @file    sidebar.blade.php
    @author  Valery D. Ortuno P. <valerydariana98@gmail.com>
    @created 2026-09-24
    @updated 2026-10-10
    @updated 2026-10-24
    @updated 2026-11-08
    @updated 2026-11-15
    @updated 2026-11-16
    @updated 2026-11-19
    @updated 2026-11-20
    @updated 2026-11-22

    @description
    Sidebar de navegacion del panel, en formato acordeon. Cada seccion
    principal es o bien un link directo (clave 'route', sin subopciones) o
    bien un grupo (clave 'children') que despliega sus subopciones hacia
    abajo, indentadas y con texto mas pequenho. El grupo se abre y cierra con
    un click en su boton (chevron que rota); el grupo que contiene la pagina
    actual arranca abierto y su subopcion activa queda resaltada. La logica es
    la misma en desktop y dentro del Drawer movil (mismo markup); al tocar una
    subopcion en movil se navega y se cierra el drawer (data-drawer-hide).

    Cada item admite: 'label', 'route' (link directo), 'children' (grupo),
    'badge', 'count' y 'countEvent' (contador que se actualiza sin recargar al
    recibir ese evento de ventana con `pendientes` en el detalle). El estado de
    apertura es Alpine y se recalcula en el servidor en cada navegacion, por lo
    que sigue funcionando despues de wire:navigate. Accesible: el boton de cada
    grupo expone aria-expanded y aria-controls, y se opera con teclado.

    El titulo ($titleMain/$titleSub) se muestra junto al logo FCyT. En movil el
    cajon mide `w-72`. El boton hamburguesa lo dibuja x-ui.page-header; este
    componente solo pone el `data-drawer-target`/`id` al que apunta. Se
    mantienen el badge de notificaciones, el bloque del usuario autenticado y
    "Cerrar sesion".

    @changelog
    - 2026-09-24  [Valery D. Ortuno P]  feat: creacion inicial del sidebar.
    - 2026-09-25  [OchoaCesar]  feat: el item activo tambien se resalta en
      rutas hijas (routeName.*) para vistas de detalle.
    - 2026-09-26  [Valery D. Ortuno P]  fix: ancho del cajon y posicion del
      boton hamburguesa en movil.
    - 2026-09-28  [T1]  chore: resolver el conflicto de merge del changelog.
    - 2026-10-09  [Alisson D. Alvarado]  feat: boton de notificaciones en la
      parte inferior del sidebar.
    - 2026-10-10  [Valery D. Ortuno P]  fix: el item pasa a llamarse "Central de
      riesgos", como la HU 10.
    - 2026-10-12  [Diego Tejerina]  fix: el `<aside>` pasa de `sticky` a `fixed`
      en mobile (con `lg:sticky`); se corrige el scroll horizontal del sitio.
    - 2026-10-12  [Diego Tejerina]  fix: el boton hamburguesa se saca de este
      componente; lo dibuja x-ui.page-header. $title se divide en
      $titleMain/$titleSub y se agrega el bloque del usuario autenticado.
    - 2026-10-24  [Valery D. Ortuno P]  refactor: "Materias" pasa a "Cursos".
    - 2026-10-24  [Valery D. Ortuno P]  refactor: se quita "Inicio"; Monitor en
      vivo y Central de riesgos pasan al final.
    - 2026-11-08  [Valery D. Ortuno P]  feat: el contador de "Docentes" muestra
      las solicitudes de registro pendientes.
    - 2026-11-08  [OchoaCesar]  refactor: se retira el item "Ver auxiliares".
    - 2026-11-15  [Diego Tejerina]  feat: el menu pasa a acordeon con submenus.
      Cursos, Examenes, Monitor en vivo, Auxiliares, Invitaciones y Central de
      riesgos se agrupan con sus subopciones; Reportes queda como link directo.
      Se retira "Docentes" del menu (su ruta sigue activa, sin link). El badge
      de invitaciones pasa a la cabecera del grupo Invitaciones.
    - 2026-11-15  [Alex Candia]  feat: "Ver curso" se divide en "Ver estudiantes"
      y "Habilitar estudiantes"; Reportes pasa a grupo con sus tres reportes.
    - 2026-11-16  [Alex Candia]  feat: subopción "Eliminar curso" debajo de
      "Editar curso".
    - 2026-11-19  [Valery D. Ortuno P]  refactor: subopciones reordenadas con el
      mismo patron (Ver, Crear, Editar, Eliminar y luego las acciones propias)
      y etiquetas cortas y coherentes; Central de riesgos pasa a "Incidencias
      confirmadas" e "Incidencias pendientes". Se agregan "Eliminar examen",
      "Editar auxiliar" y "Eliminar auxiliar" (estos dos, marcadores en
      desarrollo) y vuelve "Docentes" > "Validar registros" con el contador de
      solicitudes de registro (HU 26), que se habia perdido en el acordeon.
    - 2026-11-20  [OchoaCesar]  feat: subopción "Eliminar examen" debajo de
      "Editar examen".
    - 2026-11-22  [Valery D. Ortuno P]  feat: el auxiliar ve en "Monitor en
      vivo" solo las paginas que tiene habilitadas (Ingreso, Monitor o las dos)
      por sus cursos o examenes asignados; sin ninguna, el grupo no aparece.
      Nuevo grupo "Examenes > Ver examenes" del auxiliar (HU 24).
    - 2026-11-22  [OchoaCesar]  refactor: "Archivar curso" y "Restaurar curso"
      dejan de ser dos subopciones del grupo Cursos y pasan a una sola
      subopcion "Archivar/Restaurar cursos" (pagina unica con dos pestanas).
    - 2026-11-23  [OchoaCesar]  refactor: esa pagina unica deja las pestanas y
      muestra una sola lista de cursos, con los archivados al final.
--}}

@props([
    'id' => 'sidebar',
    'titleMain' => null,
    'titleSub' => null,
    'active' => null,
    'items' => [],
])

@php
    $current = (string) \Illuminate\Support\Facades\Route::currentRouteName();

    $usuario = auth()->user();
    $nombreUsuario = $usuario ? trim($usuario->nombre_usuario.' '.$usuario->apellido) : null;
    $rolUsuario = $usuario?->roles->first()?->nombre_rol;

    // Un item (o subopcion) esta activo si su ruta coincide con la actual, con
    // el prop $active o con uno de sus patrones extra ($extra). El comodin
    // `ruta.*` solo se agrega cuando el nombre ya tiene punto (una hoja como
    // `cursos.crear`): si se aplicara al nombre pelado del listado (`cursos`)
    // marcaria tambien a sus hermanas (`cursos.crear`, `cursos.editar`...) y se
    // resaltarian dos subopciones a la vez.
    $esActivo = function (?string $ruta, array $extra = []) use ($current, $active): bool {
        if ($ruta === null) {
            return false;
        }

        if ($ruta === $active) {
            return true;
        }

        if ($current === '') {
            return false;
        }

        $patrones = [$ruta];
        if (str_contains($ruta, '.')) {
            $patrones[] = $ruta.'.*';
        }

        return request()->routeIs(...array_merge($patrones, $extra));
    };

    $contarPendientesInvitaciones = $rolUsuario === \App\Models\Rol::NOMBRE_DOCENTE && $usuario
        ? app(\App\Services\Invitacion\ListarInvitacionesRecibidasService::class)->contarPendientes($usuario)
        : 0;

    // Orden coherente en todos los grupos: Ver, Crear, Editar, Eliminar y
    // despues las acciones propias del grupo (en Cursos, Cargar CSV va antes
    // de los estudiantes porque es como se los da de alta). Las etiquetas van cortas para
    // que entren en una linea del sidebar.
    $contarSolicitudesRegistro = $rolUsuario === \App\Models\Rol::NOMBRE_DOCENTE
        ? \App\Services\Docente\ListarSolicitudesRegistroService::contarPendientes()
        : 0;

    $defaultItems = [
        ['label' => 'Cursos', 'children' => [
            ['label' => 'Ver cursos', 'route' => 'cursos'],
            ['label' => 'Crear curso', 'route' => 'cursos.crear'],
            ['label' => 'Editar curso', 'route' => 'cursos.editar'],
            // Archivar y restaurar comparten una sola lista de cursos.
            ['label' => 'Archivar/Restaurar cursos', 'route' => 'cursos.archivar-restaurar'],
            ['label' => 'Eliminar curso', 'route' => 'cursos.eliminar'],
            // ['label' => 'Cursos archivados', 'route' => 'cursos.archivados'],
            ['label' => 'Cargar CSV', 'route' => 'cursos.cargar-csv'],
            // El detalle de un curso (/cursos/{curso}) muestra las dos secciones;
            // se resalta "Ver estudiantes" como acceso principal.
            ['label' => 'Ver estudiantes', 'route' => 'cursos.ver-estudiantes', 'match' => ['cursos.detalle', 'cursos.ver']],
            ['label' => 'Habilitar estudiantes', 'route' => 'cursos.habilitar-estudiantes'],
        ]],
        ['label' => 'Examenes', 'children' => [
            ['label' => 'Ver examenes', 'route' => 'examenes'],
            ['label' => 'Crear examen', 'route' => 'examenes.crear'],
            ['label' => 'Editar examen', 'route' => 'examenes.editar'],
            ['label' => 'Eliminar examen', 'route' => 'examenes.eliminar'],
            ['label' => 'Dividir examen', 'route' => 'examenes.dividir'],
        ]],
        ['label' => 'Monitor en vivo', 'children' => [
            ['label' => 'Ingreso', 'route' => 'monitoreo.ingreso'],
            ['label' => 'Monitor', 'route' => 'monitoreo'],
        ]],
        ['label' => 'Reportes', 'children' => [
            ['label' => 'Asistencia', 'route' => 'reportes.asistencia'],
            ['label' => 'Horarios de ingreso', 'route' => 'reportes.horario-ingreso'],
            ['label' => 'Intentos no autorizados', 'route' => 'reportes.intentos-no-autorizados'],
        ]],
        ['label' => 'Auxiliares', 'children' => [
            ['label' => 'Ver auxiliares', 'route' => 'auxiliares'],
            ['label' => 'Crear auxiliar', 'route' => 'auxiliares.crear'],
            ['label' => 'Editar auxiliar', 'route' => 'auxiliares.editar'],
            ['label' => 'Eliminar auxiliar', 'route' => 'auxiliares.eliminar'],
            ['label' => 'Asignar auxiliar', 'route' => 'auxiliares.asignar'],
        ]],
        // Validacion del registro de docentes (HU 26): el contador muestra las
        // solicitudes pendientes y se actualiza al aprobar o rechazar.
        ['label' => 'Docentes', 'countEvent' => 'solicitudes-registro',
            'count' => $contarSolicitudesRegistro, 'children' => [
            ['label' => 'Validar registros', 'route' => 'docentes'],
        ]],
        ['label' => 'Invitaciones', 'countEvent' => 'invitaciones-pendientes',
            'count' => $contarPendientesInvitaciones, 'children' => [
            ['label' => 'Enviar invitacion', 'route' => 'invitaciones.enviar'],
            ['label' => 'Aceptar invitacion', 'route' => 'invitaciones.aceptar'],
        ]],
        ['label' => 'Central de riesgos', 'children' => [
            ['label' => 'Incidencias confirmadas', 'route' => 'central-riesgo.incidencias'],
            ['label' => 'Incidencias pendientes', 'route' => 'central-riesgo.sospechosos'],
        ]],
    ];

    // Paginas de Monitor en vivo que el auxiliar tiene habilitadas por sus
    // cursos o examenes asignados (HU 24). Una sola consulta.
    $paginasAuxiliar = $rolUsuario === \App\Models\Rol::NOMBRE_AUXILIAR && $usuario
        ? app(\App\Services\Auxiliar\AccesoAuxiliarService::class)->paginas((int) $usuario->id_usuario)
        : ['ingreso' => false, 'monitor' => false];

    $monitorAuxiliar = array_values(array_filter([
        $paginasAuxiliar['ingreso'] ? ['label' => 'Ingreso', 'route' => 'monitoreo.ingreso'] : null,
        $paginasAuxiliar['monitor'] ? ['label' => 'Monitor', 'route' => 'monitoreo'] : null,
    ]));

    $auxiliarItems = array_values(array_filter([
        // Sus cursos no son los del docente: tiene su propia ruta (HU aparte).
        ['label' => 'Cursos', 'route' => 'auxiliar.cursos'],
        // Solo los examenes a los que esta asignado (HU 24).
        ['label' => 'Examenes', 'children' => [
            ['label' => 'Ver examenes', 'route' => 'auxiliar.examenes'],
        ]],
        $monitorAuxiliar !== [] ? ['label' => 'Monitor en vivo', 'children' => $monitorAuxiliar] : null,
    ]));

    if ($items) {
        $nav = $items;
    } elseif ($rolUsuario === 'auxiliar') {
        $nav = $auxiliarItems;
    } else {
        $nav = $defaultItems;
    }

    // El acceso a notificaciones vive fuera de $nav (es un boton fijo del
    // sidebar, no un item mas del menu), por eso se resalta aparte.
    $notificacionesActivas = $current !== '' && request()->routeIs('notificaciones', 'notificaciones.*');
@endphp

<aside id="{{ $id }}" class="shrink-0 fixed inset-y-0 start-0 z-40 h-screen w-72 lg:sticky lg:top-0 lg:z-auto lg:w-[18%] bg-surface-sidebar text-neutral-primary transition-transform -translate-x-full lg:translate-x-0" aria-label="Sidebar">
    <button type="button" data-drawer-hide="{{ $id }}" aria-controls="{{ $id }}"
            class="lg:hidden absolute top-2.5 end-2.5 flex items-center justify-center text-neutral-primary hover:bg-brand-strong rounded-base w-9 h-9">
        <span class="sr-only">Close sidebar</span>
        <svg class="w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 17.94 6M18 18 6.06 6"/></svg>
    </button>

    <div class="flex flex-col h-full px-4 py-5 overflow-y-auto">
        <a href="{{ route('inicio') }}" class="flex items-center mb-8">
            @if (! empty($logo))
                {{ $logo }}
            @else
                <span class="shrink-0 inline-flex items-center justify-center w-11 h-11 bg-neutral-primary-soft rounded-base p-1.5">
                    <img src="{{ asset('fcytLogo.svg') }}" alt="Logo FCyT" class="h-7 w-auto">
                </span>
            @endif
            @if ($titleMain)
                <span class="ms-3 min-w-0 leading-snug">
                    <span class="block font-semibold truncate">{{ $titleMain }}</span>
                    @if ($titleSub)
                        <span class="block text-xs text-brand-subtle truncate">{{ $titleSub }}</span>
                    @endif
                </span>
            @endif
        </a>

        <ul class="space-y-1 font-medium">
            @foreach ($nav as $indice => $item)
                @php $children = $item['children'] ?? null; @endphp

                @if ($children)
                    @php
                        // El grupo arranca abierto si alguna de sus subopciones
                        // es la pagina actual, para que el usuario vea donde esta
                        // parado sin tener que expandir a mano.
                        $grupoAbierto = collect($children)->contains(fn (array $hijo): bool => $esActivo($hijo['route'] ?? null, $hijo['match'] ?? []));
                        $idSubmenu = $id.'-grupo-'.$indice;
                    @endphp
                    <li x-data="{ abierto: {{ $grupoAbierto ? 'true' : 'false' }} }">
                        <button type="button" x-on:click="abierto = ! abierto"
                                :aria-expanded="abierto ? 'true' : 'false'" aria-controls="{{ $idSubmenu }}"
                                @class([
                                    'w-full flex items-center px-3 py-2 rounded-base group',
                                    'text-neutral-primary hover:bg-brand-active hover:text-neutral-primary',
                                ])>
                            <span class="flex-1 text-start whitespace-nowrap">{{ $item['label'] }}</span>

                            @if (! empty($item['countEvent']))
                                <span x-data="{ n: {{ (int) ($item['count'] ?? 0) }} }" x-show="n > 0" @style(['display: none' => empty($item['count'])])
                                      x-on:{{ $item['countEvent'] }}.window="n = $event.detail.pendientes" x-text="n > 99 ? '99+' : n"
                                      class="inline-flex items-center justify-center min-w-5 h-5 px-1 ms-2 text-xs font-medium bg-danger-soft text-fg-danger-strong rounded-full">{{ $item['count'] ?? 0 }}</span>
                            @endif

                            <svg class="w-4 h-4 ms-2 shrink-0 transition-transform" :class="abierto ? 'rotate-180' : ''"
                                 aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 9-7 7-7-7"/></svg>
                        </button>

                        <ul id="{{ $idSubmenu }}" x-show="abierto" x-cloak class="mt-1 space-y-1 ps-3 border-s border-brand-medium/40">
                            @foreach ($children as $hijo)
                                @php $hijoActivo = $esActivo($hijo['route'] ?? null, $hijo['match'] ?? []); @endphp
                                <li>
                                    <a href="{{ route($hijo['route']) }}" data-drawer-hide="{{ $id }}" @class([
                                        'flex items-center ps-3 pe-3 py-1.5 rounded-base text-sm group',
                                        'bg-neutral-primary-soft text-fg-brand' => $hijoActivo,
                                        'text-brand-subtle hover:bg-brand-active hover:text-neutral-primary' => ! $hijoActivo,
                                    ])
                                       @if ($hijoActivo) aria-current="page" @endif>
                                        <span class="flex-1 whitespace-nowrap">{{ $hijo['label'] }}</span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </li>
                @else
                    @php $itemActivo = $esActivo($item['route'] ?? null, $item['match'] ?? []); @endphp
                    <li>
                        <a href="{{ route($item['route']) }}" data-drawer-hide="{{ $id }}" @class([
                            'flex items-center px-3 py-2 rounded-base group',
                            'bg-neutral-primary-soft text-fg-brand' => $itemActivo,
                            'text-neutral-primary hover:bg-brand-active hover:text-neutral-primary' => ! $itemActivo,
                        ])
                           @if ($itemActivo) aria-current="page" @endif>
                            <span class="flex-1 whitespace-nowrap">{{ $item['label'] }}</span>

                            @if (! empty($item['badge']))
                                <span @class([
                                    'text-xs font-medium px-1.5 py-0.5 rounded-sm',
                                    'bg-brand-softer text-fg-brand' => $itemActivo,
                                    'bg-brand-strong text-neutral-primary' => ! $itemActivo,
                                ])>{{ $item['badge'] }}</span>
                            @endif

                            @if (! empty($item['countEvent']))
                                <span x-data="{ n: {{ (int) ($item['count'] ?? 0) }} }" x-show="n > 0" @style(['display: none' => empty($item['count'])])
                                      x-on:{{ $item['countEvent'] }}.window="n = $event.detail.pendientes" x-text="n > 99 ? '99+' : n"
                                      class="inline-flex items-center justify-center min-w-5 h-5 px-1 ms-2 text-xs font-medium bg-danger-soft text-fg-danger-strong rounded-full">{{ $item['count'] ?? 0 }}</span>
                            @elseif (! empty($item['count']))
                                <span class="inline-flex items-center justify-center w-5 h-5 ms-2 text-xs font-medium bg-danger-soft text-fg-danger-strong rounded-full">{{ $item['count'] }}</span>
                            @endif
                        </a>
                    </li>
                @endif
            @endforeach
        </ul>

        <div class="mt-auto pt-4">
            <a href="{{ route('notificaciones') }}" data-drawer-hide="{{ $id }}" @class([
                'flex items-center px-3 py-2 rounded-base group',
                'bg-neutral-primary-soft text-fg-brand' => $notificacionesActivas,
                'text-neutral-primary hover:bg-brand-active hover:text-neutral-primary' => ! $notificacionesActivas,
            ])>
                <span class="ms-3 flex-1 whitespace-nowrap">Notificaciones</span>
                <span id="notificaciones-badge" class="hidden inline-flex items-center justify-center min-w-5 h-5 px-1.5 ms-2 text-xs font-medium bg-danger-soft text-fg-danger-strong rounded-full">0</span>
            </a>

            {{-- Bloque del usuario autenticado: avatar + nombre + rol. Sale
                 de auth()->user() (tabla `usuario`, login por cod_sis); sin
                 sesion no se muestra nada en vez de inventar datos. --}}
            @if ($usuario)
                <div class="mt-4 flex items-center gap-3 border-t border-brand-medium/40 pt-4">
                    <x-ui.avatar :name="$nombreUsuario" size="sm" class="shrink-0" />
                    <div class="min-w-0">
                        <p class="truncate text-sm font-medium text-neutral-primary">{{ $nombreUsuario }}</p>
                        @if ($rolUsuario)
                            <p class="truncate text-xs text-brand-subtle">{{ $rolUsuario }}</p>
                        @endif
                    </div>
                </div>
                <form method="POST" action="{{ route('logout') }}" class="mt-3">
                    @csrf
                    <x-ui.button type="submit" variant="secondary" class="w-full justify-center">Cerrar sesion</x-ui.button>
                </form>
            @endif
        </div>
    </div>
</aside>

@if ($rolUsuario === 'docente')
<script>
(function () {
    'use strict';
    var badge = document.getElementById('notificaciones-badge');
    if (!badge) {
        return;
    }

    var endpoint = '{{ route('api.notificaciones.index') }}';

    function actualizar() {
        fetch(endpoint, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
            .then(function (respuesta) {
                if (!respuesta.ok) {
                    throw new Error('HTTP ' + respuesta.status);
                }
                return respuesta.json();
            })
            .then(function (notificaciones) {
                var total = Array.isArray(notificaciones) ? notificaciones.length : 0;
                if (total > 0) {
                    badge.textContent = total > 99 ? '99+' : total;
                    badge.classList.remove('hidden');
                } else {
                    badge.classList.add('hidden');
                }
            })
            .catch(function () {
                // silencioso
            });
    }

    actualizar();
    setInterval(actualizar, 60000);
    document.addEventListener('visibilitychange', function () {
        if (document.visibilityState === 'visible') {
            actualizar();
        }
    });
})();
</script>
@endif
