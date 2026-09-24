{{--
    @file    number-input.blade.php
    @author  OchoaCesar
    @created 2026-09-23
    @updated 2026-09-23

    @description
    Campo de entrada numérico con etiqueta (parecido a input pero
    conserva el inputmode/step para cantidades).
--}}

@props([
    'label' => null,
    'name' => null,
    'id' => null,
    'placeholder' => null,
    'value' => null,
    'step' => 'any',
])

@php
    $inputId = $id ?? $name;
@endphp

<div>
    @if ($label)
        <label for="{{ $inputId }}" class="block mb-2.5 text-sm font-medium text-heading">{{ $label }}</label>
    @endif

    <input type="number" name="{{ $name }}" id="{{ $inputId }}" step="{{ $step }}" value="{{ $value }}"
           {{ $attributes->merge(['class' => 'block w-full px-3 py-2.5 bg-neutral-secondary-medium border border-default-medium text-heading text-sm rounded-base focus:ring-brand focus:border-brand shadow-xs placeholder:text-body']) }}
           placeholder="{{ $placeholder }}" />
</div>