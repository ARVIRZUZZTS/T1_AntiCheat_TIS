{{--
    @file    avatar.blade.php
    @author  Alisson D. Alvarado <alvaradoalissondalet@gmail.com>
    @created 2026-09-24
    @updated 2026-09-24

    @description
    Avatar circular: muestra imagen si se provee $src, en caso contrario
    las iniciales del nombre sobre fondo neutro. $tone permite recolorear
    el fondo (default, sospechoso, tramposo) reutilizando los mismos
    tokens de estado que x-ui.badge.
--}}

@props([
    'name' => '',
    'src' => null,
    'size' => 'md',
    'alt' => null,
    'tone' => 'default',
])

@php
    $sizes = [
        'sm' => 'w-8 h-8 text-xs',
        'md' => 'w-10 h-10 text-sm',
        'lg' => 'w-14 h-14 text-base',
        'xl' => 'w-20 h-20 text-lg',
    ];

    $tones = [
        'default' => 'bg-neutral-tertiary text-body',
        'sospechoso' => 'bg-status-en-revision-bg text-status-en-revision-fg',
        'tramposo' => 'bg-status-central-riesgos-bg text-status-central-riesgos-fg',
    ];

    $initials = $name
        ? implode('', array_map(fn ($part) => strtoupper(mb_substr($part, 0, 1)), preg_split('/\s+/', trim($name)) ?: []))
        : '';
@endphp

@if ($src)
    <img {{ $attributes->merge(['class' => 'relative inline-block object-cover rounded-full ' . ($sizes[$size] ?? $sizes['md'])]) }}
         src="{{ $src }}" alt="{{ $alt ?? $name }}" />
@else
    <div {{ $attributes->merge(['class' => 'relative inline-flex items-center justify-center overflow-hidden rounded-full font-medium ' . ($sizes[$size] ?? $sizes['md']) . ' ' . ($tones[$tone] ?? $tones['default'])]) }}>
        <span>{{ $initials }}</span>
    </div>
@endif