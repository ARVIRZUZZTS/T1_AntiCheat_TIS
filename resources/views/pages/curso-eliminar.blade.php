{{--
    @file    curso-eliminar.blade.php
    @author  Alex Candia
    @created 2026-11-16
    @updated 2026-11-16

    @description
    Página "Eliminar curso" (subopción del sidebar, debajo de "Editar curso"):
    la tabla de cursos activos del docente autenticado. Cada fila muestra el
    curso, su gestión, la cantidad de estudiantes y su botón "Eliminar".

    Al tocar el botón se abre un modal que pide los auxiliares y estudiantes
    afectados al endpoint `cursos.eliminar.afectados`, los muestra como
    advertencia y confirma con los botones Eliminar / Cancelar. Al confirmar
    envía DELETE a `cursos.destroy` con fetch, quita la fila de la lista y
    muestra el aviso "Curso eliminado con exito" sin recargar la página. El
    borrado también elimina los exámenes del curso con
    EliminarExamenCursoService. Si se elimina el último curso, la lista muestra
    "No tienes cursos registrados". Cancelar, la X, Esc o un clic fuera cierran
    el modal sin cambios.

    @changelog
    - 2026-11-16  [Alex Candia]  feat: lista de cursos con botón de eliminación.
    - 2026-11-16  [Alex Candia]  feat: modal de confirmación con conteo de
      auxiliares y estudiantes, borrado por fetch y actualización de la lista.

    @see  App\Http\Controllers\CursoController::eliminar()
    @see  App\Http\Controllers\CursoController::afectados()
    @see  App\Http\Controllers\CursoController::destroy()
    @see  App\Services\Curso\EliminarCursoService
    @see  App\Services\Examen\EliminarExamenCursoService
--}}

@extends('layouts.app')

@section('title', 'Eliminar curso')

