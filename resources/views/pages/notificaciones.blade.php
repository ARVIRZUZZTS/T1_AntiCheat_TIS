{{--
    @file    notificaciones.blade.php
    @author  Alisson D. Alvarado <alvaradoalissondalet@gmail.com>
    @created 2026-10-09
    @updated 2026-10-11
    @updated 2026-11-24

    @description
    Pantalla de notificaciones del usuario. Lista las notificaciones que devuelve
    el endpoint real `/api/notificaciones`, ordenadas de la más reciente a la más
    antigua (el backend ya las ordena y les pone el tiempo transcurrido en
    español). Cada una se pinta con la plantilla que corresponde a su `tipo`:

      - `posible_tramposo`: icono ámbar y orientación a la central de riesgos.
      - `central_riesgo`: icono rojo y orientación a la central de riesgos.
      - `examen_modificado`: icono ámbar con el detalle de lo que se cambió en
        un examen (ambientes, fecha u hora).
      - `examen_eliminado`: icono rojo con el examen eliminado, el curso del
        que se quitó y quién lo eliminó.

    Si no queda ninguna, aparece el mensaje "No tienes notificaciones".

    Docentes y auxiliares consultan la misma bandeja: cada uno ve solo los avisos
    dirigidos a su `id_usuario`.

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
    - 2026-11-24  [Equipo T1]  fix: al cargar la bandeja se marcan como leídas
      todas las notificaciones nuevas y el badge del sidebar baja a 0.
    - 2026-11-24  [Alisson D. Alvarado <alvaradoalissondalet@gmail.com>]  fix:
      identificar la bandeja del auxiliar con sus avisos de cambios de aula.
     - 2026-11-24  [Alisson D. Alvarado <alvaradoalissondalet@gmail.com>]  fix:
       identificar la bandeja del auxiliar con sus avisos de cambios de aula.
     - 2026-11-24  [Equipo T1]  feat: los auxiliares consultan su bandeja y se
        muestra el aviso de cambios de ambientes, fecha y hora de exámenes.
     - 2026-11-24  [OchoaCesar]  feat: tarjeta del aviso «Examen eliminado»
       (`examen_eliminado`) con icono rojo de papelera.
     - 2026-11-27  [Alisson D. Alvarado <alvaradoalissondalet@gmail.com>]  feat:
       mostrar el plazo de dos dias de los avisos leidos.


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
            {{ $esAuxiliar ? 'Notificaciones de cambios en exámenes.' : 'Notificaciones de posibles tramposos y cambios en exámenes.' }}
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

        <template id="notificacion-template-respaldo">
            <div class="js-notificacion relative flex items-start gap-3 sm:gap-4 rounded-2xl border border-light bg-neutral-primary-soft p-4 sm:p-5 shadow-xs">
                <span class="shrink-0 inline-flex items-center justify-center w-11 h-11 bg-brand-softer rounded-xl">
                    <svg class="w-6 h-6 text-fg-brand-strong" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><ellipse cx="12" cy="6" rx="8" ry="3" stroke="currentColor" stroke-width="2"/><path stroke="currentColor" stroke-linecap="round" stroke-width="2" d="M4 6v12c0 1.7 3.6 3 8 3s8-1.3 8-3V6M4 12c0 1.7 3.6 3 8 3s8-1.3 8-3"/></svg>
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

         <template id="notificacion-template-examen">
             <div class="js-notificacion relative flex items-start gap-3 sm:gap-4 rounded-2xl border border-light bg-neutral-primary-soft p-4 sm:p-5 shadow-xs">
                 <span class="shrink-0 inline-flex items-center justify-center w-11 h-11 bg-warning-soft rounded-xl">
                     <svg class="w-6 h-6 text-warning" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="16" rx="2" stroke="currentColor" stroke-width="2"/><path stroke="currentColor" stroke-linecap="round" stroke-width="2" d="M8 3v4m8-4v4M3 10h18m-12 4h3m-3 4h6"/></svg>
                 </span>
                 <div class="flex-1 min-w-0">
                     <h3 class="js-titulo text-sm font-bold leading-snug text-heading break-words"></h3>
                     <p class="js-descripcion mt-1 text-sm text-body break-words"></p>
                     <span class="js-hace mt-3 block text-xs text-muted"></span>
                 </div>
             </div>
         </template>

         <template id="notificacion-template-examen-eliminado">
             <div class="js-notificacion relative flex items-start gap-3 sm:gap-4 rounded-2xl border border-light bg-neutral-primary-soft p-4 sm:p-5 shadow-xs">
                 <span class="shrink-0 inline-flex items-center justify-center w-11 h-11 bg-danger-soft rounded-xl">
                     <svg class="w-6 h-6 text-danger" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0 1 16.138 21H7.862a2 2 0 0 1-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 0 0-1-1h-4a1 1 0 0 0-1 1v3M4 7h16"/></svg>
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
               data-marcar-leidas="{{ route('api.notificaciones.leidas') }}">

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
            var endpointLeidas = lista.dataset.marcarLeidas;
            var csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

            var plantillas = {
                posible_tramposo: document.getElementById('notificacion-template-tramposo'),
                central_riesgo: document.getElementById('notificacion-template-central'),
                invitacion_examen: document.getElementById('notificacion-template-invitacion'),
                respuesta_invitacion: document.getElementById('notificacion-template-respuesta'),
                respaldo_bd: document.getElementById('notificacion-template-respaldo'),
                examen_modificado: document.getElementById('notificacion-template-examen'),
                examen_eliminado: document.getElementById('notificacion-template-examen-eliminado')
            };

            var titulos = {
                posible_tramposo: 'Posible Tramposo',
                central_riesgo: 'Estudiante ingresado a central de riesgo',
                invitacion_examen: 'Invitacion para compartir un examen',
                respuesta_invitacion: 'Respuesta a tu invitacion de examen',
                respaldo_bd: 'Respaldo automático de la base de datos',
                examen_modificado: 'Cambios en un examen',
                examen_eliminado: 'Examen eliminado'
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

                if (notificacion.vence_en) {
                    var plazo = document.createElement('p');
                    plazo.className = 'mt-2 text-xs text-muted';
                    nodo.appendChild(plazo);

                    // El aviso se retira de la vista al vencer, sin borrar sus datos.
                    function actualizarPlazo() {
                        var restante = new Date(notificacion.vence_en).getTime() - Date.now();
                        if (restante <= 0) {
                            nodo.remove();
                            actualizarVacio();
                            return;
                        }
                        var horas = Math.ceil(restante / 3600000);
                        plazo.textContent = horas > 24
                            ? 'Esta notificación se ocultará en ' + Math.ceil(horas / 24) + ' días.'
                            : 'Esta notificación se ocultará en ' + horas + (horas === 1 ? ' hora.' : ' horas.');
                        window.setTimeout(actualizarPlazo, Math.min(restante, 60000));
                    }
                    actualizarPlazo();
                }

                return nodo;
            }

            function marcarLeidas() {
                if (!endpointLeidas) {
                    return Promise.resolve();
                }

                return fetch(endpointLeidas, {
                    method: 'PATCH',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    credentials: 'same-origin'
                })
                    .then(function (respuesta) {
                        if (!respuesta.ok) {
                            throw new Error('HTTP ' + respuesta.status);
                        }
                        window.dispatchEvent(new CustomEvent('notificaciones-leidas', { detail: { nuevas: 0 } }));
                    })
                    .catch(function () {
                        // silencioso: el polling del sidebar vuelve a consultar
                    });
            }

            function cargar() {
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

            // El servidor fija la fecha antes de devolver el plazo de cada aviso.
            marcarLeidas().then(cargar);
        })();
    </script>
@endsection
