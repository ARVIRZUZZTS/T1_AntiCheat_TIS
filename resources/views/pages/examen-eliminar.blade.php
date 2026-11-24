{{--
    @file    examen-eliminar.blade.php
    @author  OchoaCesar <cesareduardonick@gmail.com>
    @created 2026-11-20
    @updated 2026-11-20

    @description
    Página "Eliminar examen" (subopción del sidebar, debajo de "Editar
    examen"): la tabla de exámenes programados del docente. Cada fila muestra
    el examen, su fecha, el curso gestionado, la cantidad de inscritos y el
    botón "Eliminar".

    Al tocar "Eliminar" se valida en pantalla: si el examen ya inició o
    finalizó, o tiene ingresos o incidencias, se muestra el aviso de bloqueo
    dentro de la misma tarjeta y no se abre el modal. Si pasa, se abre el
    modal "Eliminar examen" que
    confirma con los botones Eliminar / Cancelar y envía DELETE a
    `api.examenes.cursos.desvincular`, desvinculando el examen solo del curso
    elegido. El éxito se muestra en el modal "Examen eliminado con exito" y el
    chip del curso (o la tarjeta completa) se quita de la lista sin recargar.

    @changelog
    - 2026-11-20  [OchoaCesar]  feat: página con la lista de exámenes, botón
      de eliminación por curso propio, bloqueo previo y modales de
      confirmación y éxito.
    - 2026-11-20  [OchoaCesar]  fix: la tarjeta queda solo con el título y
      su etiqueta de estado, con el botón "Eliminar" a la derecha; se quitan
      el icono de documento y los datos de fecha, horario, ambientes e
      ingresos.
    - 2026-11-20  [OchoaCesar]  fix: el aviso de bloqueo se muestra dentro
      de la tarjeta presionada; antes iba al final de la lista y no se veía
      al hacer clic.

    @see  App\Http\Controllers\ExamenController::eliminarVista()
    @see  App\Services\Examen\ListarExamenesDocenteService
    @see  App\Services\Examen\EliminarExamenCursoService
    @see  resources/views/pages/curso-eliminar.blade.php
--}}

@extends('layouts.app')

@section('title', 'Eliminar examen')

@section('content')
    @php
        $totalVinculos = array_sum(array_map(fn (array $examen): int => count($examen['cursos_propios']), $examenes));
        $totalGestionables = count(array_filter($examenes, fn (array $examen): bool => $examen['cursos_propios'] !== []));
        $soloLectura = count($examenes) - $totalGestionables;
    @endphp

    <div
        x-data="{
            ahora: @js($ahora),
            csrfToken: document.querySelector('meta[name=csrf-token]')?.getAttribute('content') || '',
            modalEliminar: false,
            examenEliminar: null,
            avisoEliminacion: '',
            avisoClave: '',
            errorEliminacion: '',
            exitoEliminacion: false,
            eliminando: false,
            vinculosEliminados: [],
            totalVinculos: {{ $totalVinculos }},
            soloLectura: {{ $soloLectura }},
            mensajeNoIniciado: 'No se puede eliminar un examen que ya inició o finalizó',
            mensajeConDatos: 'No se puede eliminar un examen con ingresos o incidencias registradas',

            visibleFila(datos) {
                return ! this.vinculosEliminados.includes(String(datos.clave || ''));
            },

            motivoBloqueoEliminar(datos) {
                if (datos.fecha) {
                    const inicio = datos.fecha + ' ' + (datos.horaInicio || '00:00');
                    if (inicio <= this.ahora) {
                        return this.mensajeNoIniciado;
                    }
                }

                if (Number(datos.ingresados || 0) > 0 || Number(datos.incidencias || 0) > 0) {
                    return this.mensajeConDatos;
                }

                return '';
            },

            abrirModalEliminar(datos) {
                this.avisoEliminacion = '';
                this.avisoClave = '';
                this.errorEliminacion = '';

                const motivo = this.motivoBloqueoEliminar(datos);
                if (motivo !== '') {
                    this.avisoEliminacion = motivo;
                    this.avisoClave = String(datos.examenId) + ':' + String(datos.cursoId);
                    return;
                }

                this.examenEliminar = {
                    id: String(datos.examenId),
                    nombre: datos.examenNombre,
                    cursoId: String(datos.cursoId),
                    cursoNombre: datos.cursoNombre,
                };
                this.modalEliminar = true;
            },

            cerrarModalEliminar() {
                if (this.eliminando) {
                    return;
                }

                this.modalEliminar = false;
                this.examenEliminar = null;
                this.errorEliminacion = '';
            },

            async confirmarEliminar() {
                if (! this.examenEliminar || this.eliminando) {
                    return;
                }

                this.eliminando = true;
                this.errorEliminacion = '';

                try {
                    const respuesta = await fetch('/api/examenes/' + this.examenEliminar.id + '/cursos/' + this.examenEliminar.cursoId, {
                        method: 'DELETE',
                        credentials: 'same-origin',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': this.csrfToken,
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });

                    const cuerpo = await respuesta.json().catch(() => ({}));

                    if (! respuesta.ok) {
                        this.errorEliminacion = respuesta.status === 403
                            ? 'No tienes permiso para eliminar este examen de ese curso.'
                            : (cuerpo?.errors?.eliminar?.[0]
                                || cuerpo?.message
                                || 'No se pudo eliminar el examen.');
                        return;
                    }

                    const clave = this.examenEliminar.id + ':' + this.examenEliminar.cursoId;

                    if (! this.vinculosEliminados.includes(clave)) {
                        this.vinculosEliminados.push(clave);
                    }

                    this.modalEliminar = false;
                    this.examenEliminar = null;
                    this.exitoEliminacion = true;
                } catch (error) {
                    this.errorEliminacion = 'No se pudo conectar con el servidor.';
                } finally {
                    this.eliminando = false;
                }
            },

            cerrarConEscape() {
                if (this.exitoEliminacion) {
                    this.exitoEliminacion = false;
                    return;
                }

                this.cerrarModalEliminar();
            },
        }"
        @keydown.escape.window="cerrarConEscape()"
        class="px-4 py-4 sm:px-6 sm:py-6 lg:px-8"
    >
        <x-ui.table compact :headers="['EXAMEN', 'CURSO', 'INSCRITOS', 'ACCIONES']">
            <x-slot:body>
                @forelse ($examenes as $examen)
                    @foreach ($examen['cursos_propios'] as $cursoPropio)
                        @php
                            $clave = $examen['id'].':'.$cursoPropio['id'];
                            $etiqueta = $examen['tipo'] ?: 'Sin tipo';
                            $fecha = $examen['fecha'] ?? '';
                        @endphp
                        <tr x-show="visibleFila($el.dataset)" x-cloak data-clave="{{ $clave }}"
                            @class(['bg-neutral-primary', 'border-b border-default' => ! $loop->last])>
                            <th scope="row" class="px-4 py-2.5 font-medium text-heading">
                                <span class="block wrap-break-word">{{ $etiqueta }}</span>
                                <span class="mt-0.5 block text-xs text-body">
                                    {{ $fecha !== '' ? \Illuminate\Support\Carbon::parse($fecha)->format('d/m/Y') : 'Sin fecha' }}
                                </span>
                                <div x-show="avisoClave === '{{ $clave }}'" x-cloak class="mt-2">
                                    <x-ui.alert type="danger" title="No se puede eliminar el examen">
                                        <span x-text="avisoEliminacion"></span>
                                    </x-ui.alert>
                                </div>
                            </th>
                            <td class="px-4 py-2.5 text-body">{{ $cursoPropio['nombre'] }}</td>
                            <td class="px-4 py-2.5 text-body">{{ $examen['inscritos'] }}</td>
                            <td class="px-4 py-2.5">
                                <button
                                    type="button"
                                    class="inline-flex min-h-11 shrink-0 items-center px-2 text-sm font-medium text-fg-danger-strong underline-offset-2 hover:underline focus:rounded-base focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-medium"
                                    data-examen-id="{{ $examen['id'] }}"
                                    data-examen-nombre="{{ $etiqueta }}"
                                    data-curso-id="{{ $cursoPropio['id'] }}"
                                    data-curso-nombre="{{ $cursoPropio['nombre'] }}"
                                    data-clave="{{ $clave }}"
                                    data-fecha="{{ $fecha }}"
                                    data-hora-inicio="{{ $examen['hora_inicio'] ?? '' }}"
                                    data-ingresados="{{ $examen['ingresados'] }}"
                                    data-incidencias="{{ $examen['incidencias'] }}"
                                    aria-label="Eliminar examen del curso {{ $cursoPropio['nombre'] }}"
                                    aria-haspopup="dialog"
                                    @click="abrirModalEliminar($el.dataset)"
                                >
                                    Eliminar
                                </button>
                            </td>
                        </tr>
                    @endforeach
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-8 text-center text-sm text-body">
                            No tienes exámenes programados para eliminar.
                        </td>
                    </tr>
                @endforelse

                <tr x-show="soloLectura === 0 && totalVinculos > 0 && vinculosEliminados.length === totalVinculos"
                    x-cloak>
                    <td colspan="4" class="px-4 py-8 text-center text-sm text-body">
                        No tienes exámenes programados para eliminar.
                    </td>
                </tr>
            </x-slot:body>
        </x-ui.table>

        {{-- Modal de confirmación. --}}
        <div
            x-show="modalEliminar"
            x-cloak
            tabindex="-1"
            role="dialog"
            aria-modal="true"
            aria-labelledby="titulo-eliminar-examen"
            @click.self="cerrarModalEliminar()"
            class="overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 flex justify-center items-center w-full md:inset-0 h-full max-h-full bg-overlay-modal/50"
        >
            <div class="relative p-4 w-full max-w-md max-h-full">
                <div class="relative bg-neutral-primary-soft border border-default rounded-base shadow-sm p-4 md:p-6">
                    <div class="flex items-start justify-between gap-3 border-b border-default pb-4">
                        <div>
                            <h3 id="titulo-eliminar-examen" class="text-lg font-medium text-heading">Eliminar examen</h3>
                            <p class="text-sm text-muted">El examen dejara de aparecer en la lista de este curso.</p>
                        </div>

                        <button
                            type="button"
                            @click="cerrarModalEliminar()"
                            x-bind:disabled="eliminando"
                            class="text-body bg-transparent hover:bg-neutral-tertiary hover:text-heading rounded-base text-sm w-9 h-9 ms-auto inline-flex justify-center items-center cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <svg class="w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 17.94 6M18 18 6.06 6"/></svg>
                            <span class="sr-only">Cerrar modal</span>
                        </button>
                    </div>

                    <div class="py-4 space-y-3">
                        <p class="text-sm text-body">Se eliminara el examen del curso.</p>
                        <p class="text-sm font-medium text-fg-danger-strong">Esta accion no se puede deshacer.</p>
                        <p class="text-sm font-semibold text-heading" x-text="examenEliminar?.nombre || ''"></p>
                        <p class="text-sm text-body" x-text="examenEliminar ? 'Curso: ' + examenEliminar.cursoNombre : ''"></p>
                        <p x-show="errorEliminacion !== ''" x-text="errorEliminacion" x-cloak role="alert" class="text-sm font-medium text-fg-danger-strong"></p>
                    </div>

                    <div class="border-t border-default pt-4 flex flex-col gap-3 sm:flex-row sm:justify-start">
                        <x-ui.button
                            variant="danger"
                            class="w-full justify-center disabled:opacity-50 disabled:cursor-not-allowed sm:w-auto"
                            x-bind:disabled="eliminando"
                            @click="confirmarEliminar()"
                        >
                            <span x-text="eliminando ? 'Eliminando...' : 'Eliminar'"></span>
                        </x-ui.button>

                        <x-ui.button-cancelar
                            class="w-full justify-center disabled:opacity-50 disabled:cursor-not-allowed sm:w-auto"
                            @click="cerrarModalEliminar()"
                        >
                            Cancelar
                        </x-ui.button-cancelar>
                    </div>
                </div>
            </div>
        </div>

        {{-- Modal de éxito de eliminación. --}}
        <div
            x-show="exitoEliminacion"
            x-cloak
            role="dialog"
            aria-modal="true"
            aria-labelledby="titulo-exito-eliminar"
            @click.self="exitoEliminacion = false"
            class="overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 flex justify-center items-center w-full md:inset-0 h-full max-h-full bg-overlay-modal/50"
        >
            <div class="relative p-4 w-full max-w-md max-h-full">
                <div class="relative bg-neutral-primary-soft border border-default rounded-base shadow-sm p-4 md:p-6 text-center">
                    <h3 id="titulo-exito-eliminar" class="text-lg font-medium text-heading">Examen eliminado con exito</h3>
                    <p class="mt-2 text-sm text-body">El examen dejo de aparecer en la lista de este curso.</p>
                    <div class="mt-4">
                        <button
                            type="button"
                            @click="exitoEliminacion = false"
                            class="inline-flex items-center justify-center rounded-base border border-transparent bg-brand px-4 py-2 text-sm font-medium text-white hover:bg-brand-strong"
                        >
                            Aceptar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
