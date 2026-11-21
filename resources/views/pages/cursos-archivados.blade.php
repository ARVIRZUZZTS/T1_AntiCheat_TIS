@extends('layouts.app')

@section('title', 'Cursos archivados')

@section('content')
    <div class="px-4 py-4 sm:px-6 sm:py-6 lg:px-8">
        <div class="mx-auto w-full max-w-5xl space-y-4">
            @forelse ($cursos as $curso)
                <details class="rounded-base border border-default bg-neutral-primary-soft shadow-xs">
                    <summary class="cursor-pointer list-none p-4 sm:p-5">
                        <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="font-semibold text-heading break-words">{{ $curso->nombre_curso }}</p>
                                <p class="text-xs text-body">Archivado · {{ $curso->estudiantes_count }} estudiantes · {{ $curso->examenes_count }} exámenes</p>
                            </div>
                            <span class="text-xs text-fg-brand">Solo lectura</span>
                        </div>
                    </summary>

                    <div class="border-t border-default p-4 sm:p-5 space-y-6">
                        <section>
                            <h3 class="text-sm font-semibold text-heading">Estudiantes</h3>
                            @if ($curso->estudiantes->isEmpty())
                                <p class="mt-2 text-sm text-body">Sin estudiantes registrados.</p>
                            @else
                                <ul class="mt-2 divide-y divide-default rounded-base border border-default">
                                    @foreach ($curso->estudiantes as $estudiante)
                                        <li class="px-3 py-2 text-sm text-body">
                                            {{ $estudiante->nombre_estudiante }} {{ $estudiante->apellido_estudiante }} ({{ $estudiante->sis_estudiante }})
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </section>

                        <section>
                            <h3 class="text-sm font-semibold text-heading">Exámenes</h3>
                            @if ($curso->examenes->isEmpty())
                                <p class="mt-2 text-sm text-body">Sin exámenes registrados.</p>
                            @else
                                <div class="mt-2 space-y-3">
                                    @foreach ($curso->examenes as $examen)
                                        <div class="rounded-base border border-default p-3">
                                            <p class="text-sm font-medium text-heading">
                                                {{ $examen->nombre_examen ?? ($examen->tipoExamen?->nombre_tipo_examen ?? ('Examen #'.$examen->id_examen)) }}
                                            </p>
                                            <p class="text-xs text-body mt-1">
                                                Fecha: {{ $examen->fecha ?? '—' }} · Hora: {{ $examen->hora_inicio ? substr($examen->hora_inicio, 0, 5) : '—' }} · Duración: {{ $examen->duracion ?? '—' }} min
                                            </p>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </section>

                        <section>
                            <h3 class="text-sm font-semibold text-heading">Incidencias</h3>
                            @php
                                $incidencias = $curso->examenes->flatMap->incidencias;
                            @endphp
                            @if ($incidencias->isEmpty())
                                <p class="mt-2 text-sm text-body">Sin incidencias registradas.</p>
                            @else
                                <div class="mt-2 overflow-x-auto rounded-base border border-default">
                                    <table class="w-full text-sm text-left text-body">
                                        <thead class="text-xs uppercase bg-neutral-secondary-medium text-heading">
                                            <tr>
                                                <th class="px-3 py-2">Estudiante</th>
                                                <th class="px-3 py-2">Examen</th>
                                                <th class="px-3 py-2">Tipo</th>
                                                <th class="px-3 py-2">Fecha</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($incidencias as $incidencia)
                                                <tr class="border-t border-default">
                                                    <td class="px-3 py-2">
                                                        {{ $incidencia->estudiante?->nombre_estudiante }} {{ $incidencia->estudiante?->apellido_estudiante }}
                                                        ({{ $incidencia->sis_estudiante }})
                                                    </td>
                                                    <td class="px-3 py-2">#{{ $incidencia->id_examen }}</td>
                                                    <td class="px-3 py-2">{{ $incidencia->tipo_infraccion ?? '—' }}</td>
                                                    <td class="px-3 py-2">{{ $incidencia->fecha_registro ?? '—' }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </section>
                    </div>
                </details>
            @empty
                <div class="rounded-base border border-default bg-neutral-primary-soft p-8 text-center text-sm text-body shadow-xs">
                    No tienes cursos archivados.
                </div>
            @endforelse
        </div>
    </div>
@endsection
