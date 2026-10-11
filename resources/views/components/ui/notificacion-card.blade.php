{{--
    @file    notificacion-card.blade.php
    @author  Alisson D. Alvarado <alvaradoalissondalet@gmail.com>
    @created 2026-10-09
    @updated 2026-10-10

    @description
    Tarjeta presentacional de una notificación de "Posible Tramposo", responsive
    mobile-first. Conserva la API de props de dev (`id`, `titulo`, `auxiliar`,
    `estudiante`, `examen`, `materia`, `hace`, `detalleHref`) para no romper a
    quien ya la usa, y suma los ajustes de la HU 12 (T3/T4):

      - descripción en forma de oración con auxiliar, estudiante, examen y materia;
      - tiempo abajo a la izquierda y "Ver detalle" abajo a la derecha, tanto en
        móvil como en escritorio (un único `flex justify-between`);
      - título/texto con `break-words` y espacio reservado a la derecha para que
        el título pueda partirse en varias líneas sin pisar el texto ni la X;
      - botón "Ver detalle" en rojo (danger) y X gris arriba a la derecha.

    La X es un descarte visual: el handler de la página elimina el nodo con
    clase `js-notificacion`. NO cambia el estado del registro (sigue "En
    revisión").

    @changelog
    - 2026-10-09  [Alisson D. Alvarado]  feat: creación inicial del componente.
    - 2026-10-10  feat: integración de la tarjeta de la HU 12 (T3/T4) sobre la
      base de dev (descripción, layout responsive y acciones).
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
        'El auxiliar %s registró al estudiante %s como posible tramposo en el examen de %s de la materia %s.',
        $auxiliar,
        $estudiante,
        $examen,
        $materia
    ));
@endphp

<div {{ $attributes->merge(['class' => 'js-notificacion relative flex items-start gap-3 sm:gap-4 rounded-2xl border border-light bg-neutral-primary-soft p-4 sm:p-5 shadow-xs']) }}>
    <span class="shrink-0 inline-flex items-center justify-center w-11 h-11 bg-warning-soft rounded-xl">
        <svg class="w-6 h-6 text-warning" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0ZM12 9v4m0 4h.01"/></svg>
    </span>

    <div class="flex-1 min-w-0 pe-8">
        <h3 class="text-sm font-bold leading-snug text-heading break-words">{{ $titulo }}</h3>
        <p class="mt-1 text-sm text-body break-words">{{ $descripcion }}</p>

        <div class="mt-3 flex items-center justify-between gap-3">
            <span class="text-xs text-muted">{{ $hace }}</span>

            {{-- El botón se declara aquí (no usa x-ui.button a propósito) porque la
                 especificación pide texto semibold y el botón base del sistema va en
                 font-medium; se usa la variante roja pedida por la HU 12. --}}
            <a href="{{ $detalleHref }}"
               class="shrink-0 inline-flex items-center justify-center px-4 py-2 text-sm font-semibold text-white bg-danger rounded-base hover:bg-danger-strong focus:ring-4 focus:ring-danger-medium focus:outline-none">
                Ver detalle
            </a>
        </div>
    </div>

    <button type="button" aria-label="Cerrar notificación" title="Cerrar"
            class="js-cerrar-notificacion absolute end-2 top-2 inline-flex items-center justify-center w-8 h-8 bg-transparent rounded-base text-neutral-tertiary-medium hover:bg-neutral-tertiary-soft hover:text-heading focus:outline-none focus:ring-4 focus:ring-neutral-tertiary-soft">
        <svg class="w-4 h-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6 18 17.94 6M18 18 6.06 6"/></svg>
    </button>
</div>
