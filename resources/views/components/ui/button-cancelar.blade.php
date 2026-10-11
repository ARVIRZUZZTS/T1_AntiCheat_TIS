{{--
    @file    button-cancelar.blade.php
    @author  David E. Chavez T. <virzuzz12345@gmail.com>
    @created 2026-09-29
    @updated 2026-09-29

    @description
    Botón de cancelación con estilo negativo: texto y borde del color
    de las letras genéricas (heading), fondo transparente. Se usa en
    modales y formularios para la acción de cancelar/cerrar.

    @changelog
    - 2026-09-29  [David E. Chavez T.]  feat: creación inicial del componente UI.
--}}

@props([
    'type' => 'button',
    'disabled' => false,
])

@php
    $classes = 'inline-flex items-center justify-center box-border border border-heading bg-transparent text-heading hover:bg-neutral-secondary-medium hover:text-heading font-medium leading-5 rounded-base focus:outline-none focus:ring-4 focus:ring-neutral-tertiary px-4 py-2.5 text-sm';

    if ($disabled) {
        $classes .= ' opacity-50 cursor-not-allowed';
    }
@endphp

<button {{ $attributes->merge(['type' => $type, 'disabled' => $disabled, 'class' => $classes]) }}>
    {{ $slot }}
</button>
