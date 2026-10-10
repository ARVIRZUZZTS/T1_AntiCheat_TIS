{{--
    @file    notificaciones.blade.php
    @author  Alisson D. Alvarado <alvaradoalissondalet@gmail.com>
    @created 2026-10-09
    @updated 2026-10-09

    @description
    Pantalla de notificaciones del docente. Muestra las notificaciones de
    "Posible Tramposo" ordenadas de la más reciente a la más antigua, cada una
    en su tarjeta (`x-ui.notificacion-card`). Si el usuario es auxiliar, esas
    notificaciones no se muestran; si no queda ninguna, aparece el mensaje
    "No tienes notificaciones".

    El botón de cerrar (X) de cada tarjeta la borra del DOM con JavaScript plano
    (sin Alpine: el proyecto solo carga el bundle de Livewire). Es un descarte
    visual porque los datos son mockeados; cuando exista el endpoint real, ese
    mismo handler persistirá el borrado.

    Los datos son mockeados a propósito: representan la respuesta de un endpoint
    de notificaciones que se conectará más adelante. El rol del usuario tampoco
    viene del auth todavía: se simula con `$esAuxiliar`.

    @changelog
    - 2026-10-09  [Alisson D. Alvarado]  feat: creación inicial de la pantalla.

    @see  \resources\views\components\ui\notificacion-card.blade.php
--}}

@extends('layouts.app')

@section('title', 'Notificaciones')

@section('content')
    <p class="text-body">
        Notificaciones de posibles tramposos. Datos mockeados: vendrán del endpoint
        <code class="text-fg-brand">/notificaciones</code>.
    </p>

    @php
        $ahora = \Carbon\CarbonImmutable::now();
        $esAuxiliar = false;
        $formatearTiempo = static function (\Carbon\CarbonImmutable $fecha, \Carbon\CarbonImmutable $ahora): string {
            $minutos = (int) $ahora->greaterThan($fecha) ? $ahora->diffInMinutes($fecha) : 0;

            if ($minutos < 1) {
                return 'Hace menos de un minuto';
            }

            if ($minutos < 60) {
                return $minutos === 1 ? 'Hace 1 minuto' : "Hace {$minutos} minutos";
            }

            $horas = (int) floor($minutos / 60);

            if ($horas < 24) {
                return $horas === 1 ? 'Hace 1 hora' : "Hace {$horas} horas";
            }

            $dias = (int) floor($horas / 24);

            return $dias === 1 ? 'Hace 1 día' : "Hace {$dias} días";
        };

        // Respuesta mockeada del endpoint /notificaciones, alineada con el esquema
        // real: notificacion_docente -> central_riesgo -> registro_asistencia ->
        // estudiante/examen, con el auxiliar como registrador y el curso como materia.
        $notificaciones = collect([
            [
                'id' => 1,
                'tipo' => 'Posible Tramposo',
                'auxiliar' => 'María Fernanda Rojas',
                'estudiante' => 'Juan Pablo Quispe Mamani',
                'examen' => 'Primer Parcial',
                'materia' => 'Matemática I',
                'fecha_registro' => $ahora->subMinutes(20),
            ],
            [
                'id' => 2,
                'tipo' => 'Posible Tramposo',
                'auxiliar' => 'Carlos Andrés Vega',
                'estudiante' => 'Valentina Arce Flores',
                'examen' => 'Examen Final',
                'materia' => 'Física II',
                'fecha_registro' => $ahora->subHours(2),
            ],
            [
                'id' => 3,
                'tipo' => 'Posible Tramposo',
                'auxiliar' => 'Lucía Morales Castro',
                'estudiante' => 'Diego Fernández Soto',
                'examen' => 'Segundo Parcial',
                'materia' => 'Química Orgánica',
                'fecha_registro' => $ahora->subDays(1)->subHours(3),
            ],
        ])
            ->reject(static fn (array $n): bool => $esAuxiliar && ($n['tipo'] ?? '') === 'Posible Tramposo')
            ->sortByDesc('fecha_registro')
            ->values()
            ->map(static function (array $n) use ($ahora, $formatearTiempo): array {
                $n['hace'] = $formatearTiempo($n['fecha_registro'], $ahora);

                return $n;
            });
    @endphp

    <div id="notificaciones-lista" class="mt-6 flex flex-col gap-4 px-[2vh]">
        @foreach ($notificaciones as $notificacion)
            <x-ui.notificacion-card
                class="js-notificacion"
                :id="$notificacion['id']"
                :titulo="$notificacion['tipo']"
                :auxiliar="$notificacion['auxiliar']"
                :estudiante="$notificacion['estudiante']"
                :examen="$notificacion['examen']"
                :materia="$notificacion['materia']"
                :hace="$notificacion['hace']"
            />
        @endforeach
    </div>

    {{-- Estado vacío. Se oculta con el atributo HTML cuando hay notificaciones y
         el script de abajo lo revela si el docente cierra todas con la X; así el
         mensaje aparece tanto al arrancar sin datos como tras borrar la última. --}}
    <div id="notificaciones-vacio" class="mt-6 px-[2vh]" @if ($notificaciones->isNotEmpty()) hidden @endif>
        <div class="bg-neutral-primary-soft border border-light rounded-base shadow-xs p-6 text-center text-sm text-body">
            No tienes notificaciones
        </div>
    </div>

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