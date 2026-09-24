{{--
    @file    textarea.blade.php
    @author  David E. Chavez T. <virzuzz12345@gmail.com>
    @created 2026-09-23
    @updated 2026-09-23

    @description
    Área de texto multilínea con etiqueta y opción de estado de error.
--}}

@props([
    'label' => null,
    'name' => null,
    'id' => null,
    'rows' => 4,
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

    <textarea name="{{ $name }}" id="{{ $inputId }}" rows="{{ $rows }}"
              {{ $attributes->merge(['class' => 'block w-full p-3.5 text-sm rounded-base shadow-xs placeholder:text-body ' . $stateClass]) }}
              placeholder="{{ $placeholder }}"
              @if ($error) aria-invalid="true" @endif>{{ $slot }}</textarea>

    @if ($error)
        <p class="mt-1 text-sm text-fg-danger-strong">{{ $error }}</p>
    @endif
</div>