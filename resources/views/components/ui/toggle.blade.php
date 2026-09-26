{{--
    @file    toggle.blade.php
    @author  David E. Chavez T. <virzuzz12345@gmail.com>
    @created 2026-09-23
    @updated 2026-09-23

    @description
    Interruptor (toggle switch). El estado activo se pinta con marca vía
    peer-checked; $checked solo define el valor inicial.
--}}

@props([
    'label' => null,
    'name' => null,
    'id' => null,
    'checked' => false,
])

@php
    $inputId = $id ?? $name;
@endphp

<label {{ $attributes->merge(['class' => 'inline-flex items-center cursor-pointer']) }}>
    <input type="checkbox" name="{{ $name }}" id="{{ $inputId }}" value="" class="sr-only peer" @checked($checked) />

    <div class="relative w-9 h-5 bg-neutral-quaternary peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-brand-soft rounded-full peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-buffer after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-brand"></div>

    <span class="ms-3 text-sm font-medium text-heading select-none">{{ $label }}</span>
</label>