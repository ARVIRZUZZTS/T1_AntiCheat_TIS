{{--
    @file    examenes-lista.blade.php
    @author  Alex Candia
    @created 2026-11-18
    @updated 2026-11-18
    @updated 2026-11-19

    @description
    Listado exclusivo de la subopción «Ver examenes». No contiene la barra de
    pestañas ni los formularios, selectores o modales de Crear, Editar,
    Eliminar, Invitar o Dividir: esas acciones viven en sus propias páginas del
    submenú.

    Un selector acota la lista a un curso. Cada tarjeta muestra el examen y un
    botón "Ver detalle" que abre un modal con toda su información (tipo, fecha,
    horario, duración, cursos, ambientes, quien lo creó, reglas y materiales
    permitidos), que llega de GET /api/examenes/{examen}/detalle.

    @changelog
    - 2026-11-18  [Alex Candia]  feat: listado propio de "Ver lista de examenes".
    - 2026-11-19  [Valery D. Ortuno P]  refactor: la lista es solo de consulta:
      sale "Eliminar" (queda en la página Eliminar examen) y el código de las
      otras pestañas que no se usaba; entra "Ver detalle" con su modal. Se
      quita el icono de la tarjeta.

    @see  App\Http\Controllers\Api\ExamenDetalleController
    @see  resources/views/pages/examenes.blade.php
--}}

@php
    $curso = $curso ?? null;
    $cursos = $cursos ?? ($curso !== null ? collect([$curso]) : collect());
    // La página siempre lista todos los cursos del docente; el selector de
    // curso de la vista acota el resultado.
    $listadoGlobal = $curso === null;
    $urlListaGlobal = route('api.examenes.listar');
    $urlListaCurso = route('api.cursos.examenes.listar', ['idCurso' => '__CURSO__']);

    $opcionesCursos = $cursos->mapWithKeys(function ($curso) {
        $etiqueta = $curso->nombre_curso;

        if (!empty($curso->es_anfitrion)) {
            $etiqueta .= ' (Anfitrion)';
        }

        return [$curso->id_curso => $etiqueta];
    })->all();
@endphp

