{{--
    @file    notificacion-card.blade.php
    @created 2026-10-10

    @description
    Tarjeta presentacional y reutilizable para una notificación. Recibe todo
    por props/slots para no quedar atada a un solo tipo:

      - $icon       SVG crudo (string). Si no se pasa, usa el triángulo de
                    advertencia por defecto.
      - $iconClass  Clases de color del cuadro del icono (fondo + texto).
      - $title      Título en negrita.
      - $text       Texto descriptivo.
      - $time       Tiempo transcurrido ("Hace 20 minutos"); va abajo a la
                    izquierda.
      - $actions    Slot con las acciones de la tarjeta; se alinean abajo a
                    la derecha.

    La X de cerrar es parte del componente y funciona con JavaScript plano (el
    proyecto no carga Alpine, solo el bundle de Livewire): el handler de la
    página elimina el nodo con clase `js-notificacion`. Cerrar es solo un
    descarte visual; NO cambia el estado del registro.

    El pie (tiempo + acciones) usa un único `flex justify-between`, así el
    tiempo queda abajo a la izquierda y el botón abajo a la derecha tanto en
    móvil como en escritorio. El título y el texto usan `break-words` y el
    contenido reserva espacio a la derecha para que la X no los pise cuando el
    título parte en varias líneas.

    @changelog
    - 2026-10-10  feat: creación inicial del componente reutilizable.
--}}

@props([
    'title' => '',
    'text' => '',
    'time' => null,
    'icon' => null,
    'iconClass' => 'bg-warning-soft text-warning',
])

<article {{ $attributes->merge(['class' => 'js-notificacion relative flex items-start gap-3 sm:gap-4 rounded-2xl border border-light bg-neutral-primary-soft p-4 sm:p-5 shadow-xs']) }}>
    <span class="shrink-0 inline-flex h-11 w-11 items-center justify-center rounded-xl {{ $iconClass }}">
        @if ($icon)
            {!! $icon !!}
        @else
            <svg class="w-6 h-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0ZM12 9v4m0 4h.01"/></svg>
        @endif
    </span>

    <div class="min-w-0 flex-1 pe-7">
        <h3 class="text-sm font-bold leading-snug text-heading break-words">{{ $title }}</h3>
        <p class="mt-1 text-sm text-body break-words">{{ $text }}</p>

        <div class="mt-3 flex items-center justify-between gap-3">
            @if ($time)
                <time class="text-xs text-muted">{{ $time }}</time>
            @else
                <span aria-hidden="true"></span>
            @endif

            @if (isset($actions))
                <div class="shrink-0">{{ $actions }}</div>
            @endif
        </div>
    </div>

    <button type="button" aria-label="Cerrar notificación" title="Cerrar"
            class="js-cerrar-notificacion absolute end-2 top-2 inline-flex h-8 w-8 items-center justify-center rounded-base text-neutral-tertiary-medium hover:bg-neutral-tertiary-soft hover:text-heading focus:outline-none focus:ring-4 focus:ring-neutral-tertiary-soft">
        <svg class="w-4 h-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6 18 17.94 6M18 18 6.06 6"/></svg>
    </button>
</article>
