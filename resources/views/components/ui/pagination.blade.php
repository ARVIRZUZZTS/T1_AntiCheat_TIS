{{--
    @file    pagination.blade.php
    @author  OchoaCesar
    @created 2026-09-23
    @updated 2026-09-23

    @description
    Paginación de números con anterior/siguiente. Variantes de tamaño
    sm (36px en mobile, 28px desde sm: — pensado para uso táctil), default
    (w-9) y large (w-10). Página activa resaltada con marca. Por defecto
    los controles son enlaces; con $interactive se vuelven botones que
    disparan Livewire (método irPagina(número)).
--}}

@props([
    'current' => 1,
    'total' => 5,
    'size' => 'default',
    'href' => null,
    'interactive' => false,
    'activeFilled' => false,
])

@php
    $box = match ($size) {
        'sm' => 'w-9 h-9 sm:w-7 sm:h-7',
        'large' => 'w-10 h-10',
        default => 'w-9 h-9',
    };
    $btnClass = 'flex items-center justify-center text-body bg-neutral-secondary-medium box-border border border-default-medium hover:bg-neutral-tertiary-medium hover:text-heading font-medium text-sm ' . $box . ' focus:outline-none';
    $activeClass = $activeFilled
        ? 'text-white bg-brand border-brand hover:bg-brand hover:text-white'
        : 'text-fg-brand bg-neutral-tertiary-medium';
    $prev = max(1, $current - 1);
    $next = min($total, $current + 1);
@endphp

<nav {{ $attributes->merge(['aria-label' => 'Page navigation']) }}>
    <ul class="flex -space-x-px text-sm">
        <li>
            @if ($interactive)
                <button type="button" wire:click="irPagina({{ $prev }})" @disabled($current === 1) class="{{ $btnClass }} rounded-s-base">
                    <span class="sr-only">Previous</span>
                    <svg class="w-4 h-4 rtl:rotate-180" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m15 19-7-7 7-7"/></svg>
                </button>
            @else
                <a href="{{ $href ?? '#' }}" class="{{ $btnClass }} rounded-s-base">
                    <span class="sr-only">Previous</span>
                    <svg class="w-4 h-4 rtl:rotate-180" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m15 19-7-7 7-7"/></svg>
                </a>
            @endif
        </li>

        @for ($page = 1; $page <= $total; $page++)
            <li>
                @if ($interactive)
                    <button type="button" wire:click="irPagina({{ $page }})"
                            @class([
                                $btnClass,
                                $activeClass => $page === $current,
                            ])
                            aria-current="{{ $page === $current ? 'page' : null }}">
                        {{ $page }}
                    </button>
                @else
                    <a href="{{ $href ?? '#' }}"
                       @class([
                           $btnClass,
                           $activeClass => $page === $current,
                       ])
                       aria-current="{{ $page === $current ? 'page' : null }}">
                        {{ $page }}
                    </a>
                @endif
            </li>
        @endfor

        <li>
            @if ($interactive)
                <button type="button" wire:click="irPagina({{ $next }})" @disabled($current === $total) class="{{ $btnClass }} rounded-e-base">
                    <span class="sr-only">Next</span>
                    <svg class="w-4 h-4 rtl:rotate-180" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m9 5 7 7-7 7"/></svg>
                </button>
            @else
                <a href="{{ $href ?? '#' }}" class="{{ $btnClass }} rounded-e-base">
                    <span class="sr-only">Next</span>
                    <svg class="w-4 h-4 rtl:rotate-180" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m9 5 7 7-7 7"/></svg>
                </a>
            @endif
        </li>
    </ul>
</nav>