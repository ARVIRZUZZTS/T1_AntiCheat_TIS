{{--
    @file    card.blade.php
    @author  Alex Candia <alex.leonar.candia@gmail.com>
    @created 2026-09-23
    @updated 2026-09-23

    @description
    Tarjeta clicable (si se pasa $href) o estática con título y slot
    para el cuerpo. Usa superficie de tarjeta y borde de la paleta.

    @changelog
    - 2026-09-23  [Alex Candia]  feat: creación inicial del componente.
    - 2026-10-12  [Diego Tejerina]  fix: el hover de fondo quedaba puesto
      siempre, también sin $href (una `<div>` estática, no clicable) — por
      ejemplo la card de "Próximos exámenes" en /inicio, que no lleva a
      ningún lado pero se pintaba como si fuera clicable al pasar el mouse.
      Ahora el hover solo se aplica cuando la card es un link de verdad.
--}}

@props(['href' => null, 'title' => null])

@php
    $classes = 'bg-neutral-primary-soft block max-w-sm p-6 border border-default rounded-base shadow-xs'
        . ($href ? ' hover:bg-neutral-secondary-medium' : '');
@endphp

@if ($href)
    <a {{ $attributes->merge(['href' => $href, 'class' => $classes]) }}>
@else
    <div {{ $attributes->merge(['class' => $classes]) }}>
@endif
    @if ($title)
        <h5 class="mb-3 text-2xl font-semibold tracking-tight leading-8 text-heading">{{ $title }}</h5>
    @endif
    @if ($slot->isNotEmpty())
        <div class="text-body">
            {{ $slot }}
        </div>
    @endif
@if ($href)
    </a>
@else
    </div>
@endif