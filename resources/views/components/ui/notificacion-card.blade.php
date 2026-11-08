{{--
    @file    notificacion-card.blade.php
    @author  Alisson D. Alvarado <alvaradoalissondalet@gmail.com>
    @created 2026-10-09
    @updated 2026-10-11

    @description
    Tarjeta presentacional de una notificación del docente, responsive
    mobile-first. Muestra la descripción y la orientación correspondiente al
    tipo de aviso sin ofrecer acciones sobre la notificación.

    @changelog
    - 2026-10-09  [Alisson D. Alvarado]  feat: creación inicial del componente.
    - 2026-10-10  feat: integración de la tarjeta de la HU 12 (T3/T4) sobre la
      base de dev (descripción, layout responsive y acciones).
    - 2026-10-11  [OchoaCesar]  feat: "Ver detalle" despacha el evento window
      'abrir-detalle-incidencia' para abrir el modal del detalle en vez de
      navegar (se conserva el href como respaldo sin JS).
    - 2026-10-11  [OchoaCesar]  fix: el id de la incidencia se lee de
      `data-incidencia` y no del onclick incrustado, para que el script de la
      página (que clona tarjetas desde un template estático) pueda
      reemplazarlo con el `referencia` de cada aviso.
    - 2026-11-08  [Alisson D. Alvarado]  fix: dejar las tarjetas informativas.
--}}

@props([
    'id' => 0,
    'tipo' => 'posible_tramposo',
    'titulo' => null,
    'auxiliar' => '',
    'estudiante' => '',
    'examen' => '',
    'materia' => '',
    'hace' => '',
    'detalleHref' => '#',
    'descripcion' => null,
])

@php
    $esCentral = $tipo === 'central_riesgo';

    $titulo ??= $esCentral
        ? 'Estudiante ingresado a central de riesgo'
        : 'Posible Tramposo';

    $descripcion ??= $esCentral
        ? trim(sprintf(
            'El estudiante %s fue ingresado a la central de riesgo en la materia de %s',
            $estudiante,
            $materia
        ))
        : trim(sprintf(
            'El auxiliar %s registró al estudiante %s como posible tramposo en el examen de %s de la materia %s.',
            $auxiliar,
            $estudiante,
            $examen,
            $materia
        ));
@endphp

<div {{ $attributes->merge(['class' => 'js-notificacion relative flex items-start gap-3 sm:gap-4 rounded-2xl border border-light bg-neutral-primary-soft p-4 sm:p-5 shadow-xs']) }}>
    @if ($esCentral)
        <span class="shrink-0 inline-flex items-center justify-center w-11 h-11 bg-danger-soft rounded-xl">
            <svg class="w-6 h-6 text-danger" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2"/><path stroke="currentColor" stroke-linecap="round" stroke-width="2" d="M5.6 5.6l12.8 12.8"/></svg>
        </span>
    @else
        <span class="shrink-0 inline-flex items-center justify-center w-11 h-11 bg-warning-soft rounded-xl">
            <svg class="w-6 h-6 text-warning" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0ZM12 9v4m0 4h.01"/></svg>
        </span>
    @endif

    <div class="flex-1 min-w-0">
        <h3 class="text-sm font-bold leading-snug text-heading break-words">{{ $titulo }}</h3>
        <p class="js-descripcion mt-1 text-sm text-body break-words">{{ $descripcion }}</p>
        <p class="mt-2 text-sm text-body">
            {{ $esCentral
                ? 'Dirigete a la central de riesgos para ver el detalle completo'
                : 'Dirigete a la central de riesgos para aceptar o rechazar a este estudiante' }}
        </p>
        <span class="js-hace mt-3 block text-xs text-muted">{{ $hace }}</span>
    </div>
</div>
