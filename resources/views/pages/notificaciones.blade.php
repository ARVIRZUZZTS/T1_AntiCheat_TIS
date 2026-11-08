{{--
    @file    notificaciones.blade.php
    @author  Alisson D. Alvarado <alvaradoalissondalet@gmail.com>
    @created 2026-10-09
    @updated 2026-10-11

    @description
    Pantalla de notificaciones del docente. Lista las notificaciones que devuelve
    el endpoint real `/api/notificaciones`, ordenadas de la más reciente a la más
    antigua (el backend ya las ordena y les pone el tiempo transcurrido en
    español). Cada una se pinta con `x-ui.notificacion-card`, eligiendo la
    plantilla según el `tipo` del aviso (HU 6 · T3):

      - `posible_tramposo`: icono ámbar y orientación a la central de riesgos.
      - `central_riesgo`: icono rojo y orientación a la central de riesgos.

    Si no queda ninguna, aparece el mensaje "No tienes notificaciones".

    El rol se lee del usuario autenticado: si es auxiliar, esas notificaciones
    no se le generan ni se le muestran, así que la pantalla no consulta el
    endpoint y arranca en el estado vacío.

    @changelog
    - 2026-10-09  [Alisson D. Alvarado]  feat: creación inicial de la pantalla.
    - 2026-10-10  feat: "Ver detalle" apunta a la ruta del detalle del registro
      (HU 12 · T4).
    - 2026-10-11  [OchoaCesar]  feat: "Ver detalle" abre el modal de detalle de
      incidencia con sus submodales de confirmación y rechazo (HU 12 · T4).
    - 2026-10-11  [OchoaCesar]  fix: al clonar la tarjeta, el script escribe
      `data-incidencia` con el `referencia` del aviso (id de la incidencia);
      el onclick incrustado del template quedaba siempre en 0 y el modal lo
      ignoraba, por lo que "Ver detalle" no abría nada.
    - 2026-11-08  [Alisson D. Alvarado]  fix: mostrar notificaciones sin acciones.

    @see  \resources\views\components\ui\notificacion-card.blade.php
--}}

@extends('layouts.app')

@section('title', 'Notificaciones')

