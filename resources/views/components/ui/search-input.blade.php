{{--
    @file    search-input.blade.php
    @author  Valery D. Ortuno P. <valerydariana98@gmail.com>
    @created 2026-09-24
    @updated 2026-09-24

    @description
    Campo de búsqueda con icono de lupa y botón opcional. El botón y el
    icono pueden deshabilitarse con las props correspondientes. Si se
    provee $wireSubmit, el formulario se envía vía Livewire al método
    indicado y el botón pasa a ser de tipo submit.
--}}

@props([
    'name' => null,
    'id' => null,
    'placeholder' => 'Search',
    'label' => null,
    'buttonLabel' => 'Search',
    'showButton' => true,
    'showIcon' => true,
    'wireSubmit' => null,
])

@php
    $inputId = $id ?? $name;
@endphp

<form class="max-w-md mx-auto"
      @if ($wireSubmit) wire:submit.prevent="{{ $wireSubmit }}" @endif>
    @if ($label)
        <label for="{{ $inputId }}" class="block mb-2.5 text-sm font-medium text-heading">{{ $label }}</label>
    @endif

    <div class="relative">
        @if ($showIcon)
            <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
                <svg class="w-4 h-4 text-body" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-width="2" d="m21 21-3.5-3.5M17 10a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z"/></svg>
            </div>
        @endif

        <input type="search" name="{{ $name }}" id="{{ $inputId }}"
               {{ $attributes->merge(['class' => 'block w-full p-3 ' . ($showIcon ? 'ps-9' : '') . ' bg-neutral-secondary-medium border border-default-medium text-heading text-sm rounded-base focus:ring-brand focus:border-brand shadow-xs placeholder:text-body']) }}
               placeholder="{{ $placeholder }}" />

        @if ($showButton)
            <button {{ $wireSubmit ? 'type=submit' : 'type=button' }} class="absolute end-1.5 bottom-1.5 text-white bg-brand hover:bg-brand-strong box-border border border-transparent focus:ring-4 focus:ring-brand-medium shadow-xs font-medium leading-5 rounded text-xs px-3 py-1.5 focus:outline-none">
                {{ $buttonLabel }}
            </button>
        @endif
    </div>
</form>