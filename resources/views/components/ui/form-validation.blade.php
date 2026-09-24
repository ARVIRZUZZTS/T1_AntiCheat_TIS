{{--
    @file    form-validation.blade.php
    @author  David E. Chavez T. <virzuzz12345@gmail.com>
    @created 2026-09-23
    @updated 2026-09-23

    @description
    Campo con estado de validación visual: success (confirmación) o
    danger (error), incluyendo mensaje bajo el campo.
--}}

@props([
    'state' => 'success',
    'label' => null,
    'name' => null,
    'id' => null,
    'type' => 'text',
    'placeholder' => null,
    'message' => null,
])

@php
    $inputId = $id ?? $name;
    $isSuccess = $state === 'success';
    $fg = $isSuccess ? 'fg-success-strong' : 'fg-danger-strong';
    $inputClass = ($isSuccess ? 'bg-success-soft border-success-subtle focus:ring-success focus:border-success' : 'bg-danger-soft border-danger-subtle focus:ring-danger focus:border-danger')
        . ' text-' . $fg . ' text-sm rounded-base block w-full px-3 py-2.5 shadow-xs placeholder:text-' . $fg;
@endphp

<div class="mb-6">
    <label for="{{ $inputId }}" class="block mb-2.5 text-sm font-medium text-{{ $fg }}">{{ $label }}</label>

    <input type="{{ $type }}" name="{{ $name }}" id="{{ $inputId }}" class="{{ $inputClass }}"
           placeholder="{{ $placeholder }}" aria-invalid="{{ $isSuccess ? 'false' : 'true' }}" />

    @if ($message)
        <p class="mt-2.5 text-sm text-{{ $fg }}"><span class="font-medium">{{ $isSuccess ? 'Well done!' : 'Oh, snapp!' }}</span> {{ $message }}</p>
    @endif
</div>