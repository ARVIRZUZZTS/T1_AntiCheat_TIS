{{--
    @file    notificaciones.blade.php
    @created 2026-10-10

    @description
    Pantalla de notificaciones del docente (HU 12 · T3 y T4). Es la pantalla de
    Inicio: lista las notificaciones de "Posible Tramposo" de la más reciente a
    la más antigua, cada una en un `x-ui.notificacion-card`.

    Datos mockeados: en el backend aún no existe controlador/modelo de la tabla
    `notificacion_docente`, así que se simula la respuesta de un endpoint
    `/notificaciones` (auxiliar registrador, estudiante, examen y materia
    salen de notificacion_docente -> central_riesgo -> usuario/estudiante/examen
    -> curso). Cuando el endpoint real exista, solo cambia la fuente de
    `$notificaciones`.

    Reglas:
    - Si el usuario es auxiliar, no ve las notificaciones de "Posible Tramposo"
      ($esAuxiliar se simula; vendrá del rol autenticado).
    - La X oculta la tarjeta de la lista y muestra "No tienes notificaciones"
      cuando ya no queda ninguna. El registro sigue "En revisión": no se
      confirma, rechaza ni muta nada.
    - "Ver detalle" navega al detalle del registro, sin confirmar ni rechazar.

    @see  \resources\views\components\ui\notificacion-card.blade.php

    @changelog
    - 2026-10-10  feat: creación inicial de la pantalla (T3 + T4).
--}}

@extends('layouts.app')

@section('title', 'Notificaciones')

@section('content')
    @php
        $esAuxiliar = false;

        $formatearTiempo = static fn (\Carbon\CarbonImmutable $fecha): string
            => ucfirst($fecha->locale('es')->diffForHumans());

        $ahora = \Carbon\CarbonImmutable::now();

        $notificaciones = collect([
            [
                'id' => 1,
                'tipo' => 'Posible Tramposo',
                'auxiliar' => 'Juan Peres',
                'estudiante' => 'Ivan Soto Peredo',
                'examen' => 'Segundo Parcial',
                'materia' => 'Cálculo 2',
                'fecha_registro' => $ahora->subMinutes(20),
            ],
            [
                'id' => 2,
                'tipo' => 'Posible Tramposo',
                'auxiliar' => 'María Fernanda Rojas',
                'estudiante' => 'Valentina Arce Flores',
                'examen' => 'Examen Final',
                'materia' => 'Física II',
                'fecha_registro' => $ahora->subHours(2),
            ],
            [
                'id' => 3,
                'tipo' => 'Posible Tramposo',
                'auxiliar' => 'Carlos Andrés Vega',
                'estudiante' => 'Diego Fernández Soto',
                'examen' => 'Primer Parcial',
                'materia' => 'Química Orgánica',
                'fecha_registro' => $ahora->subDay()->subHours(3),
            ],
        ])
            ->when(
                $esAuxiliar,
                fn ($items) => $items->reject(fn (array $n) => ($n['tipo'] ?? '') === 'Posible Tramposo')
            )
            ->sortByDesc('fecha_registro')
            ->values();
    @endphp

    @if ($notificaciones->isEmpty())
        <div class="mt-6 rounded-2xl border border-light bg-neutral-primary-soft p-6 text-center text-sm text-body shadow-xs">
            No tienes notificaciones
        </div>
    @else
        <div id="notificaciones-lista" class="mt-6 flex flex-col gap-4">
            @foreach ($notificaciones as $n)
                <x-ui.notificacion-card
                    :title="'Posible Tramposo'"
                    :text="'El auxiliar '.$n['auxiliar'].' registró al estudiante '.$n['estudiante'].' como posible tramposo en el examen de '.$n['examen'].' de la materia '.$n['materia'].'.'"
                    :time="$formatearTiempo($n['fecha_registro'])"
                >
                    <x-slot:actions>
                        <x-ui.button variant="danger" size="sm" :href="route('incidencias.detalle', $n['id'])">
                            Ver detalle
                        </x-ui.button>
                    </x-slot:actions>
                </x-ui.notificacion-card>
            @endforeach
        </div>

        <div id="notificaciones-vacio" class="mt-6 rounded-2xl border border-light bg-neutral-primary-soft p-6 text-center text-sm text-body shadow-xs" hidden>
            No tienes notificaciones
        </div>
    @endif

    <script>
        (function () {
            'use strict';

            var lista = document.getElementById('notificaciones-lista');

            if (!lista) {
                return;
            }

            var vacio = document.getElementById('notificaciones-vacio');

            lista.addEventListener('click', function (evento) {
                var boton = evento.target.closest('.js-cerrar-notificacion');

                if (!boton) {
                    return;
                }

                var tarjeta = boton.closest('.js-notificacion');

                if (tarjeta) {
                    tarjeta.remove();
                }

                if (vacio && lista.querySelectorAll('.js-notificacion').length === 0) {
                    vacio.hidden = false;
                }
            });
        })();
    </script>
@endsection
