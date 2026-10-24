{{--
    @file    table.blade.php
    @author  David E. Chavez T. <virzuzz12345@gmail.com>
    @created 2026-09-23
    @updated 2026-09-26

    @description
    Tabla de datos con encabezados y filas. $headers es un arreglo de
    strings y $rows un arreglo de filas; cada celda puede ser un string
    o ['heading' => true, 'value' => ...] para la columna de cabecera
    de la fila, o ['html' => ...] para contenido HTML (badges, botones).
    Una fila puede traer la clave '__rowClass' => 'clases-tailwind' para
    pintar el fondo de esa fila completa, '__xShow' => 'expresion-alpine'
    para ocultar la fila según el estado del formulario (se ignora al
    renderizar celdas), y '__rowAttrs' => ['data-x' => 'y'] para fijar
    atributos arbitrarios en el <tr>, que es como el monitor en vivo marca
    cada fila para filtrarla desde el navegador. Con $compact, las filas
    bajan de py-4 a py-2.5 (~44px de alto).

    El slot nombrado `body` reemplaza el bucle de $rows y sirve para tablas
    cuyas filas las dibuja Alpine con x-for (reportes).

    @see  resources/views/pages/curso-detalle.blade.php

    @changelog
    - 2026-09-23  [David E. Chavez T.]  feat: creación inicial del componente.
    - 2026-09-26  [Alisson D. Alvarado] feat: soporte de visibilidad por fila
      con la clave '__xShow' (misma convención '__' que '__rowClass').
    - 2026-10-10  [T1]  feat: soporte de atributos por fila con la clave
      '__rowAttrs' (misma convención '__' que las dos anteriores).
    - 2026-10-12  [Diego Tejerina]  feat: prop opcional `minWidth` para fijar
      un ancho mínimo a la tabla (por ejemplo 'min-w-[640px]') en tablas con
      muchas columnas que en mobile quedarían ilegibles si se achicaran al
      ancho de la pantalla; el scroll queda contenido en este componente
      (`overflow-x-auto` del div envolvente), nunca en la página. Sin este
      prop el comportamiento no cambia.
    - 2026-10-23  [Alex Candia]  feat: slot `body` para filas dinámicas de
      Alpine sin duplicar el contenedor ni los encabezados en cada reporte.
--}}

@props([
    'headers' => [],
    'rows' => [],
    'compact' => false,
    'minWidth' => null,
])

@php
    $cellPad = $compact ? 'px-4 py-2.5' : 'px-6 py-4';
@endphp

<div class="relative overflow-x-auto bg-neutral-primary-soft shadow-xs rounded-base border border-default">
    <table class="w-full text-sm text-left rtl:text-right text-body {{ $minWidth }}">
        <thead class="text-sm text-body bg-neutral-secondary-soft border-b rounded-base border-default">
            <tr>
                @foreach ($headers as $header)
                    <th scope="col" class="px-6 py-3 font-medium">{{ $header }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @if (isset($body) && $body->isNotEmpty())
                {{ $body }}
            @else
                @foreach ($rows as $row)
                    @if (is_array($row) && array_key_exists('__separator', $row))
                        <tr>
                            <td colspan="{{ count($headers) }}" class="px-4 py-2">
                                <hr class="border-default">
                            </td>
                        </tr>
                        @continue
                    @endif
                    @php
                        $rowClass = is_array($row) ? ($row['__rowClass'] ?? null) : null;
                        $rowXShow = is_array($row) ? ($row['__xShow'] ?? null) : null;
                        $rowAttrs = is_array($row) ? ($row['__rowAttrs'] ?? []) : [];
                    @endphp
                    <tr @class([
                        $loop->last ? 'bg-neutral-primary' : 'bg-neutral-primary border-b border-default',
                        $rowClass => $rowClass,
                    ]) @foreach ($rowAttrs as $attr => $valor) {{ $attr }}="{{ $valor }}" @endforeach @if ($rowXShow !== null) x-show="{{ $rowXShow }}" @endif>
                        @foreach ($row as $key => $cell)
                            @continue(in_array($key, ['__rowClass', '__xShow', '__rowAttrs'], true))
                            @if (is_array($cell) && ! empty($cell['html']))
                                <td class="{{ $cellPad }}">{!! $cell['html'] !!}</td>
                            @elseif (is_array($cell) && ! empty($cell['heading']))
                                <th scope="row" class="{{ $cellPad }} font-medium text-heading whitespace-nowrap">{{ $cell['value'] }}</th>
                            @else
                                <td class="{{ $cellPad }}">{{ is_array($cell) ? $cell['value'] : $cell }}</td>
                            @endif
                        @endforeach
                    </tr>
                @endforeach
            @endif
        </tbody>
    </table>

    @if ($slot->isNotEmpty())
        {{ $slot }}
    @endif
</div>