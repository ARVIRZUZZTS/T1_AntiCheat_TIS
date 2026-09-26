{{--
    @file    select.blade.php
    @author  Valery D. Ortuno P. <valerydariana98@gmail.com>
    @created 2026-09-24
    @updated 2026-09-25

    @description
    Select desplegable con etiqueta. $options es un arreglo de
    ['value' => ..., 'label' => ...]; $selected marca la opción activa.
    $error activa el borde de danger y muestra el mensaje bajo el campo.

    @changelog
    - 2026-09-24  [Valery D. Ortuno P]  feat: creación inicial del componente.
    - 2026-09-25  [Valery D. Ortuno P]  feat: agregar estado de error.
--}}

@props([
    'label' => null,
    'name' => null,
    'id' => null,
    'options' => [],
    'selected' => null,
    'placeholder' => null,
    'error' => null,
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

    <select name="{{ $name }}" id="{{ $inputId }}"
            {{ $attributes->merge(['class' => 'block w-full px-3 py-2.5 border text-sm rounded-base shadow-xs placeholder:text-body ' . $stateClass]) }}
            @if ($error) aria-invalid="true" @endif>
        @if ($placeholder)
            <option disabled @if (! $selected) selected @endif>{{ $placeholder }}</option>
        @endif

        @foreach ($options as $value => $label)
            <option value="{{ is_string($value) ? $value : $label }}" @selected((string) $selected === (string) (is_string($value) ? $value : $label))>
                {{ $label }}
            </option>
        @endforeach
    </select>

    @if ($error)
        <p class="mt-1 text-sm text-fg-danger-strong">{{ $error }}</p>
    @endif
</div>
