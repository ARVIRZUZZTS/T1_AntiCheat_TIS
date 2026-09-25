{{--
    @file    table.blade.php
    @author  David E. Chavez T. <virzuzz12345@gmail.com>
    @created 2026-09-23
    @updated 2026-09-23

    @description
    Tabla de datos con encabezados y filas. $headers es un arreglo de
    strings y $rows un arreglo de filas; cada celda puede ser un string
    o ['heading' => true, 'value' => ...] para la columna de cabecera
    de la fila, o ['html' => ...] para contenido HTML (badges, botones).
--}}

@props([
    'headers' => [],
    'rows' => [],
])

<div class="relative overflow-x-auto bg-neutral-primary-soft shadow-xs rounded-base border border-default">
    <table class="w-full text-sm text-left rtl:text-right text-body">
        <thead class="text-sm text-body bg-neutral-secondary-soft border-b rounded-base border-default">
            <tr>
                @foreach ($headers as $header)
                    <th scope="col" class="px-6 py-3 font-medium">{{ $header }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $row)
                <tr @class(['bg-neutral-primary border-b border-default' => ! $loop->last, 'bg-neutral-primary' => $loop->last])>
                    @foreach ($row as $cell)
                        @if (is_array($cell) && ! empty($cell['html']))
                            <td class="px-6 py-4">{!! $cell['html'] !!}</td>
                        @elseif (is_array($cell) && ! empty($cell['heading']))
                            <th scope="row" class="px-6 py-4 font-medium text-heading whitespace-nowrap">{{ $cell['value'] }}</th>
                        @else
                            <td class="px-6 py-4">{{ is_array($cell) ? $cell['value'] : $cell }}</td>
                        @endif
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>

    @if ($slot->isNotEmpty())
        {{ $slot }}
    @endif
</div>