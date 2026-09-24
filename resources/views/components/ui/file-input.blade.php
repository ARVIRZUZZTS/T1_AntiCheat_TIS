{{--
    @file    file-input.blade.php
    @author  Candy C. Ordoñez P. <camitkdos@gmail.com>
    @created 2026-09-23
    @updated 2026-09-23

    @description
    Carga de archivos con etiqueta y texto de ayuda debajo.
--}}

@props([
    'label' => null,
    'name' => null,
    'id' => null,
])

@php
    $inputId = $id ?? $name;
@endphp

<div>
    @if ($label)
        <label class="block mb-2.5 text-sm font-medium text-heading" for="{{ $inputId }}">{{ $label }}</label>
    @endif

    <input name="{{ $name }}" id="{{ $inputId }}" type="file"
           {{ $attributes->merge(['class' => 'cursor-pointer bg-neutral-secondary-medium border border-default-medium text-heading text-sm rounded-base focus:ring-brand focus:border-brand block w-full shadow-xs placeholder:text-body']) }} />

    @if ($slot->isNotEmpty())
        <p class="mt-1 text-sm text-muted">{{ $slot }}</p>
    @endif
</div>