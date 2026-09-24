{{--
    @file    avatar.blade.php
    @author  Alisson D. Alvarado <alvaradoalissondalet@gmail.com>
    @created 2026-09-24
    @updated 2026-09-24

    @description
    Avatar circular: muestra imagen si se provee $src, en caso contrario
    las iniciales del nombre sobre fondo neutro.
--}}

@props([
    'name' => '',
    'src' => null,
    'size' => 'md',
    'alt' => null,
])

@php
    $sizes = [
        'sm' => 'w-8 h-8 text-xs',
        'md' => 'w-10 h-10 text-sm',
        'lg' => 'w-14 h-14 text-base',
        'xl' => 'w-20 h-20 text-lg',
    ];

    $initials = $name
        ? implode('', array_map(fn ($part) => strtoupper(mb_substr($part, 0, 1)), preg_split('/\s+/', trim($name)) ?: []))
        : '';
@endphp

@if ($src)
    <img {{ $attributes->merge(['class' => 'relative inline-block object-cover rounded-full ' . ($sizes[$size] ?? $sizes['md'])]) }}
         src="{{ $src }}" alt="{{ $alt ?? $name }}" />
@else
    <div {{ $attributes->merge(['class' => 'relative inline-flex items-center justify-center overflow-hidden bg-neutral-tertiary rounded-full font-medium text-body ' . ($sizes[$size] ?? $sizes['md'])]) }}>
        <span>{{ $initials }}</span>
    </div>
@endif