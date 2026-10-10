{{--
    @file    notificacion-card.blade.php
    @author  Alisson D. Alvarado <alvaradoalissondalet@gmail.com>
    @created 2026-10-09
    @updated 2026-10-09

    @description
    Tarjeta presentacional de una notificación de posible tramposo, con diseño
    responsive mobile-first. El contenedor es un `flex` horizontal centrado
    verticalmente con tres zonas:

    @changelog
    - 2026-10-09  [Alisson D. Alvarado]  feat: creación inicial del componente.
--}}

@props([
    'id' => 0,
    'titulo' => 'Posible Tramposo',
    'auxiliar' => '',
    'estudiante' => '',
    'examen' => '',
    'materia' => '',
    'hace' => '',
    'detalleHref' => '#',
])

@php
    $descripcion = trim(sprintf(
        '%s · %s · %s · reportado por %s',
        $estudiante,
        $examen,
        $materia,
        $auxiliar
    ));
@endphp

<div {{ $attributes->merge(['class' => 'flex items-center w-full gap-4 p-5 bg-neutral-primary-soft border border-light rounded-base shadow-xs']) }}>
    <span class="shrink-0 inline-flex items-center justify-center w-11 h-11 bg-warning-soft rounded-base">
        <svg class="w-6 h-6 text-warning" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0ZM12 9v4m0 4h.01"/></svg>
    </span>

    <div class="flex-1 min-w-0">
        <h3 class="text-sm font-bold text-heading">{{ $titulo }}</h3>
        <p class="mt-1 text-sm text-body">{{ $descripcion }}</p>
        <span class="mt-1 block text-xs text-muted">{{ $hace }}</span>
    </div>

    <div class="shrink-0 self-stretch flex flex-col items-end justify-between gap-4">
        <button type="button" aria-label="Cerrar notificación" title="Cerrar"
                class="js-cerrar-notificacion inline-flex items-center justify-center w-8 h-8 bg-transparent rounded-base text-neutral-tertiary-medium hover:text-heading focus:outline-none focus:ring-4 focus:ring-neutral-tertiary-soft">
            <svg class="w-4 h-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6 18 17.94 6M18 18 6.06 6"/></svg>
        </button>

        {{-- El botón se declara aquí (no usa x-ui.button a propósito) porque la
             especificación pide texto semibold y el botón base del sistema va en
             font-medium; se respeta la paleta azul del sistema (brand/hover). --}}
        <a href="{{ $detalleHref }}"
           class="inline-flex items-center justify-center px-4 py-2 text-sm font-semibold text-white bg-brand rounded-base hover:bg-brand-strong focus:ring-4 focus:ring-brand-medium focus:outline-none">
            Ver detalle
        </a>
    </div>
</div>