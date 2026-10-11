{{--
    @file    notificaciones.blade.php
    @author  Alisson D. Alvarado <alvaradoalissondalet@gmail.com>
    @created 2026-10-09
    @updated 2026-10-11

    @description
    Pantalla de notificaciones del docente. Lista las notificaciones de
    "Posible Tramposo" que devuelve el endpoint real `/api/notificaciones`,
    ordenadas de la más reciente a la más antigua (el backend ya las ordena y
    les pone el tiempo transcurrido en español). Cada una se pinta con
    `x-ui.notificacion-card`. Si no queda ninguna, aparece el mensaje
    "No tienes notificaciones".

    El rol se lee del usuario autenticado: si es auxiliar, esas notificaciones
    no se le generan ni se le muestran, así que la pantalla no consulta el
    endpoint y arranca en el estado vacío.

    "Ver detalle" despacha el evento window 'abrir-detalle-incidencia' que abre
    el modal `x-ui.modal-detalle-incidencia` (detalle + submodales de confirmar
    y rechazar contra la API); sin JS conserva el href como respaldo. Nada se
    confirma ni se rechaza hasta que el docente lo indique en ese modal.

    "Ver detalle" navega al detalle del registro (T4) sin confirmar ni rechazar
    nada.

    @changelog
    - 2026-10-09  [Alisson D. Alvarado]  feat: creación inicial de la pantalla.
    - 2026-10-10  feat: "Ver detalle" apunta a la ruta del detalle del registro
      (HU 12 · T4).
    - 2026-10-11  [OchoaCesar]  feat: "Ver detalle" abre el modal de detalle de
      incidencia con sus submodales de confirmación y rechazo (HU 12 · T4).

    @see  \resources\views\components\ui\notificacion-card.blade.php
    @see  \resources\views\components\ui\modal-detalle-incidencia.blade.php
--}}

@extends('layouts.app')

@section('title', 'Notificaciones')

@section('content')
    @php
        $usuario = auth()->user();
        $esAuxiliar = $usuario !== null
            && $usuario->roles->contains('nombre_rol', \App\Models\Rol::NOMBRE_AUXILIAR);
    @endphp

    <p class="text-body">
        Notificaciones de posibles tramposos.
    </p>

    {{-- Plantilla de una tarjeta: el script la clona por cada notificación que
         devuelve el endpoint. Mantiene una sola fuente de estilos. --}}
    <template id="notificacion-template">
        <x-ui.notificacion-card
            class="js-notificacion"
            :id="0"
            titulo="Posible Tramposo"
            auxiliar=""
            estudiante=""
            examen=""
            materia=""
            hace=""
            detalleHref="#"
        />
    </template>

    <div id="notificaciones-lista"
         class="mt-6 flex flex-col gap-4 px-[2vh]"
         data-endpoint="{{ route('api.notificaciones.index') }}"
         data-es-auxiliar="{{ $esAuxiliar ? '1' : '0' }}">
    </div>

    <div id="notificaciones-vacio" class="mt-6 px-[2vh]" hidden>
        <div class="bg-neutral-primary-soft border border-light rounded-base shadow-xs p-6 text-center text-sm text-body">
            No tienes notificaciones
        </div>
    </div>

    {{-- Modal de detalle de incidencia (con sus submodales de decisión y toast). --}}
    <x-ui.modal-detalle-incidencia />

    <script>
        (function () {
            'use strict';

            var lista = document.getElementById('notificaciones-lista');
            var plantilla = document.getElementById('notificacion-template');

            if (!lista || !plantilla) {
                return;
            }

            var vacio = document.getElementById('notificaciones-vacio');
            var meta = document.querySelector('meta[name="csrf-token"]');
            var token = meta ? meta.getAttribute('content') : '';
            var endpoint = lista.dataset.endpoint;
            var esAuxiliar = lista.dataset.esAuxiliar === '1';

            function actualizarVacio() {
                if (vacio) {
                    vacio.hidden = lista.querySelectorAll('.js-notificacion').length !== 0;
                }
            }

            function crearTarjeta(notificacion) {
                var nodo = plantilla.content.firstElementChild.cloneNode(true);
                nodo.dataset.id = notificacion.id;

                var titulo = nodo.querySelector('h3');
                var descripcion = nodo.querySelector('.js-descripcion');
                var hace = nodo.querySelector('.js-hace');
                var detalle = nodo.querySelector('a');
                var cerrar = nodo.querySelector('.js-cerrar-notificacion');

                if (titulo) { titulo.textContent = notificacion.titulo || ''; }
                if (descripcion) { descripcion.textContent = notificacion.descripcion || ''; }
                if (hace) { hace.textContent = notificacion.hace || ''; }
                if (detalle) { detalle.setAttribute('href', notificacion.detalle_href || '#'); }
                if (cerrar) { cerrar.dataset.id = notificacion.id; }

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

            lista.addEventListener('click', function (evento) {
                var boton = evento.target.closest('.js-cerrar-notificacion');

                if (!boton) {
                    return;
                }

                var tarjeta = boton.closest('.js-notificacion');

                if (!tarjeta) {
                    return;
                }

                boton.disabled = true;

                fetch(endpoint + '/' + tarjeta.dataset.id + '/ocultar', {
                    method: 'PATCH',
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': token,
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                    .then(function (respuesta) {
                        if (!respuesta.ok) {
                            throw new Error('HTTP ' + respuesta.status);
                        }
                        tarjeta.remove();
                        actualizarVacio();
                    })
                    .catch(function () {
                        boton.disabled = false;
                    });
            });

            cargar();
        })();
    </script>
@endsection
