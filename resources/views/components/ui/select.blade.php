{{--
    @file    select.blade.php
    @author  Valery D. Ortuno P. <valerydariana98@gmail.com>
    @created 2026-09-24
    @updated 2026-09-28

    @description
    Select desplegable con etiqueta. $options es un arreglo de
    ['value' => ..., 'label' => ...]; $selected marca la opción activa.
    $error activa el borde de danger y muestra el mensaje bajo el campo.

    El valor de cada opción sale de la clave cuando el arreglo es asociativo
    (`[id => etiqueta]`, como los catálogos) y de la etiqueta cuando es una lista
    simple sin claves propias (`['A', 'B']`). Así sirve tanto para mapas
    id ⇒ nombre como para listas de texto.

    @changelog
    - 2026-09-24  [Valery D. Ortuno P]  feat: creación inicial del componente.
    - 2026-09-25  [Valery D. Ortuno P]  feat: agregar estado de error.
    - 2026-09-28  [Valery D. Ortuno P]  fix: la opción de placeholder lleva
      value="" para que el selector arranque en ella y no en la primera opción.
    - 2026-10-10  [Valery D. Ortuno P]  feat: marca de obligatorio (*) opcional.
    - 2026-10-10  [T1]  fix: la clave se usa como valor en cualquier arreglo
      asociativo, no solo cuando es texto; con claves numéricas (id de curso) el
      selector mandaba la etiqueta en vez del id.
--}}

@props([
    'label' => null,
    'name' => null,
    'id' => null,
    'options' => [],
    'selected' => null,
    'placeholder' => null,
    'error' => null,
    'required' => false,
])

@php
    $inputId = $id ?? $name;
    $stateClass = $error
        ? 'bg-danger-soft border-danger-subtle text-fg-danger-strong focus:ring-danger focus:border-danger'
        : 'bg-neutral-secondary-medium border-default-medium text-heading focus:ring-brand focus:border-brand';

    // Un arreglo asociativo usa su clave como valor de la opción (catálogos
    // id ⇒ nombre); una lista simple sin claves propias usa la etiqueta.
    $valorPorClave = ! array_is_list($options);
@endphp

<div>
    @if ($label)
        <label for="{{ $inputId }}" class="block mb-2.5 text-sm font-medium text-heading">{{ $label }}@if ($required) <span class="text-fg-danger-strong" aria-hidden="true">*</span>@endif</label>
    @endif

    <select name="{{ $name }}" id="{{ $inputId }}"
            {{ $attributes->merge(['class' => 'block w-full px-3 py-2.5 border text-sm rounded-base shadow-xs placeholder:text-body ' . $stateClass]) }}
            @if ($error) aria-invalid="true" @endif>
        @if ($placeholder)
            <option value="" disabled @if (! $selected) selected @endif>{{ $placeholder }}</option>
        @endif

        @foreach ($options as $value => $label)
            @php $valorOpcion = $valorPorClave ? $value : $label; @endphp
            <option value="{{ $valorOpcion }}" @selected((string) $selected === (string) $valorOpcion)>
                {{ $label }}
            </option>
        @endforeach
    </select>

    @if ($error)
        <p class="mt-1 text-sm text-fg-danger-strong">{{ $error }}</p>
    @endif
</div>