@section('content')
    @php
        $usuario = auth()->user();
        $esAuxiliar = $usuario !== null
            && $usuario->roles->contains('nombre_rol', \App\Models\Rol::NOMBRE_AUXILIAR);
    @endphp

    <div class="px-4 sm:px-6 lg:px-[2vh]">
        <p class="text-body">
            Notificaciones de posibles tramposos.
        </p>

        {{-- Plantillas de tarjeta: el script clona la que corresponde al `tipo`
             de cada notificación que devuelve el endpoint. Una sola fuente de
             estilo por tipo. --}}
        <template id="notificacion-template-tramposo">
            <x-ui.notificacion-card
                class="js-notificacion"
                tipo="posible_tramposo"
                :id="0"
                titulo="Posible Tramposo"
                auxiliar=""
                estudiante=""
                examen=""
                materia=""
                hace=""
            />
        </template>

        <template id="notificacion-template-central">
            <x-ui.notificacion-card
                class="js-notificacion"
                tipo="central_riesgo"
                :id="0"
                titulo="Estudiante ingresado a central de riesgo"
                estudiante=""
                materia=""
                hace=""
            />
        </template>

        <template id="notificacion-template-invitacion">
            <div class="js-notificacion relative flex items-start gap-3 sm:gap-4 rounded-2xl border border-light bg-neutral-primary-soft p-4 sm:p-5 shadow-xs">
                <span class="shrink-0 inline-flex items-center justify-center w-11 h-11 bg-brand-softer rounded-xl">
                    <svg class="w-6 h-6 text-fg-brand-strong" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4" stroke="currentColor" stroke-width="2"/></svg>
                </span>
                <div class="flex-1 min-w-0">
                    <h3 class="js-titulo text-sm font-bold leading-snug text-heading break-words"></h3>
                    <p class="js-descripcion mt-1 text-sm text-body break-words"></p>
                    <span class="js-hace mt-3 block text-xs text-muted"></span>
                </div>
            </div>
        </template>

        <template id="notificacion-template-respuesta">
            <div class="js-notificacion relative flex items-start gap-3 sm:gap-4 rounded-2xl border border-light bg-neutral-primary-soft p-4 sm:p-5 shadow-xs">
                <span class="shrink-0 inline-flex items-center justify-center w-11 h-11 bg-success-soft rounded-xl">
                    <svg class="w-6 h-6 text-fg-success-strong" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 6 9 17l-5-5"/></svg>
                </span>
                <div class="flex-1 min-w-0">
                    <h3 class="js-titulo text-sm font-bold leading-snug text-heading break-words"></h3>
                    <p class="js-descripcion mt-1 text-sm text-body break-words"></p>
                    <span class="js-hace mt-3 block text-xs text-muted"></span>
                </div>
            </div>
        </template>

        <div id="notificaciones-lista"
             class="mt-6 flex flex-col gap-4"
             data-endpoint="{{ route('api.notificaciones.index') }}"
             data-es-auxiliar="{{ $esAuxiliar ? '1' : '0' }}">
        </div>

        <div id="notificaciones-vacio" class="mt-6" hidden>
            <div class="bg-neutral-primary-soft border border-light rounded-base shadow-xs p-6 text-center text-sm text-body">
                No tienes notificaciones
            </div>
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
            var endpoint = lista.dataset.endpoint;
            var esAuxiliar = lista.dataset.esAuxiliar === '1';

            var plantillas = {
                posible_tramposo: document.getElementById('notificacion-template-tramposo'),
                central_riesgo: document.getElementById('notificacion-template-central'),
                invitacion_examen: document.getElementById('notificacion-template-invitacion'),
                respuesta_invitacion: document.getElementById('notificacion-template-respuesta')
            };

            var titulos = {
                posible_tramposo: 'Posible Tramposo',
                central_riesgo: 'Estudiante ingresado a central de riesgo',
                invitacion_examen: 'Invitacion para compartir un examen',
                respuesta_invitacion: 'Respuesta a tu invitacion de examen'
            };

            function actualizarVacio() {
                if (vacio) {
                    vacio.hidden = lista.querySelectorAll('.js-notificacion').length !== 0;
                }
            }

            function descripcionDe(notificacion, tipo) {
                if (notificacion.descripcion) {
                    return notificacion.descripcion;
                }

                if (tipo === 'central_riesgo') {
                    return 'El estudiante ' + (notificacion.estudiante || '')
                        + ' fue ingresado a la central de riesgo en la materia de '
                        + (notificacion.materia || '');
                }

                return '';
            }

            function crearTarjeta(notificacion) {
                var tipo = notificacion.tipo || 'posible_tramposo';
                var plantilla = plantillas[tipo] || plantillas.posible_tramposo;
                var nodo = plantilla.content.firstElementChild.cloneNode(true);
                nodo.dataset.id = notificacion.id;
                nodo.dataset.tipo = tipo;

                var titulo = nodo.querySelector('.js-titulo') || nodo.querySelector('h3');
                var descripcion = nodo.querySelector('.js-descripcion');
                var hace = nodo.querySelector('.js-hace');

                var textoTitulo = notificacion.titulo || titulos[tipo] || '';
                var textoDescripcion = notificacion.descripcion || descripcionDe(notificacion, tipo);

                if (tipo === 'invitacion_examen') {
                    var datos = notificacion.datos || {};
                    textoDescripcion = (notificacion.descripcion || '') + (datos.invitador ? ' Invita: ' + datos.invitador + '.' : '');
                    nodo.dataset.invitacion = notificacion.referencia != null ? notificacion.referencia : '';
                }

                if (tipo === 'respuesta_invitacion') {
                    var datosRespuesta = notificacion.datos || {};
                    textoDescripcion = notificacion.descripcion || '';
                    nodo.dataset.invitacion = notificacion.referencia != null ? notificacion.referencia : '';
                }

                if (titulo) { titulo.textContent = textoTitulo; }
                if (descripcion) { descripcion.textContent = textoDescripcion; }
                if (hace) { hace.textContent = notificacion.hace || ''; }

                return nodo;
            }

            function cargar() {
                if (esAuxiliar) {
                    actualizarVacio();
                    return;
                }

                fetch(endpoint, {
                    headers: { 'Accept': 'application/json' },
                    credentials: 'same-origin'
                })
                    .then(function (respuesta) {
                        if (!respuesta.ok) {
                            throw new Error('HTTP ' + respuesta.status);
                        }
                        return respuesta.json();
                    })
                    .then(function (notificaciones) {
                        (notificaciones || []).forEach(function (notificacion) {
                            lista.appendChild(crearTarjeta(notificacion));
                        });
                        actualizarVacio();
                    })
                    .catch(actualizarVacio);
            }

            cargar();
        })();
    </script>
@endsection
