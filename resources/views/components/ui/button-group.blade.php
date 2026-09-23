{{--
    @file    button-group.blade.php
    @author  Alex Candia <alex.leonar.candia@gmail.com>
    @created 2026-09-23
    @updated 2026-09-23

    @description
    Agrupador de botones adyacentes (primer elemento redondeado a la
    izquierda, último a la derecha). Los hijos deben ser <x-ui.button>.
--}}

<div {{ $attributes->merge(['class' => 'inline-flex rounded-base shadow-xs -space-x-px', 'role' => 'group']) }}>
    {{ $slot }}
</div>