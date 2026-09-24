{{--
    @file    tabs.blade.php
    @author  David E. Chavez T. <virzuzz12345@gmail.com>
    @created 2026-09-23
    @updated 2026-09-23

    @description
    Pestañas. $items es un arreglo de
    ['label' => ..., 'href' => '#', 'active' => bool, 'disabled' => bool].
--}}

@props(['items' => []])

<ul class="flex flex-wrap text-sm font-medium text-center text-body">
    @foreach ($items as $item)
        <li @class(['me-2' => ! $loop->last])>
            @if (! empty($item['disabled']))
                <a class="inline-block px-4 py-3 text-fg-disabled cursor-not-allowed">{{ $item['label'] }}</a>
            @elseif (! empty($item['active']))
                <a href="{{ $item['href'] ?? '#' }}" class="inline-block px-4 py-2.5 text-white bg-brand rounded-base active" aria-current="page">{{ $item['label'] }}</a>
            @else
                <a href="{{ $item['href'] ?? '#' }}" class="inline-block px-4 py-3 rounded-base hover:text-heading hover:bg-neutral-secondary-soft">{{ $item['label'] }}</a>
            @endif
        </li>
    @endforeach
</ul>