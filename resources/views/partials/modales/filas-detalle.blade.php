{{--
    @file    filas-detalle.blade.php
    @author  David E. Chavez T. <virzuzz12345@gmail.com>
    @created 2026-10-10
    @updated 2026-10-10

    @description
    Partial compartido con el bloque de filas "etiqueta / valor" de los
    modales de ingreso. Recibe $filas, un arreglo de ['label' => string,
    'valor' => string]; el valor es una expresión Alpine que se pinta con
    x-text para respetar el estado del modal.

    @changelog
    - 2026-10-10  [David E. Chavez T.]  feat: creación inicial del partial.

    @see  resources/views/components/ui/modal-registro-ingreso.blade.php
--}}

@php $filas = $filas ?? []; @endphp

<div class="space-y-2 rounded-base border border-default bg-neutral-secondary-soft p-4">
    @foreach ($filas as $fila)
        <div class="flex items-center justify-between gap-3 text-sm">
            <span class="text-body">{{ $fila['label'] }}</span>
            <span class="text-right font-semibold text-heading" x-text="{{ $fila['valor'] }}"></span>
        </div>
    @endforeach
</div>
