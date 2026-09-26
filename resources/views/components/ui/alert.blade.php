{{--
    @file    alert.blade.php
    @author  Alisson D. Alvarado <alvaradoalissondalet@gmail.com>
    @created 2026-09-23
    @updated 2026-09-23

    @description
    Alerta contextual con variantes (brand, success, danger, warning),
    icono de información, título opcional y contenido en slot.
--}}

@props([
    'type' => 'brand',
    'title' => null,
])

@php
    $styles = [
        'brand'   => 'text-fg-brand-strong bg-brand-softer border-brand-subtle',
        'success' => 'text-fg-success-strong bg-success-soft border-success-subtle',
        'danger'  => 'text-fg-danger-strong bg-danger-soft border-danger-subtle',
        'warning' => 'text-fg-warning bg-warning-soft border-warning-subtle',
    ];
@endphp

<div {{ $attributes->merge([
    'class' => 'flex p-4 mb-4 text-sm ' . ($styles[$type] ?? $styles['brand']) . ' rounded-base border',
    'role' => 'alert',
]) }}>
    <svg class="w-4 h-4 me-2 shrink-0" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 11h2v5m-2 0h4m-2.592-8.5h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
    <span class="sr-only">{{ $title ?? ucfirst($type) }}</span>
    <div>
        @if ($title)
            <span class="font-medium">{{ $title }}</span>
        @endif
        {{ $slot }}
    </div>
</div>