@section('content')
    <div
        x-data="{
            modalAbierto: false,
            cursoEliminar: null,
            auxiliares: 0,
            estudiantes: 0,
            cargandoAfectados: false,
            eliminando: false,
            error: '',
            eliminados: [],
            totalCursos: {{ $cursos->count() }},
            toastVisible: false,
            mensajeExito: '',
            temporizadorToast: null,
            solicitudAfectados: 0,

            abrirModal(datos) {
                this.cursoEliminar = {
                    id: Number(datos.cursoId),
                    nombre: datos.cursoNombre,
                    urlAfectados: datos.urlAfectados,
                    urlEliminar: datos.urlEliminar,
                };
                this.modalAbierto = true;
                this.auxiliares = 0;
                this.estudiantes = 0;
                this.error = '';
                this.cargandoAfectados = true;

                const solicitud = ++this.solicitudAfectados;

                fetch(this.cursoEliminar.urlAfectados, {
                    headers: { 'Accept': 'application/json' },
                    credentials: 'same-origin',
                })
                    .then((respuesta) => {
                        if (! respuesta.ok) {
                            throw new Error('No se pudieron cargar los afectados.');
                        }

                        return respuesta.json();
                    })
                    .then((datos) => {
                        if (solicitud !== this.solicitudAfectados) {
                            return;
                        }

                        this.auxiliares = Number(datos.auxiliares) || 0;
                        this.estudiantes = Number(datos.estudiantes) || 0;
                    })
                    .catch((fallo) => {
                        if (solicitud !== this.solicitudAfectados) {
                            return;
                        }

                        this.error = fallo.message || 'No se pudieron cargar los afectados.';
                    })
                    .finally(() => {
                        if (solicitud === this.solicitudAfectados) {
                            this.cargandoAfectados = false;
                        }
                    });
            },

            cerrarModal() {
                if (this.eliminando) {
                    return;
                }

                this.solicitudAfectados++;
                this.modalAbierto = false;
                this.cursoEliminar = null;
                this.auxiliares = 0;
                this.estudiantes = 0;
                this.cargandoAfectados = false;
                this.error = '';
            },

            async confirmarEliminacion() {
                if (! this.cursoEliminar || this.eliminando) {
                    return;
                }

                this.eliminando = true;
                this.error = '';

                try {
                    const respuesta = await fetch(this.cursoEliminar.urlEliminar, {
                        method: 'DELETE',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').getAttribute('content'),
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        credentials: 'same-origin',
                    });

                    const datos = await respuesta.json().catch(() => ({}));

                    if (! respuesta.ok) {
                        if (respuesta.status === 403) {
                            this.error = 'No tienes permiso para eliminar este curso.';
                        } else if (respuesta.status === 404) {
                            this.error = 'El curso ya no existe.';
                        } else {
                            this.error = datos.message || datos.mensaje || 'No se pudo eliminar el curso.';
                        }

                        return;
                    }

                    this.eliminados.push(this.cursoEliminar.id);
                    this.modalAbierto = false;
                    this.cursoEliminar = null;
                    this.mostrarExito(datos.mensaje || 'Curso eliminado con exito');
                } catch (fallo) {
                    this.error = 'No se pudo eliminar el curso.';
                } finally {
                    this.eliminando = false;
                }
            },

            mostrarExito(mensaje) {
                this.mensajeExito = mensaje;
                this.toastVisible = true;

                clearTimeout(this.temporizadorToast);
                this.temporizadorToast = setTimeout(() => {
                    this.toastVisible = false;
                }, 4000);
            },

            textoAuxiliares() {
                return this.auxiliares === 1 ? '1 auxiliar' : this.auxiliares + ' auxiliares';
            },

            textoEstudiantes() {
                return this.estudiantes === 1 ? '1 estudiante' : this.estudiantes + ' estudiantes';
            },
        }"
        @keydown.escape.window="cerrarModal()"
        class="px-4 py-4 sm:px-6 sm:py-6 lg:px-8"
    >
        @if ($cursos->isEmpty())
            <p class="mx-auto max-w-2xl rounded-base border border-default bg-neutral-primary-soft p-8 text-center text-sm text-body shadow-xs">
                No tienes cursos activos para eliminar.
            </p>
        @else
            <x-ui.table compact :headers="['CURSO', 'GESTIÓN', 'ESTUDIANTES', 'ACCIONES']">
                <x-slot:body>
                    @foreach ($cursos as $curso)
                        <tr wire:key="curso-eliminar-{{ $curso->id_curso }}"
                            x-show="! eliminados.includes({{ $curso->id_curso }})"
                            @class(['bg-neutral-primary', 'border-b border-default' => ! $loop->last])>
                            <th scope="row" class="px-4 py-2.5 font-medium text-heading">
                                <span class="block wrap-break-word">{{ $curso->nombre_curso }}</span>
                            </th>
                            <td class="px-4 py-2.5 text-body">{{ $curso->gestion ?? 'Gestión no registrada' }}</td>
                            <td class="px-4 py-2.5 text-body">{{ $curso->estudiantes_count }}</td>
                            <td class="px-4 py-2.5">
                                <button
                                    type="button"
                                    class="inline-flex min-h-11 shrink-0 items-center px-2 text-sm font-medium text-fg-danger-strong underline-offset-2 hover:underline focus:rounded-base focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-medium"
                                    data-eliminar-curso="{{ $curso->id_curso }}"
                                    data-curso-id="{{ $curso->id_curso }}"
                                    data-curso-nombre="{{ $curso->nombre_curso }}"
                                    data-url-afectados="{{ route('cursos.eliminar.afectados', $curso) }}"
                                    data-url-eliminar="{{ route('cursos.destroy', $curso) }}"
                                    aria-label="Eliminar curso {{ $curso->nombre_curso }}"
                                    aria-haspopup="dialog"
                                    @click="abrirModal($el.dataset)"
                                >
                                    Eliminar
                                </button>
                            </td>
                        </tr>
                    @endforeach

                    <tr x-show="eliminados.length === totalCursos" x-cloak>
                        <td colspan="4" class="px-4 py-8 text-center text-sm text-body">
                            No tienes cursos activos para eliminar.
                        </td>
                    </tr>
                </x-slot:body>
            </x-ui.table>
        @endif

        {{-- Aviso de éxito sin recargar la página. --}}
        <div
            x-show="toastVisible"
            x-cloak
            x-transition.opacity
            role="status"
            aria-live="polite"
            class="fixed top-4 end-4 z-[60] w-[calc(100%-2rem)] max-w-sm"
        >
            <div class="flex items-center gap-3 rounded-base border border-default bg-neutral-primary-soft p-4 shadow-xs">
                <span class="inline-flex shrink-0 items-center justify-center w-8 h-8 rounded bg-success-soft text-fg-success-strong">
                    <svg class="w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 11.917 9.724 16.5 19 7.5"/></svg>
                </span>

                <p x-text="mensajeExito" class="min-w-0 flex-1 text-sm text-heading"></p>

                <button
                    type="button"
                    @click="toastVisible = false"
                    class="inline-flex shrink-0 items-center justify-center w-8 h-8 rounded-base text-body hover:bg-neutral-secondary-medium hover:text-heading"
                >
                    <span class="sr-only">Cerrar aviso</span>
                    <svg class="w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 17.94 6M18 18 6.06 6"/></svg>
                </button>
            </div>
        </div>

        {{-- Modal de confirmación. --}}
        <div
            x-show="modalAbierto"
            x-cloak
            tabindex="-1"
            role="dialog"
            aria-modal="true"
            aria-labelledby="titulo-eliminar-curso"
            @click.self="cerrarModal()"
            class="overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 flex justify-center items-center w-full md:inset-0 h-full max-h-full bg-overlay-modal/50"
        >
            <div class="relative p-4 w-full max-w-md max-h-full">
                <div class="relative bg-neutral-primary-soft border border-default rounded-base shadow-sm p-4 md:p-6">
                    <div class="flex items-center justify-between border-b border-default pb-4 md:pb-5">
                        <h3 id="titulo-eliminar-curso" class="text-lg font-medium text-heading">
                            Eliminar curso
                        </h3>

                        <button
                            type="button"
                            @click="cerrarModal()"
                            x-bind:disabled="eliminando"
                            class="text-body bg-transparent hover:bg-neutral-tertiary hover:text-heading rounded-base text-sm w-9 h-9 ms-auto inline-flex justify-center items-center cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <svg class="w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 17.94 6M18 18 6.06 6"/></svg>
                            <span class="sr-only">Cerrar modal</span>
                        </button>
                    </div>

                    <div class="space-y-4 py-4">
                        <p class="text-sm text-body">¿Seguro que quieres eliminar este curso?</p>

                        <p x-text="cursoEliminar?.nombre" class="text-sm font-semibold text-heading break-words"></p>

                        <p class="text-sm text-fg-danger-strong">
                            Esta acción no se puede deshacer.
                        </p>

                        <p class="text-sm text-body">
                            Los exámenes de este curso también se eliminarán.
                        </p>

                        <p x-show="cargandoAfectados" x-cloak class="text-sm text-body">
                            Cargando auxiliares y estudiantes afectados…
                        </p>

                        <div
                            x-show="! cargandoAfectados && ! error && (auxiliares > 0 || estudiantes > 0)"
                            x-cloak
                            class="rounded-base border border-danger-subtle bg-danger-soft p-3 text-sm text-fg-danger-strong"
                        >
                            <p class="font-medium">Se verán afectados:</p>
                            <ul class="mt-2 space-y-1">
                                <li x-show="auxiliares > 0" x-text="textoAuxiliares()"></li>
                                <li x-show="estudiantes > 0" x-text="textoEstudiantes()"></li>
                            </ul>
                        </div>

                        <p
                            x-show="! cargandoAfectados && ! error && auxiliares === 0 && estudiantes === 0"
                            x-cloak
                            class="text-sm text-body"
                        >
                            Este curso no tiene auxiliares ni estudiantes asignados.
                        </p>

                        <p x-show="error" x-text="error" x-cloak role="alert" class="text-sm font-medium text-fg-danger-strong"></p>
                    </div>

                    <div class="flex flex-col gap-3 border-t border-default pt-4 sm:flex-row sm:items-center">
                        <x-ui.button
                            variant="danger"
                            class="w-full justify-center disabled:opacity-50 disabled:cursor-not-allowed sm:w-auto"
                            x-bind:disabled="cargandoAfectados || eliminando"
                            @click="confirmarEliminacion()"
                        >
                            <span x-text="eliminando ? 'Eliminando…' : 'Eliminar'"></span>
                        </x-ui.button>

                        <x-ui.button-cancelar
                            class="w-full justify-center disabled:opacity-50 disabled:cursor-not-allowed sm:w-auto"
                            x-bind:disabled="eliminando"
                            @click="cerrarModal()"
                        >
                            Cancelar
                        </x-ui.button-cancelar>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
