{{--
    @file    calendar.blade.php
    @author  Alex Candia <alex.leonar.candia@gmail.com>
    @created 2026-09-23
    @updated 2026-09-23

    @description
    Selector de fecha (input datepicker de Flowbite) con icono de
    calendario a la izquierda.
--}}

@props([
    'name' => null,
    'id' => null,
    'value' => null,
    'placeholder' => 'Select date',
])

@php
    $inputId = $id ?? $name;
@endphp

<div class="relative">
    <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
        <svg class="w-4 h-4 text-body" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 10h16m-8-3V4M7 7V4m10 3V4M5 20h14a1 1 0 0 0 1-1V7a1 1 0 0 0-1-1H5a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1Zm3-7h.01v.01H8V13Zm4 0h.01v.01H12V13Zm4 0h.01v.01H16V13Zm-8 4h.01v.01H8V17Zm4 0h.01v.01H12V17Zm4 0h.01v.01H16V17Z"/></svg>
    </div>

    <input datepicker id="{{ $inputId }}" name="{{ $name }}" type="text" value="{{ $value }}"
           {{ $attributes->merge(['class' => 'block w-full ps-9 pe-3 py-2.5 bg-neutral-secondary-medium border border-default-medium text-heading text-sm rounded-base focus:ring-brand focus:border-brand shadow-xs placeholder:text-body']) }}
           placeholder="{{ $placeholder }}" />
</div>