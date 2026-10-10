{{--
    @file    sidebar.blade.php
@author  Valery D. Ortuno P. <valerydariana98@gmail.com>
    @created 2026-09-24
    @updated 2026-10-09

    @description
    Sidebar de navegación del panel. $items es un arreglo de ítems:
    ['label', 'route' (nombre de ruta), 'badge', 'count']. El ítem activo
    se resalta en blanco sobre el fondo azul (`bg-surface-sidebar`); se
    detecta solo por la ruta actual y/o por el prop $active. El título
    ($title) se muestra junto al logo FCyT dentro de una caja blanca con
    borde redondeado (para que el SVG, que es negro, resalte sobre el
    azul). $logo es un slot opcional para reemplazar el logo.

    En móvil el cajón mide `w-72` en vez del 15% de ancho, que a esa
    pantalla apenas dejaba 56px, y el botón hamburguesa va en una franja
    propia con el mismo padding que el encabezado de la página.

    @changelog
    - 2026-09-24  [Valery D. Ortuno P]  feat: creación inicial del sidebar.
    - 2026-09-25  [OchoaCesar]  feat: el item activo también se resalta en
      rutas hijas (routeName.*) para vistas de detalle.
    - 2026-09-26  [Valery D. Ortuno P]  fix: ancho del cajón y posición del
      botón hamburguesa en móvil; se corrigen también los acentos del
      comentario de cabecera, que quedaron como "A3" al escribir el archivo.
    - 2026-09-28  [T1]  chore: resolver el conflicto de merge del changelog al
      integrar dev en feature/28 (#28).
    - 2026-10-09  [Alisson D. Alvarado]  feat: botón de notificaciones en la
      parte inferior del sidebar, con icono de campana y estado activo propio.
--}}

@props([
    'id' => 'sidebar',
    'title' => null,
    'active' => null,
    'showTrigger' => true,
    'items' => [],
])

@php
    $current = (string) \Illuminate\Support\Facades\Route::currentRouteName();

    $defaultItems = [
        ['label' => 'Inicio', 'route' => 'inicio'],
        ['label' => 'Materias', 'route' => 'materias'],
        ['label' => 'Exámenes', 'route' => 'examenes'],
        ['label' => 'Monitor en vivo', 'route' => 'monitoreo'],
        ['label' => 'Central de riesgo', 'route' => 'central-riesgo'],
        ['label' => 'Usuarios y roles', 'route' => 'usuarios'],
        ['label' => 'Reportes', 'route' => 'reportes'],
    ];

    $nav = $items ?: $defaultItems;

    // El acceso a notificaciones vive fuera de $nav (es un botón fijo del
    // sidebar, no un ítem más del menú), por eso se resalta aparte.
    $notificacionesActivas = $current !== '' && request()->routeIs('notificaciones', 'notificaciones.*');
@endphp

@if ($showTrigger)
    {{-- En movil el boton va en una franja propia con el mismo padding que el
         encabezado de la pagina (px-6 py-4), para que quede alineado con el
         titulo a la izquierda y no encima de el. --}}
    <div class="lg:hidden flex items-center px-6 py-4">
        <button data-drawer-target="{{ $id }}" data-drawer-toggle="{{ $id }}" aria-controls="{{ $id }}" type="button"
                class="inline-flex items-center justify-center text-neutral-primary bg-surface-sidebar box-border border border-transparent hover:bg-brand-strong font-medium leading-5 rounded-base text-sm p-2.5 focus:outline-none">
            <span class="sr-only">Open sidebar</span>
            <svg class="w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-width="2" d="M5 7h14M5 12h14M5 17h10"/></svg>
        </button>
    </div>
@endif

<aside id="{{ $id }}" class="shrink-0 sticky top-0 h-screen w-72 lg:w-[15%] bg-surface-sidebar text-neutral-primary transition-transform -translate-x-full lg:translate-x-0" aria-label="Sidebar">
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
            @if ($title)
                <span class="ms-3 font-light leading-snug">{{ $title }}</span>
            @endif
        </a>

        <ul class="space-y-2 font-medium">
            @foreach ($nav as $item)
                @php
                    $isActive = ($item['route'] ?? null) === $active
                        || (($item['route'] ?? null) !== null && $current !== '' && request()->routeIs($item['route'], $item['route'].'.*'));
                @endphp
                <li>
                    <a href="{{ route($item['route']) }}" @class([
                        'flex items-center px-3 py-2 rounded-base group',
                        'bg-neutral-primary-soft text-fg-brand' => $isActive,
                        'text-neutral-primary hover:bg-brand-active hover:text-neutral-primary' => ! $isActive,
                    ])>
                        <span class="flex-1 whitespace-nowrap">{{ $item['label'] }}</span>

                        @if (! empty($item['badge']))
                            <span @class([
                                'text-xs font-medium px-1.5 py-0.5 rounded-sm',
                                'bg-brand-softer text-fg-brand' => $isActive,
                                'bg-brand-strong text-neutral-primary' => ! $isActive,
                            ])>{{ $item['badge'] }}</span>
                        @endif

                        @if (! empty($item['count']))
                            <span class="inline-flex items-center justify-center w-5 h-5 ms-2 text-xs font-medium bg-danger-soft text-fg-danger-strong rounded-full">{{ $item['count'] }}</span>
                        @endif
                    </a>
                </li>
            @endforeach
        </ul>

        <div class="mt-auto pt-4">
            <a href="{{ route('notificaciones') }}" @class([
                'flex items-center px-3 py-2 rounded-base group',
                'bg-neutral-primary-soft text-fg-brand' => $notificacionesActivas,
                'text-neutral-primary hover:bg-brand-active hover:text-neutral-primary' => ! $notificacionesActivas,
            ])>
                <svg class="shrink-0 w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5.365V3m0 2.365a5.338 5.338 0 0 1 5.133 3.666c.346 1.097.542 2.238.542 3.404 0 3.44-1.65 5.175-3.646 6.51-.585.39-.968.973-1.029 1.644-.112.841.273 1.414.542 1.95M12 5.365A5.338 5.338 0 0 0 6.867 9.03c-.346 1.097-.542 2.238-.542 3.404 0 3.44 1.65 5.175 3.646 6.51.585.39.968.973 1.029 1.644.112.841-.273 1.414-.542 1.95M5 21h14"/></svg>
                <span class="ms-3 flex-1 whitespace-nowrap">Notificaciones</span>
            </a>
        </div>
    </div>
</aside>