{{--
    @file    sidebar.blade.php
    @author  Valery D. Ortuno P. <valerydariana98@gmail.com>
    @created 2026-09-24
    @updated 2026-09-24

    @description
    Sidebar tipo drawer (Flowbite). $items es un arreglo de ítems:
    ['label', 'icon' (chart|cart|kanban|inbox|users|lock|logout),
    'badge', 'count', 'active', 'children' => [[label]]]. $brand y
    $logo son slots opcionales; $showTrigger muestra el botón de apertura.
--}}

@props([
    'id' => 'default-sidebar',
    'brand' => null,
    'active' => null,
    'items' => [],
    'showTrigger' => false,
])

@php
    $icons = [
        'chart'   => '<svg class="w-5 h-5 transition duration-75 group-hover:text-fg-brand" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6.025A7.5 7.5 0 1 0 17.975 14H10V6.025Z"/><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.5 3c-.169 0-.334.014-.5.025V11h7.975c.011-.166.025-.331.025-.5A7.5 7.5 0 0 0 13.5 3Z"/></svg>',
        'cart'    => '<svg class="shrink-0 w-5 h-5 transition duration-75 group-hover:text-fg-brand" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 4h1.5L9 16m0 0h8m-8 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm8 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm-8.5-3h9.25L19 7H7.312"/></svg>',
        'kanban'  => '<svg class="shrink-0 w-5 h-5 transition duration-75 group-hover:text-fg-brand" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v14M9 5v14M4 5h16a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1Z"/></svg>',
        'inbox'   => '<svg class="shrink-0 w-5 h-5 transition duration-75 group-hover:text-fg-brand" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 13h3.439a.991.991 0 0 1 .908.6 3.978 3.978 0 0 0 7.306 0 .99.99 0 0 1 .908-.6H20M4 13v6a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-6M4 13l2-9h12l2 9M9 7h6m-7 3h8"/></svg>',
        'users'   => '<svg class="shrink-0 w-5 h-5 transition duration-75 group-hover:text-fg-brand" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-width="2" d="M16 19h4a1 1 0 0 0 1-1v-1a3 3 0 0 0-3-3h-2m-2.236-4a3 3 0 1 0 0-4M3 18v-1a3 3 0 0 1 3-3h4a3 3 0 0 1 3 3v1a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1Zm8-10a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>',
        'lock'    => '<svg class="shrink-0 w-5 h-5 transition duration-75 group-hover:text-fg-brand" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 10V6a3 3 0 0 1 3-3v0a3 3 0 0 1 3 3v4m3-2 .917 11.923A1 1 0 0 1 17.92 21H6.08a1 1 0 0 1-.997-1.077L6 8h12Z"/></svg>',
        'logout'  => '<svg class="shrink-0 w-5 h-5 transition duration-75 group-hover:text-fg-brand" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12H4m12 0-4 4m4-4-4-4m3-4h2a3 3 0 0 1 3 3v10a3 3 0 0 1-3 3h-2"/></svg>',
        'chevron' => '<svg class="w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 9-7 7-7-7"/></svg>',
        'close'   => '<svg class="w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 17.94 6M18 18 6.06 6"/></svg>',
    ];

    $defaultItems = [
        ['label' => 'Dashboard', 'icon' => 'chart', 'active' => true],
        ['label' => 'E-commerce', 'icon' => 'cart', 'children' => ['Products', 'Billing', 'Invoice']],
        ['label' => 'Kanban', 'icon' => 'kanban', 'badge' => 'Pro'],
        ['label' => 'Inbox', 'icon' => 'inbox', 'count' => 2],
        ['label' => 'Users', 'icon' => 'users'],
        ['label' => 'Products', 'icon' => 'lock'],
        ['label' => 'Sign In', 'icon' => 'logout'],
    ];

    $nav = $items ?: $defaultItems;
@endphp

@if ($showTrigger)
    <button data-drawer-target="{{ $id }}" data-drawer-toggle="{{ $id }}" aria-controls="{{ $id }}" type="button"
            class="text-heading bg-transparent box-border border border-transparent hover:bg-neutral-secondary-medium focus:ring-4 focus:ring-neutral-tertiary font-medium leading-5 rounded-base ms-3 mt-3 text-sm p-2 focus:outline-none inline-flex sm:hidden">
        <span class="sr-only">Open sidebar</span>
        <svg class="w-6 h-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-width="2" d="M5 7h14M5 12h14M5 17h10"/></svg>
    </button>
@endif

<aside id="{{ $id }}" class="fixed top-0 left-0 z-40 w-80 h-full transition-transform -translate-x-full sm:translate-x-0" aria-label="Sidebar">
    <div class="h-full px-3 py-4 overflow-y-auto bg-neutral-primary-soft border-e border-default">
        <a href="#" class="flex items-center ps-2.5 mb-5">
            @if (! empty($logo))
                {{ $logo }}
            @endif
            @if ($brand)
                <span class="self-center text-lg text-heading font-semibold whitespace-nowrap">{{ $brand }}</span>
            @endif
        </a>

        <ul class="space-y-2 font-medium">
            @foreach ($nav as $item)
                <li>
                    @if (! empty($item['children']))
                        <button type="button" data-collapse-toggle="{{ \Illuminate\Support\Str::slug($item['label']) }}-dropdown"
                                class="flex items-center w-full justify-between px-2 py-1.5 text-body rounded-base hover:bg-neutral-tertiary hover:text-fg-brand group"
                                aria-expanded="false">
                            {!! $icons[$item['icon']] ?? '' !!}
                            <span class="flex-1 ms-3 text-left rtl:text-right whitespace-nowrap">{{ $item['label'] }}</span>
                            {!! $icons['chevron'] !!}
                        </button>
                        <ul id="{{ \Illuminate\Support\Str::slug($item['label']) }}-dropdown" class="hidden py-2 space-y-2">
                            @foreach ($item['children'] as $child)
                                <li>
                                    <a href="#" class="pl-10 flex items-center px-2 py-1.5 text-body rounded-base hover:bg-neutral-tertiary hover:text-fg-brand group">{{ $child }}</a>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <a href="#" @class([
                            'flex items-center px-2 py-1.5 rounded-base group',
                            'bg-neutral-tertiary text-fg-brand' => ! empty($item['active']) || $item['label'] === $active,
                            'text-body hover:bg-neutral-tertiary hover:text-fg-brand' => empty($item['active']) && $item['label'] !== $active,
                        ])>
                            {!! $icons[$item['icon']] ?? '' !!}
                            <span class="flex-1 ms-3 whitespace-nowrap">{{ $item['label'] }}</span>

                            @if (! empty($item['badge']))
                                <span class="bg-neutral-secondary-medium border border-default-medium text-heading text-xs font-medium px-1.5 py-0.5 rounded-sm">{{ $item['badge'] }}</span>
                            @endif

                            @if (! empty($item['count']))
                                <span class="inline-flex items-center justify-center w-5 h-5 ms-2 text-xs font-medium text-fg-danger-strong bg-danger-soft border border-danger-subtle rounded-full">{{ $item['count'] }}</span>
                            @endif
                        </a>
                    @endif
                </li>
            @endforeach
        </ul>
    </div>
</aside>