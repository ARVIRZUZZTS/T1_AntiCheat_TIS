{{--
    @file    sidebar.blade.php
    @author  Valery D. Ortuno P. <valerydariana98@gmail.com>
    @created 2026-09-24
    @updated 2026-09-24

    @description
    Sidebar de navegaciA3n del panel. $items es un arreglo de A-tems:
    ['label', 'route' (nombre de ruta), 'badge', 'count']. El A-tem activo
    se resalta en blanco sobre el fondo azul (`bg-surface-sidebar`); se
    detecta solo por la ruta actual y/o por el prop $active. El tA-tulo
    ($title) se muestra junto al logo FCyT dentro de una caja blanca con
    borde redondeado (para que el SVG, que es negro, resalte sobre el
    azul). $logo es un slot opcional para reemplazar el logo.
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
@endphp

@if ($showTrigger)
    <button data-drawer-target="{{ $id }}" data-drawer-toggle="{{ $id }}" aria-controls="{{ $id }}" type="button"
            class="lg:hidden inline-flex items-center justify-center ms-3 mt-3 text-neutral-primary bg-surface-sidebar box-border border border-transparent hover:bg-brand-strong font-medium leading-5 rounded-base text-sm p-2.5 focus:outline-none">
        <span class="sr-only">Open sidebar</span>
        <svg class="w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-width="2" d="M5 7h14M5 12h14M5 17h10"/></svg>
    </button>
@endif

<aside id="{{ $id }}" class="fixed top-0 left-0 z-40 h-full w-[15%] bg-surface-sidebar text-neutral-primary transition-transform -translate-x-full lg:translate-x-0" aria-label="Sidebar">
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
                        || (($item['route'] ?? null) !== null && $current !== '' && request()->routeIs($item['route']));
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
    </div>
</aside>