{{--
    @file    popover.blade.php
    @author  OchoaCesar
    @created 2026-09-23
    @updated 2026-09-23

    @description
    Popover de ayuda (Flowbite data-popover). $sections es un arreglo de
    ['heading' => ..., 'body' => ...]. El texto que dispara el popover
    se pasa en $slot de $triggerText.
--}}

@props([
    'id' => null,
    'triggerText' => null,
    'readMoreLabel' => null,
    'readMoreHref' => '#',
    'sections' => [],
])

@php
    $id = $id ?: 'popover-' . \Illuminate\Support\Str::random(6);
@endphp

<p class="flex items-center text-sm text-body">
    {{ $triggerText }}

    <button data-popover-target="{{ $id }}" data-popover-placement="bottom-end" type="button">
        <svg class="w-4 h-4 text-body hover:text-heading ms-2" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.529 9.988a2.502 2.502 0 1 1 5 .191A2.441 2.441 0 0 1 12 12.582V14m-.01 3.008H12M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
        <span class="sr-only">Show information</span>
    </button>
</p>

<div data-popover id="{{ $id }}" role="tooltip" class="absolute z-10 p-3 invisible inline-block text-sm text-body transition-opacity duration-300 bg-neutral-primary-soft border border-default rounded-base shadow-xs opacity-0 w-72">
    <div>
        @foreach ($sections as $section)
            @if (! empty($section['heading']))
                <h3 class="font-semibold text-heading mb-2">{{ $section['heading'] }}</h3>
            @endif
            <p @class(['mb-4' => ! $loop->last])>{{ $section['body'] ?? $section }}</p>
        @endforeach

        @if ($readMoreLabel)
            <a href="{{ $readMoreHref }}" class="flex items-center font-medium text-fg-brand hover:underline">
                {{ $readMoreLabel }}
                <svg class="w-4 h-4 ms-1 rtl:rotate-180" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 12H5m14 0-4 4m4-4-4-4"/></svg>
            </a>
        @endif
    </div>
    <div data-popper-arrow></div>
</div>