<div class="flex flex-col gap-4"
     x-data="{
         cursoFijo: @js((string) ($curso?->id_curso ?? '')),
         urlListaGlobal: @js($urlListaGlobal),
         urlListaCurso: @js($urlListaCurso),
         urlDetalle: @js(route('api.examenes.detalle', ['examen' => '__ID__'])),
         // Vacio = todos los cursos.
         cursoLista: @js($listadoGlobal ? '' : (string) ($curso?->id_curso ?? '')),
         examenes: [],
         estado: 'listo',
         peticion: 0,
         // Modal "Ver detalle".
         modalDetalle: false,
         examenDetalle: null,
         detalle: null,
         cargandoDetalle: false,
         errorDetalle: '',
         peticionDetalle: 0,
         get cursoContexto() {
             return this.cursoLista || this.cursoFijo;
         },
         get urlActual() {
             return this.cursoContexto !== ''
                 ? this.urlListaCurso.replace('__CURSO__', this.cursoContexto)
                 : this.urlListaGlobal;
         },
         async cargar() {
             const peticion = ++this.peticion;

             this.estado = 'cargando';
             try {
                 const respuesta = await fetch(this.urlActual, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' });
                 if (! respuesta.ok) {
                     throw new Error('HTTP ' + respuesta.status);
                 }
                 const cuerpo = await respuesta.json();
                 if (peticion !== this.peticion) {
                     return;
                 }
                 this.examenes = cuerpo.datos ?? [];
                 this.estado = 'listo';
             } catch (error) {
                 if (peticion === this.peticion) {
                     this.estado = 'error';
                 }
             }
         },
         init() {
             this.cargar();
         },
         etiquetaExamen(examen) {
             return [examen.tipo || 'Sin tipo', examen.curso || '']
                 .filter(Boolean)
                 .join(' - ');
         },
         fechaDe(examen) {
             if (! examen.fecha) {
                 return '—';
             }
             const [anio, mes, dia] = examen.fecha.split('-');
             return dia + '/' + mes + '/' + anio;
         },
         horario(examen) {
             return [examen.hora_inicio, examen.hora_fin].filter(Boolean).join(' - ') || '—';
         },
         ambientesDe(examen) {
             return (examen.ambientes || []).map((ambiente) => ambiente.nombre).join(', ') || '—';
         },
         async abrirDetalle(examen) {
             const peticion = ++this.peticionDetalle;

             this.examenDetalle = examen;
             this.detalle = null;
             this.errorDetalle = '';
             this.cargandoDetalle = true;
             this.modalDetalle = true;

             try {
                 const respuesta = await fetch(this.urlDetalle.replace('__ID__', examen.id), {
                     headers: { 'Accept': 'application/json' },
                     credentials: 'same-origin',
                 });
                 const cuerpo = await respuesta.json().catch(() => ({}));
                 if (peticion !== this.peticionDetalle) {
                     return;
                 }
                 if (! respuesta.ok) {
                     this.errorDetalle = cuerpo?.message || 'No se pudo cargar el detalle del examen.';
                     return;
                 }
                 this.detalle = cuerpo.datos ?? null;
             } catch (error) {
                 if (peticion === this.peticionDetalle) {
                     this.errorDetalle = 'No se pudo conectar con el servidor.';
                 }
             } finally {
                 if (peticion === this.peticionDetalle) {
                     this.cargandoDetalle = false;
                 }
             }
         },
         cerrarDetalle() {
             this.modalDetalle = false;
             this.peticionDetalle++;
         },
     }">
    @if (session('mensaje'))
        <x-ui.modal-aviso :mensaje="session('mensaje')" />
    @endif

    {{-- Selector de curso: "Todos los cursos" o uno concreto. --}}
    @if ($listadoGlobal)
        <div class="min-w-0 sm:max-w-xs">
            <label for="examen-curso-lista" class="block mb-2.5 text-sm font-medium text-heading">Curso</label>
            <select id="examen-curso-lista"
                    x-model="cursoLista"
                    @change="cargar()"
                    class="block w-full px-3 py-2.5 border text-sm rounded-base shadow-xs bg-neutral-secondary-medium border-default-medium text-heading focus:ring-brand focus:border-brand">
                <option value="">Todos los cursos</option>
                @foreach ($opcionesCursos as $idCursoLista => $nombreCursoLista)
                    <option value="{{ $idCursoLista }}">{{ $nombreCursoLista }}</option>
                @endforeach
            </select>
        </div>
    @endif

    <div x-show="estado === 'cargando'" x-cloak class="flex flex-col gap-4">
        @foreach ([1, 2] as $placeholder)
            <div class="animate-pulse border border-default bg-neutral-primary-soft rounded-base shadow-xs px-5 py-6 sm:px-8 flex flex-col gap-4 lg:flex-row lg:items-center lg:gap-6" aria-hidden="true">
                <div class="flex flex-col gap-2 lg:w-72">
                    <div class="h-5 w-40 rounded bg-neutral-secondary-medium"></div>
                    <div class="h-4 w-24 rounded-full bg-neutral-secondary-medium"></div>
                </div>
                <div class="grid flex-1 grid-cols-2 gap-4 sm:grid-cols-4">
                    <div class="h-9 w-24 rounded bg-neutral-secondary-medium mx-auto"></div>
                    <div class="h-9 w-24 rounded bg-neutral-secondary-medium mx-auto"></div>
                    <div class="h-9 w-24 rounded bg-neutral-secondary-medium mx-auto"></div>
                    <div class="h-9 w-24 rounded bg-neutral-secondary-medium mx-auto"></div>
                </div>
            </div>
        @endforeach
    </div>

    <div x-show="estado === 'error'" x-cloak>
        <x-ui.alert type="danger" title="No se pudieron cargar los examenes">
            Ocurrio un problema al consultar el listado.
            <div class="mt-2">
                <x-ui.button variant="secondary" @click="cargar()">Reintentar</x-ui.button>
            </div>
        </x-ui.alert>
    </div>

    <ul class="flex flex-col gap-4" x-show="estado === 'listo'" x-cloak>
        <template x-for="examen in examenes" :key="examen.id">
            <li class="flex flex-col gap-4 border border-default bg-neutral-primary-soft rounded-base shadow-xs px-5 py-6 sm:px-8 lg:flex-row lg:items-center lg:gap-6">
                {{-- Nombre con curso y etiquetas. --}}
                <div class="flex min-w-0 flex-col items-start gap-1 lg:w-72 lg:shrink-0">
                    <h3 class="text-xl font-bold text-heading wrap-break-word" x-text="etiquetaExamen(examen)"></h3>
                    <div class="flex flex-wrap gap-2">
                        <span x-show="examen.es_propio" class="inline-flex items-center px-1.5 py-0.5 text-xs font-medium rounded-full border bg-brand-softer border-brand-subtle text-fg-brand-strong">Propio</span>
                        <span x-show="examen.es_anfitrion" class="inline-flex items-center px-1.5 py-0.5 text-xs font-medium rounded-full border bg-warning-soft border-warning-subtle text-fg-warning">Anfitrion</span>
                        <span x-show="!examen.es_propio" class="inline-flex items-center px-1.5 py-0.5 text-xs font-medium rounded-full border bg-success-soft border-success-subtle text-fg-success-strong">Invitado</span>
                        <x-ui.badge type="gray" x-show="examen.estado === 'Programado'">
                            <span x-text="examen.estado"></span>
                        </x-ui.badge>
                        <x-ui.badge type="warning" x-show="examen.estado === 'En curso'">
                            <span x-text="examen.estado"></span>
                        </x-ui.badge>
                        <x-ui.badge type="brand" x-show="examen.estado === 'Finalizado'">
                            <span x-text="examen.estado"></span>
                        </x-ui.badge>
                    </div>
                </div>

                {{-- Datos de la tarjeta. --}}
                <div class="grid flex-1 grid-cols-2 gap-4 sm:grid-cols-4">
                    <div class="flex flex-col items-center gap-1.5 text-center">
                        <span class="text-xs font-semibold uppercase text-muted">Fecha:</span>
                        <time class="text-sm font-mono text-body" :datetime="examen.fecha" x-text="fechaDe(examen)"></time>
                    </div>
                    <div class="flex flex-col items-center gap-1.5 text-center">
                        <span class="text-xs font-semibold uppercase text-muted">Horario:</span>
                        <span class="text-sm font-mono text-body" x-text="horario(examen)"></span>
                    </div>
                    <div class="flex flex-col items-center gap-1.5 text-center">
                        <span class="text-xs font-semibold uppercase text-muted">Ambientes:</span>
                        <span class="text-sm font-mono text-body" x-text="ambientesDe(examen)"></span>
                    </div>
                    <div class="flex flex-col items-center gap-1.5 text-center">
                        <span class="text-xs font-semibold uppercase text-muted">Ingresos:</span>
                        <span class="text-sm font-bold text-heading" x-text="examen.ingresados"></span>
                    </div>
                </div>

                <div class="flex shrink-0 justify-end">
                    <x-ui.button variant="secondary" class="w-full justify-center sm:w-auto" @click="abrirDetalle(examen)">
                        Ver detalle
                    </x-ui.button>
                </div>
            </li>
        </template>

        <li x-show="examenes.length === 0"
            class="flex flex-col items-center gap-2 p-8 text-center bg-neutral-primary-soft border border-default rounded-base shadow-xs">
            <p class="text-sm text-body" x-text="cursoContexto !== '' ? 'No hay examenes programados' : 'No hay examenes registrados.'"></p>
        </li>
    </ul>

    {{-- Modal "Ver detalle": datos del examen, reglas y materiales. --}}
    <div x-show="modalDetalle" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-overlay-modal/50 p-4"
         role="dialog" aria-modal="true" aria-labelledby="titulo-detalle-examen"
         @click.self="cerrarDetalle()" @keydown.escape.window="modalDetalle && cerrarDetalle()">
        <div class="w-full max-w-2xl max-h-full overflow-y-auto rounded-base border border-default bg-neutral-primary-soft shadow-sm">
            <div class="flex items-start justify-between gap-3 border-b border-default p-4 md:p-5">
                <div class="min-w-0">
                    <h3 id="titulo-detalle-examen" class="text-lg font-semibold text-heading wrap-break-word"
                        x-text="examenDetalle ? etiquetaExamen(examenDetalle) : 'Detalle del examen'"></h3>
                    <p class="text-sm text-muted" x-show="examenDetalle?.estado" x-text="examenDetalle?.estado"></p>
                </div>
                <button type="button" @click="cerrarDetalle()" class="shrink-0 text-body bg-transparent hover:bg-neutral-tertiary hover:text-heading rounded-base text-sm w-9 h-9 inline-flex justify-center items-center cursor-pointer">
                    <svg class="w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 17.94 6M18 18 6.06 6"/></svg>
                    <span class="sr-only">Cerrar</span>
                </button>
            </div>

            <div class="p-4 md:p-5">
                <p x-show="cargandoDetalle" class="py-6 text-center text-sm text-body">Cargando detalle...</p>
                <p x-show="! cargandoDetalle && errorDetalle !== ''" x-text="errorDetalle" class="py-6 text-center text-sm text-fg-danger-strong"></p>

                <div x-show="! cargandoDetalle && detalle" class="space-y-5">
                    {{-- Datos generales. --}}
                    <dl class="grid grid-cols-1 gap-x-6 gap-y-3 text-sm sm:grid-cols-2">
                        <div>
                            <dt class="text-xs font-semibold uppercase text-muted">Tipo</dt>
                            <dd class="mt-0.5 text-heading" x-text="detalle?.tipo || '—'"></dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase text-muted">Fecha</dt>
                            <dd class="mt-0.5 text-heading" x-text="detalle?.fecha || '—'"></dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase text-muted">Horario</dt>
                            <dd class="mt-0.5 text-heading" x-text="[detalle?.hora_inicio, detalle?.hora_fin].filter(Boolean).join(' - ') || '—'"></dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase text-muted">Duracion</dt>
                            <dd class="mt-0.5 text-heading" x-text="detalle?.duracion ? detalle.duracion + ' minutos' : '—'"></dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase text-muted">Creado por</dt>
                            <dd class="mt-0.5 text-heading" x-text="detalle?.creador || '—'"></dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase text-muted">Ingresos</dt>
                            <dd class="mt-0.5 text-heading" x-text="examenDetalle?.ingresados ?? 0"></dd>
                        </div>
                        <div class="sm:col-span-2">
                            <dt class="text-xs font-semibold uppercase text-muted">Ambientes</dt>
                            <dd class="mt-0.5 text-heading" x-text="(detalle?.ambientes || []).join(', ') || 'Sin ambientes asignados'"></dd>
                        </div>
                    </dl>

                    {{-- Cursos con su docente. --}}
                    <section>
                        <h4 class="text-sm font-semibold text-heading">Cursos</h4>
                        <ul class="mt-2 space-y-1 text-sm text-body" x-show="(detalle?.cursos || []).length > 0">
                            <template x-for="(cursoDetalle, indice) in (detalle?.cursos || [])" :key="indice">
                                <li>
                                    <span class="text-heading" x-text="cursoDetalle.nombre"></span>
                                    <span x-show="cursoDetalle.docente" x-text="'(' + cursoDetalle.docente + ')'"></span>
                                </li>
                            </template>
                        </ul>
                        <p class="mt-2 text-sm text-muted" x-show="(detalle?.cursos || []).length === 0">Sin cursos asignados</p>
                    </section>

                    {{-- Reglas del examen. --}}
                    <section>
                        <h4 class="text-sm font-semibold text-heading">Reglas del examen</h4>
                        <ol class="mt-2 list-decimal space-y-1 ps-5 text-sm text-body" x-show="(detalle?.normas || []).length > 0">
                            <template x-for="(norma, indice) in (detalle?.normas || [])" :key="indice">
                                <li class="wrap-break-word" x-text="norma"></li>
                            </template>
                        </ol>
                        <p class="mt-2 text-sm text-muted" x-show="(detalle?.normas || []).length === 0">Sin reglas registradas</p>
                    </section>

                    {{-- Materiales permitidos. --}}
                    <section>
                        <h4 class="text-sm font-semibold text-heading">Materiales permitidos</h4>
                        <ul class="mt-2 list-disc space-y-1 ps-5 text-sm text-body" x-show="(detalle?.materiales || []).length > 0">
                            <template x-for="(material, indice) in (detalle?.materiales || [])" :key="indice">
                                <li class="wrap-break-word" x-text="material"></li>
                            </template>
                        </ul>
                        <p class="mt-2 text-sm text-muted" x-show="(detalle?.materiales || []).length === 0">Sin materiales permitidos</p>
                    </section>
                </div>
            </div>

            <div class="flex justify-end border-t border-default p-4 md:p-5">
                <x-ui.button class="w-full justify-center sm:w-auto" @click="cerrarDetalle()">Aceptar</x-ui.button>
            </div>
        </div>
    </div>
</div>
