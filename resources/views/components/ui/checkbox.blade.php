{{--
    @file    checkbox.blade.php
    @author  Alex Candia <alex.leonar.candia@gmail.com>
    @created 2026-09-23
    @updated 2026-09-23

    @description
    Casilla de verificación con etiqueta, estado marcado/deshabilitado
    y texto de ayuda opcional (slot).
--}}

@props([
    'label' => null,
    'name' => null,
    'id' => null,
    'checked' => false,
    'disabled' => false,
])

@php
    $inputId = $id ?? $name;
    $stateClass = $disabled
        ? 'border-light'
        : 'border-default-medium focus:ring-brand-soft';
@endphp

<div {{ $attributes->merge(['class' => 'flex items-center mb-4']) }}>
    <input id="{{ $inputId }}" type="checkbox" name="{{ $name }}" value=""
           class="w-4 h-4 rounded-xs bg-neutral-secondary-medium focus:ring-2 {{ $stateClass }}"
           @checked($checked) @disabled($disabled) />

    <label for="{{ $inputId }}" @class([
        'ms-2 text-sm font-medium select-none',
        'text-fg-disabled' => $disabled,
        'text-heading' => ! $disabled,
    ])>
        {{ $label }}
        {{ $slot }}
    </label>
</div>