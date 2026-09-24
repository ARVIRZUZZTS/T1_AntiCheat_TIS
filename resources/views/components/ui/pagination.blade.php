{{--
    @file    pagination.blade.php
    @author  OchoaCesar
    @created 2026-09-23
    @updated 2026-09-23

    @description
    Paginación de números con anterior/siguiente. Variantes de tamaño
    default (w-9) y large (w-10). Página activa resaltada con marca.
--}}

@props([
    'current' => 1,
    'total' => 5,
    'size' => 'default',
    'href' => null,
])

@php
    $box = $size === 'large' ? 'w-10 h-10' : 'w-9 h-9';
    $linkClass = 'flex items-center justify-center text-body bg-neutral-secondary-medium box-border border border-default-medium hover:bg-neutral-tertiary-medium hover:text-heading font-medium text-sm ' . $box . ' focus:outline-none';
@endphp

<nav {{ $attributes->merge(['aria-label' => 'Page navigation']) }}>
    <ul class="flex -space-x-px text-sm">
        <li>
            <a href="{{ $href ?? '#' }}" class="{{ $linkClass }} rounded-s-base">
                <span class="sr-only">Previous</span>
                <svg class="w-4 h-4 rtl:rotate-180" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m15 19-7-7 7-7"/></svg>
            </a>
        </li>

        @for ($page = 1; $page <= $total; $page++)
            <li>
                <a href="{{ $href ?? '#' }}"
                   @class([
                       $linkClass,
                       'text-fg-brand bg-neutral-tertiary-medium' => $page === $current,
                   ])
                   aria-current="{{ $page === $current ? 'page' : null }}">
                    {{ $page }}
                </a>
            </li>
        @endfor

        <li>
            <a href="{{ $href ?? '#' }}" class="{{ $linkClass }} rounded-e-base">
                <span class="sr-only">Next</span>
                <svg class="w-4 h-4 rtl:rotate-180" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m9 5 7 7-7 7"/></svg>
            </a>
        </li>
    </ul>
</nav>