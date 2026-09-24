{{--
    @file    header-burger.blade.php
    @author  David E. Chavez T. <virzuzz12345@gmail.com>
    @created 2026-09-23
    @updated 2026-09-23

    @description
    Cabecera superior fija con menú hamburguesa colapsable (Flowbite).
    $items es un arreglo de ['label', 'href', 'active']; $slot permite
    inyectar contenido junto al logo (visible en md+).
--}}

@props([
    'id' => 'header-burger',
    'brand' => null,
    'href' => '#',
    'items' => [],
])

@php
    $defaultItems = [
        ['label' => 'Home', 'active' => true],
        ['label' => 'Services'],
        ['label' => 'Pricing'],
        ['label' => 'Contact'],
    ];
    $nav = $items ?: $defaultItems;
@endphp

<nav class="bg-neutral-secondary-soft fixed w-full z-20 top-0 start-0 border-b border-default">
    <div class="max-w-screen-xl flex flex-wrap items-center justify-between mx-auto p-4">
        <a href="{{ $href }}" class="flex items-center space-x-3 rtl:space-x-reverse">
            @if (! empty($logo))
                {{ $logo }}
            @endif
            @if ($brand)
                <span class="self-center text-xl text-heading font-semibold whitespace-nowrap">{{ $brand }}</span>
            @endif
        </a>

        <button data-collapse-toggle="{{ $id }}" type="button"
                class="inline-flex items-center p-2 w-10 h-10 justify-center text-sm text-body rounded-base hover:bg-neutral-tertiary hover:text-heading focus:outline-none focus:ring-2 focus:ring-neutral-tertiary"
                aria-controls="{{ $id }}" aria-expanded="false">
            <span class="sr-only">Open main menu</span>
            <svg class="w-6 h-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-width="2" d="M5 7h14M5 12h14M5 17h14"/></svg>
        </button>

        <div class="hidden w-full" id="{{ $id }}">
            <ul class="flex flex-col font-medium mt-4 pt-4 bg-neutral-secondary-soft space-y-2 border-t border-default">
                @foreach ($nav as $item)
                    <li>
                        <a href="{{ $item['href'] ?? '#' }}"
                           @class([
                               'block py-2 px-3 rounded',
                               'text-white bg-brand md:bg-transparent md:text-fg-brand md:p-0' => ! empty($item['active']),
                               'text-heading hover:bg-neutral-tertiary md:hover:bg-transparent md:border-0 md:hover:text-fg-brand md:p-0' => empty($item['active']),
                           ])
                           @if (! empty($item['active'])) aria-current="page" @endif>
                            {{ $item['label'] }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
</nav>