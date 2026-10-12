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

    @changelog
    - 2026-09-24  [Alisson D. Alvarado]  feat: creación inicial del componente.
    - 2026-10-12  [Diego Tejerina]  fix: las iniciales se limitan a 2 (primera
      letra del primer y del último nombre/apellido) en vez de una por cada
      palabra; con nombres de 3 o 4 palabras el círculo quedaba con las letras
      amontonadas en vez de las 2 que pide el diseño.
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

    // Siempre 2 iniciales como máximo (primera letra del primer y del último
    // nombre/apellido), nunca una por cada palabra: con nombres compuestos
    // ("Maria Fernanda Gutierrez Soliz") el circulo quedaba con 3 o 4 letras
    // amontonadas en vez de las 2 que el diseño espera.
    $palabras = $name !== '' ? array_values(array_filter(preg_split('/\s+/', trim($name)))) : [];
    $primerasLetras = array_map(fn ($palabra) => strtoupper(mb_substr($palabra, 0, 1)), $palabras);
    $initials = match (count($primerasLetras)) {
        0 => '',
        1 => $primerasLetras[0],
        default => $primerasLetras[0] . end($primerasLetras),
    };
@endphp

@if ($src)
    <img {{ $attributes->merge(['class' => 'relative inline-block object-cover rounded-full ' . ($sizes[$size] ?? $sizes['md'])]) }}
         src="{{ $src }}" alt="{{ $alt ?? $name }}" />
@else
    <div {{ $attributes->merge(['class' => 'relative inline-flex items-center justify-center overflow-hidden rounded-full font-medium ' . ($sizes[$size] ?? $sizes['md']) . ' ' . ($tones[$tone] ?? $tones['default'])]) }}>
        <span>{{ $initials }}</span>
    </div>
@endif