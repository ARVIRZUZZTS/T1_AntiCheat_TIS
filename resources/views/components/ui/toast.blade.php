{{--
    @file    toast.blade.php
    @author  David E. Chavez T. <virzuzz12345@gmail.com>
    @created 2026-09-23
    @updated 2026-09-23

    @description
    Notificación toast (success | danger | warning) con icono, mensaje
    y botón de cierre desmontable (data-dismiss-target).
--}}

@props([
    'id' => null,
    'variant' => 'success',
    'title' => null,
])

@php
    $id = $id ?: 'toast-' . \Illuminate\Support\Str::random(8);

    $styles = [
        'success' => ['icon' => 'text-fg-success bg-success-soft', 'check' => 'M5 11.917 9.724 16.5 19 7.5'],
        'danger'  => ['icon' => 'text-fg-danger bg-danger-soft', 'check' => 'M6 18 17.94 6M18 18 6.06 6'],
        'warning' => ['icon' => 'text-fg-warning bg-warning-soft', 'check' => 'M12 13V8m0 8h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z'],
    ];
    $style = $styles[$variant] ?? $styles['success'];
@endphp

<div id="{{ $id }}" class="flex items-center w-full max-w-sm p-4 text-body bg-neutral-primary-soft rounded-base shadow-xs border border-default" role="alert">
    <div class="inline-flex items-center justify-center shrink-0 w-8 h-8 rounded {{ $style['icon'] }}">
        <svg class="w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $style['check'] }}"/></svg>
        <span class="sr-only">{{ ucfirst($variant) }} icon</span>
    </div>

    <div class="ms-3 text-sm font-normal">
        @if ($title)
            <p class="font-medium text-heading">{{ $title }}</p>
        @endif
        {{ $slot }}
    </div>

    <button type="button" data-dismiss-target="#{{ $id }}" aria-label="Close"
            class="ms-auto flex items-center justify-center text-body hover:text-heading bg-transparent box-border border border-transparent hover:bg-neutral-secondary-medium focus:ring-4 focus:ring-neutral-tertiary font-medium leading-5 rounded text-sm h-8 w-8 focus:outline-none">
        <span class="sr-only">Close</span>
        <svg class="w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 17.94 6M18 18 6.06 6"/></svg>
    </button>
</div>