{{--
    @file    button.blade.php
    @author  Alisson D. Alvarado <alvaradoalissondalet@gmail.com>
    @created 2026-09-23
    @updated 2026-09-23

    @description
    Botón con variantes (default, secondary, tertiary, success, danger,
    warning, dark, ghost), tamaños, estado deshabilitado y carga (loader).
    Si se pasa $href y no está deshabilitado, renderiza un <a>.
--}}

@props([
    'variant' => 'default',
    'size' => 'md',
    'type' => 'button',
    'href' => null,
    'loading' => false,
    'disabled' => false,
])

@php
    $variants = [
        'default'   => 'text-white bg-brand border-transparent hover:bg-brand-strong focus:ring-brand-medium',
        'secondary' => 'text-body bg-neutral-secondary-medium border-default-medium hover:bg-neutral-tertiary-medium hover:text-heading focus:ring-neutral-tertiary',
        'tertiary'  => 'text-body bg-neutral-primary-soft border-default hover:bg-neutral-secondary-medium hover:text-heading focus:ring-neutral-tertiary-soft',
        'success'   => 'text-white bg-success border-transparent hover:bg-success-strong focus:ring-success-medium',
        'danger'    => 'text-white bg-danger border-transparent hover:bg-danger-strong focus:ring-danger-medium',
        'warning'   => 'text-white bg-warning border-transparent hover:bg-warning-strong focus:ring-warning-medium',
        'dark'      => 'text-white bg-dark border-transparent hover:bg-dark-strong focus:ring-neutral-tertiary',
        'ghost'     => 'text-heading bg-transparent border-transparent hover:bg-neutral-secondary-medium focus:ring-neutral-tertiary',
    ];

    $sizes = [
        'sm' => 'px-3 py-1.5 text-xs',
        'md' => 'px-4 py-2.5 text-sm',
        'lg' => 'px-5 py-3 text-base',
    ];

    $state = ($disabled || $loading)
        ? 'text-fg-disabled bg-disabled border-default-medium cursor-not-allowed'
        : ($variants[$variant] ?? $variants['default']);

    $classes = 'inline-flex items-center justify-center box-border border focus:ring-4 shadow-xs font-medium leading-5 rounded-base focus:outline-none '
        . $state . ' ' . ($sizes[$size] ?? $sizes['md']);
@endphp

@if ($href && ! $disabled && ! $loading)
    <a {{ $attributes->merge(['href' => $href, 'class' => $classes]) }}>
        {{ $slot }}
    </a>
@else
    <button {{ $attributes->merge(['type' => $type, 'disabled' => $disabled || $loading, 'class' => $classes]) }}>
        @if ($loading)
            <svg aria-hidden="true" role="status" class="w-4 h-4 me-2 text-current animate-spin" viewBox="0 0 100 101" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M100 50.5908C100 78.2051 77.6142 100.591 50 100.591C22.3858 100.591 0 78.2051 0 50.5908C0 22.9766 22.3858 0.59082 50 0.59082C77.6142 0.59082 100 22.9766 100 50.5908ZM9.08144 50.5908C9.08144 73.1895 27.4013 91.5094 50 91.5094C72.5987 91.5094 90.9186 73.1895 90.9186 50.5908C90.9186 27.9921 72.5987 9.67226 50 9.67226C27.4013 9.67226 9.08144 27.9921 9.08144 50.5908Z" fill="#E5E7EB"/>
                <path d="M93.9676 39.0409C96.393 38.4038 97.8624 35.9116 97.0079 33.5539C95.2932 28.8227 92.871 24.3692 89.8167 20.348C85.8452 15.1192 80.8826 10.7238 75.2124 7.41289C69.5422 4.10194 63.2754 1.94025 56.7698 1.05124C51.7666 0.367541 46.6976 0.446843 41.7345 1.27873C39.2613 1.69328 37.813 4.19778 38.4501 6.62326C39.0873 9.04874 41.5694 10.4717 44.0505 10.1071C47.8511 9.54855 51.7191 9.52689 55.5402 10.0491C60.8642 10.7766 65.9928 12.5457 70.6331 15.2552C75.2735 17.9648 79.3347 21.5619 82.5849 25.841C84.9175 28.9121 86.7997 32.2913 88.1811 35.8758C89.083 38.2158 91.5421 39.6781 93.9676 39.0409Z" fill="currentColor"/>
            </svg>
            Loading...
        @else
            {{ $slot }}
        @endif
    </button>
@endif