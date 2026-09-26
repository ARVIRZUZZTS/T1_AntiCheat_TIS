{{--
    @file    time-picker.blade.php
    @author  David E. Chavez T. <virzuzz12345@gmail.com>
    @created 2026-09-23
    @updated 2026-09-23

    @description
    Selector de hora tipo input[type=time] con icono de reloj. El icono
    puede deshabilitarse con $showIcon.
--}}

@props([
    'label' => null,
    'name' => null,
    'id' => null,
    'value' => '00:00',
    'min' => '09:00',
    'max' => '18:00',
    'required' => false,
    'showIcon' => true,
])

@php
    $inputId = $id ?? $name;
@endphp

<div>
    @if ($label)
        <label for="{{ $inputId }}" class="block mb-2 text-sm font-medium text-heading">{{ $label }}</label>
    @endif

    <div class="relative">
        @if ($showIcon)
            <div class="absolute inset-y-0 end-0 top-0 flex items-center pe-3.5 pointer-events-none">
                <svg class="w-4 h-4 text-body" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
            </div>
        @endif

        <input type="time" name="{{ $name }}" id="{{ $inputId }}" value="{{ $value }}" min="{{ $min }}" max="{{ $max }}"
               {{ $attributes->merge(['class' => 'block w-full px-3 py-2.5 bg-neutral-secondary-medium border border-default-medium text-heading text-sm rounded-base focus:ring-brand focus:border-brand shadow-xs placeholder:text-body']) }}
               @if ($required) required @endif />
    </div>
</div>