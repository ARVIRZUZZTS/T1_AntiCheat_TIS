{{--
    @file    card.blade.php
    @author  Alex Candia <alex.leonar.candia@gmail.com>
    @created 2026-09-23
    @updated 2026-09-23

    @description
    Tarjeta clicable (si se pasa $href) o estática con título y slot
    para el cuerpo. Usa superficie de tarjeta y borde de la paleta.
--}}

@props(['href' => null, 'title' => null])

@php
    $classes = 'bg-neutral-primary-soft block max-w-sm p-6 border border-default rounded-base shadow-xs hover:bg-neutral-secondary-medium';
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