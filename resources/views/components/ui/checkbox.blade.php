{{--
    @file    checkbox.blade.php
    @author  Alex Candia <alex.leonar.candia@gmail.com>
    @created 2026-09-23
    @updated 2026-10-05

    @description
    Casilla de verificación con etiqueta, estado marcado/deshabilitado
    y texto de ayuda opcional (slot).

    Los atributos extra (x-model, :value, :id...) van sobre el <input>, igual que
    en `input.blade.php` y `textarea.blade.php`: son atributos del control, no del
    renglón. Antes se fusionaban sobre el <div> contenedor, lo que impedía
    enlazarla con Alpine y darle un valor propio, así que un grupo de casillas
    marcado en bloque no se podía armar con este componente.

    @changelog
    - 2026-09-23  [Alex Candia]  feat: creación inicial del componente UI.
    - 2026-10-05  [Alex Candia]  refactor: los atributos se fusionan sobre el
      <input> y se agrega la prop `value`, para poder armar grupos de casillas
      con x-model. El renglón pierde su `mb-4`, que ahora pone quien arma la lista.

    @see  resources/views/components/ui/modal-crear-examen.blade.php
--}}

@props([
    'label' => null,
    'name' => null,
    'id' => null,
    'value' => null,
    'checked' => false,
    'disabled' => false,
])

@php
    $inputId = $id ?? $name;
    $stateClass = $disabled
        ? 'border-light'
        : 'border-default-medium focus:ring-brand-soft';
@endphp

<div class="flex items-center">
    <input id="{{ $inputId }}" type="checkbox" name="{{ $name }}"
           @if ($value !== null) value="{{ $value }}" @endif
           {{ $attributes->merge(['class' => 'w-4 h-4 shrink-0 rounded-xs bg-neutral-secondary-medium focus:ring-2 ' . $stateClass]) }}
           @checked($checked) @disabled($disabled) />

    <label for="{{ $inputId }}" @class([
        'ms-2 text-sm font-medium select-none',
        'text-fg-disabled' => $disabled,
        'text-heading' => ! $disabled,
    ])>
        {{ $label }}
        {{ $slot }}
    </label>
</div>