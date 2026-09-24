{{--
    @file    radio.blade.php
    @author  OchoaCesar
    @created 2026-09-23
    @updated 2026-09-23

    @description
    Grupo de radios. $options es un arreglo de
    ['value' => ..., 'label' => ..., 'checked' => bool, 'disabled' => bool].
--}}

@props([
    'name' => 'countries',
    'legend' => null,
    'options' => [],
])

@php
    $defaults = [
        ['value' => 'usa', 'label' => 'United States'],
        ['value' => 'de', 'label' => 'Germany'],
        ['value' => 'es', 'label' => 'Spain'],
        ['value' => 'uk', 'label' => 'United Kingdom'],
    ];
    $items = $options ?: $defaults;
@endphp

<fieldset {{ $attributes }}>
    @if ($legend)
        <legend class="mb-2 text-sm font-medium text-heading">{{ $legend }}</legend>
    @else
        <legend class="sr-only">{{ $name }}</legend>
    @endif

    @foreach ($items as $index => $item)
        @php
            $value = $item['value'] ?? $index;
            $label = $item['label'] ?? $value;
            $disabled = $item['disabled'] ?? false;
            $checked = $item['checked'] ?? false;
        @endphp

        <div @class(['flex items-center', 'mb-4' => ! $loop->last])>
            <input id="{{ $name }}-{{ $value }}" type="radio" name="{{ $name }}" value="{{ $value }}"
                   class="w-4 h-4 text-neutral-primary bg-neutral-secondary-medium border border-default-medium rounded-full checked:border-brand focus:ring-2 focus:outline-none focus:ring-brand-subtle appearance-none"
                   @checked($checked) @disabled($disabled) />

            <label for="{{ $name }}-{{ $value }}" @class([
                'select-none ms-2 text-sm font-medium',
                'text-fg-disabled' => $disabled,
                'text-heading' => ! $disabled,
            ])>
                {{ $label }}
            </label>
        </div>
    @endforeach
</fieldset>