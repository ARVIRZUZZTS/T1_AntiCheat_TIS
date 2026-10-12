{{--
    @file    stat-card.blade.php
    @author  Diego Tejerina <josediegotejerinamolina@gmail.com>
    @created 2026-10-12

    @description
    Tarjeta de métrica (número grande + etiqueta + ícono), usada en las
    cards de conteo de /materias/{curso} y /monitoreo. Antes cada vista
    tenía su propia copia de este markup, con el mismo bug en las dos: el
    recuadro del ícono (`w-12 h-12`) no tenía `shrink-0`, así que cuando el
    texto era largo ("Deshabilitados", o el horario "08:00 – 09:30" de la
    card "En curso") el ícono se achicaba y se aplastaba en vez de quedarse
    cuadrado.

    $icon es el slot con el SVG del ícono; es OPCIONAL (si no se pasa, no se
    reserva el recuadro). $labelFirst invierte el orden (etiqueta arriba,
    valor abajo) para la card "En curso", que muestra el horario como valor;
    las demás muestran el número primero. $nowrap evita que el valor se
    parta en dos líneas.

    @changelog
    - 2026-10-12  [Diego Tejerina]  feat: creación inicial, unificando las
      cards de conteo de materia-estudiantes.blade.php y
      monitor-en-vivo.blade.php (antes duplicadas, con el mismo bug de
      ícono aplastado en las dos).
    - 2026-10-12  [Diego Tejerina]  fix: $icon pasa a opcional. Con 6
      columnas (o 2 en mobile), "08:00 – 09:30" + el recuadro del ícono no
      entran juntos en el ancho de la card: el texto, aunque en una sola
      línea, se desbordaba por debajo del ícono y quedaba tapado. La card
      "En curso" deja de pasar ícono (ver monitor-en-vivo.blade.php) y acá
      se agrega `overflow-hidden` al valor en una sola línea como resguardo,
      para que si algún día no entra, se recorte en vez de taparse con el
      ícono.
--}}

@props([
    'value' => null,
    'label' => null,
    'tone' => 'brand',
    'labelFirst' => false,
    'nowrap' => false,
])

@php
    $tones = [
        'brand'   => 'text-fg-brand',
        'success' => 'text-fg-success',
        'danger'  => 'text-fg-danger-strong',
        'warning' => 'text-fg-warning',
    ];
    $colorTexto = $tones[$tone] ?? $tones['brand'];
@endphp

<div {{ $attributes->merge(['class' => 'flex items-center justify-between gap-3 bg-neutral-primary-soft p-4 sm:p-6 border border-default rounded-base shadow-xs min-w-0']) }}>
    <div class="min-w-0">
        @if ($labelFirst)
            <p class="text-sm text-body truncate">{{ $label }}</p>
            <p @class(['font-semibold text-sm sm:text-base' => $nowrap, 'text-lg sm:text-2xl' => ! $nowrap, $colorTexto => true, 'whitespace-nowrap overflow-hidden' => $nowrap])>{{ $value }}</p>
        @else
            <p @class(['font-semibold text-xl sm:text-3xl truncate' => true, $colorTexto => true, 'whitespace-nowrap overflow-hidden' => $nowrap])>{{ $value }}</p>
            <p class="text-sm text-body truncate">{{ $label }}</p>
        @endif
    </div>
    @isset($icon)
        <span class="shrink-0 flex items-center justify-center size-10 sm:size-12 rounded-base bg-neutral-secondary-soft {{ $colorTexto }}">
            {{ $icon }}
        </span>
    @endisset
</div>
