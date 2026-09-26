<div class="p-4">
    <h1 class="text-xl font-bold mb-4">Asistencia del examen</h1>

    <ul class="space-y-2">
        @forelse ($asistencias as $fila)
            <li @class([
                'p-3 rounded border',
                'border-red-500 bg-red-50'   => $fila['presente'],
                'border-gray-300 bg-white'   => ! $fila['presente'],
            ])>
                <span class="font-semibold">
                    {{ $fila['estudiante']->apellido_estudiante }},
                    {{ $fila['estudiante']->nombre_estudiante }}
                </span>

                @if ($fila['presente'])
                    <span class="text-sm text-red-700 ml-2">
                        Ingresó a las {{ $fila['hora'] }}
                    </span>
                @endif
            </li>
        @empty
            <li class="text-gray-500">No hay estudiantes en este examen.</li>
        @endforelse
    </ul>
</div>