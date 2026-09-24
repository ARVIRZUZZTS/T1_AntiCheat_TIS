{{--
    @file    select.blade.php
    @author  Valery D. Ortuno P. <valerydariana98@gmail.com>
    @created 2026-09-24
    @updated 2026-09-24

    @description
    Select desplegable con etiqueta. $options es un arreglo de
    ['value' => ..., 'label' => ...]; $selected marca la opción activa.
--}}

@props([
    'label' => null,
    'name' => null,
    'id' => null,
    'options' => [],
    'selected' => null,
    'placeholder' => null,
])

@php
    $inputId = $id ?? $name;
@endphp

<div>
    @if ($label)
        <label for="{{ $inputId }}" class="block mb-2.5 text-sm font-medium text-heading">{{ $label }}</label>
    @endif

    <select name="{{ $name }}" id="{{ $inputId }}"
            {{ $attributes->merge(['class' => 'block w-full px-3 py-2.5 bg-neutral-secondary-medium border border-default-medium text-heading text-sm rounded-base focus:ring-brand focus:border-brand shadow-xs placeholder:text-body']) }}>
        @if ($placeholder)
            <option disabled @if (! $selected) selected @endif>{{ $placeholder }}</option>
        @endif

        @foreach ($options as $value => $label)
            <option value="{{ is_string($value) ? $value : $label }}" @selected((string) $selected === (string) (is_string($value) ? $value : $label))>
                {{ $label }}
            </option>
        @endforeach
    </select>
</div>