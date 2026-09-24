{{--
    @file    input.blade.php
    @author  Candy C. Ordoñez P. <camitkdos@gmail.com>
    @created 2026-09-23
    @updated 2026-09-23

    @description
    Campo de texto con etiqueta, placeholder, estado de error (borde
    danger) y texto de ayuda opcional.
--}}

@props([
    'label' => null,
    'name' => null,
    'id' => null,
    'type' => 'text',
    'placeholder' => null,
    'value' => null,
    'error' => null,
    'helper' => null,
])

@php
    $inputId = $id ?? $name;
    $stateClass = $error
        ? 'bg-danger-soft border-danger-subtle text-fg-danger-strong focus:ring-danger focus:border-danger'
        : 'bg-neutral-secondary-medium border-default-medium text-heading focus:ring-brand focus:border-brand';
@endphp

<div>
    @if ($label)
        <label for="{{ $inputId }}" class="block mb-2.5 text-sm font-medium text-heading">{{ $label }}</label>
    @endif

    <input type="{{ $type }}" name="{{ $name }}" id="{{ $inputId }}"
           {{ $attributes->merge(['class' => 'block w-full px-3 py-2.5 text-sm rounded-base shadow-xs placeholder:text-body ' . $stateClass]) }}
           placeholder="{{ $placeholder }}"
           value="{{ $value }}"
           @if ($error) aria-invalid="true" @endif />

    @if ($helper && ! $error)
        <p class="mt-1 text-sm text-muted">{{ $helper }}</p>
    @endif

    @if ($error)
        <p class="mt-1 text-sm text-fg-danger-strong">{{ $error }}</p>
    @endif
</div>