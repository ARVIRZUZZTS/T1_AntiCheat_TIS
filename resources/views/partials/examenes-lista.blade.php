{{--
    @file    examenes-lista.blade.php
    @author  Alex Candia
    @created 2026-11-18
    @updated 2026-11-18
    @updated 2026-11-19
    @updated 2026-11-20
    @updated 2026-11-22

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
      otras pestañas que no se usaba; entra "Ver detalle" con su modal, con el
      mismo formato que el detalle de la central de riesgos (docentes del
      examen con sus cursos, tipo con mayuscula y reglas con viñetas). Se
      quita el icono de la tarjeta.
    - 2026-11-19  [Valery D. Ortuno P]  perf: el detalle abre al instante con
      los datos de la tarjeta y solo espera docentes, reglas y materiales; se
      pide al pasar el mouse por "Ver detalle" y queda guardado para reabrirlo.
    - 2026-11-22  [Valery D. Ortuno P]  fix: el selector de curso muestra solo el
      nombre; se quita "(Anfitrion)", que es de un examen compartido y no de un
      curso.
    - 2026-11-20  [OchoaCesar]  refactor: la eliminación de examen pasa a la
      página propia /examenes/eliminar (subopción del sidebar); se retiran de
      aquí el botón "Eliminar" de la tarjeta, el aviso de bloqueo y sus
      modales.

    @see  App\Http\Controllers\Api\ExamenDetalleController
    @see  resources/views/pages/examenes.blade.php
--}}

@php
    $curso = $curso ?? null;
    $cursos = $cursos ?? ($curso !== null ? collect([$curso]) : collect());
    // La página lista solo cursos activos con examenes; el selector de la vista acota el resultado.
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

    $pillBase = 'shrink-0 whitespace-nowrap inline-flex items-center justify-center box-border border focus:ring-4 shadow-xs font-medium leading-5 rounded-full focus:outline-none px-4 py-2.5 text-sm';
    $pillActiva = 'text-white bg-brand border-transparent hover:bg-brand-strong focus:ring-brand-medium';
    $pillInactiva = 'text-body bg-neutral-primary-soft border-default hover:bg-neutral-secondary-medium hover:text-heading focus:ring-neutral-tertiary-soft';
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
         filtroTiempo: 'todos',
         esPasado(examen) {
             return examen?.estado === 'Finalizado';
         },
         get conteo() {
             const pasados = this.examenes.filter((examen) => this.esPasado(examen)).length;

             return { todos: this.examenes.length, proximos: this.examenes.length - pasados, pasados };
         },
         get examenesFiltrados() {
             return this.examenes.filter((examen) => {
                 if (this.filtroTiempo === 'pasados') {
                     return this.esPasado(examen);
                 }
                 if (this.filtroTiempo === 'proximos') {
                     return ! this.esPasado(examen);
                 }

                 return true;
             });
         },
         // Modal Ver detalle.
         modalDetalle: false,
         examenDetalle: null,
         detalle: null,
         cargandoDetalle: false,
         errorDetalle: '',
         peticionDetalle: 0,
         // Detalles ya traidos (por id) y pedidos en curso: reabrir un examen
         // es instantaneo y pasar el mouse por el enlace adelanta la consulta.
         detalles: {},
         pedidos: {},
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
                 this.detalles = {};
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
         tipoDe(examen) {
             return examen?.tipo ? examen.tipo.charAt(0).toUpperCase() + examen.tipo.slice(1) : 'Sin tipo';
         },
         etiquetaExamen(examen) {
             return [this.tipoDe(examen), examen.curso || '']
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
         pedirDetalle(id) {
             if (this.detalles[id]) {
                 return Promise.resolve(this.detalles[id]);
             }
             if (! this.pedidos[id]) {
                 this.pedidos[id] = fetch(this.urlDetalle.replace('__ID__', id), {
                     headers: { 'Accept': 'application/json' },
                     credentials: 'same-origin',
                 })
                     .then(async (respuesta) => {
                         const cuerpo = await respuesta.json().catch(() => ({}));
                         if (! respuesta.ok) {
                             throw new Error(cuerpo?.message || 'No se pudo cargar el detalle del examen.');
                         }
                         this.detalles[id] = cuerpo.datos;

                         return cuerpo.datos;
                     })
                     .finally(() => {
                         delete this.pedidos[id];
                     });
             }

             return this.pedidos[id];
         },
         precargar(examen) {
             this.pedirDetalle(String(examen.id)).catch(() => {});
         },
         async abrirDetalle(examen) {
             const id = String(examen.id);
             const peticion = ++this.peticionDetalle;

             // El modal abre ya con los datos de la tarjeta; solo espera lo
             // que la lista no trae (duracion, docentes, reglas y materiales).
             this.examenDetalle = examen;
             this.detalle = this.detalles[id] || null;
             this.errorDetalle = '';
             this.cargandoDetalle = this.detalle === null;
             this.modalDetalle = true;

             if (this.detalle !== null) {
                 return;
             }

             try {
                 const datos = await this.pedirDetalle(id);
                 if (peticion === this.peticionDetalle) {
                     this.detalle = datos;
                 }
             } catch (error) {
                 if (peticion === this.peticionDetalle) {
                     this.errorDetalle = error.message || 'No se pudo conectar con el servidor.';
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

    {{-- Barra blanca: selector y píldoras de filtro. --}}
    <div class="mt-4 bg-neutral-primary-soft border border-default rounded-base shadow-xs p-3 flex flex-col gap-3 sm:flex-row sm:items-center">
        @if ($listadoGlobal)
            <div class="min-w-0 sm:w-56">
                <label for="examen-curso-lista" class="block mb-2.5 text-sm font-medium text-heading sm:sr-only">Curso</label>
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

        <div class="flex gap-2 overflow-x-auto sm:ml-auto sm:shrink-0">
            <button type="button" class="{{ $pillBase }}" :aria-pressed="filtroTiempo === 'todos'" :class="filtroTiempo === 'todos' ? '{{ $pillActiva }}' : '{{ $pillInactiva }}'" @click="filtroTiempo = 'todos'">
                Todos <span x-text="conteo.todos"></span>
            </button>
            <button type="button" class="{{ $pillBase }}" :aria-pressed="filtroTiempo === 'proximos'" :class="filtroTiempo === 'proximos' ? '{{ $pillActiva }}' : '{{ $pillInactiva }}'" @click="filtroTiempo = 'proximos'">
                Proximos <span x-text="conteo.proximos"></span>
            </button>
            <button type="button" class="{{ $pillBase }}" :aria-pressed="filtroTiempo === 'pasados'" :class="filtroTiempo === 'pasados' ? '{{ $pillActiva }}' : '{{ $pillInactiva }}'" @click="filtroTiempo = 'pasados'">
                Pasados <span x-text="conteo.pasados"></span>
            </button>
        </div>
    </div>

    <div x-show="estado === 'cargando'" x-cloak class="flex flex-col gap-4">
        @foreach ([1, 2] as $placeholder)
            <div class="animate-pulse border border-default bg-neutral-primary-soft rounded-base shadow-xs p-4 sm:p-5 grid grid-cols-1 gap-4 lg:grid-cols-[minmax(0,1.6fr)_minmax(0,1fr)_minmax(0,1fr)] lg:items-center lg:gap-0 lg:divide-x lg:divide-default" aria-hidden="true">
                <div class="flex flex-col gap-2 lg:pr-6">
                    <div class="h-5 w-52 rounded bg-neutral-secondary-medium"></div>
                    <div class="h-4 w-24 rounded-full bg-neutral-secondary-medium"></div>
                </div>
                <div class="grid grid-cols-2 gap-4 border-t border-default pt-4 lg:border-t-0 lg:pt-0 lg:px-6">
                    <div class="h-9 w-24 rounded bg-neutral-secondary-medium mx-auto"></div>
                    <div class="h-9 w-24 rounded bg-neutral-secondary-medium mx-auto"></div>
                    <div class="h-9 w-24 rounded bg-neutral-secondary-medium mx-auto"></div>
                    <div class="h-9 w-24 rounded bg-neutral-secondary-medium mx-auto"></div>
                </div>
                <div class="border-t border-default pt-4 lg:border-t-0 lg:pt-0 lg:pl-6">
                    <div class="h-9 w-32 rounded bg-neutral-secondary-medium ml-auto"></div>
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

    <ul class="mt-4 flex flex-col gap-3" x-show="estado === 'listo'" x-cloak>
        <template x-for="examen in examenesFiltrados" :key="examen.id">
            <li class="rounded-base bg-neutral-primary-soft shadow-xs border border-default p-4 sm:p-5 grid grid-cols-1 gap-4 lg:grid-cols-[minmax(0,1.6fr)_minmax(0,1fr)_minmax(0,1fr)] lg:items-center lg:gap-0 lg:divide-x lg:divide-default">
                {{-- Nombre con curso y etiquetas. --}}
                <div class="min-w-0 lg:pr-6">
                    <h3 class="text-lg font-semibold text-heading wrap-break-word" x-text="etiquetaExamen(examen)"></h3>
                    <div class="mt-1.5 flex flex-wrap gap-2">
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
                <div class="grid grid-cols-2 gap-4 border-t border-default pt-4 lg:border-t-0 lg:pt-0 lg:px-6">
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
                        <span class="text-xl sm:text-2xl font-semibold text-heading" x-text="examen.ingresados"></span>
                    </div>
                </div>

                {{-- Mismo enlace que "Ver detalle" de la central de riesgos. --}}
                <div class="border-t border-default pt-4 lg:border-t-0 lg:pt-0 lg:pl-6 flex justify-end">
                    <button type="button" @click="abrirDetalle(examen)" @mouseenter="precargar(examen)" @focus="precargar(examen)"
                            class="cursor-pointer whitespace-nowrap border-0 bg-transparent p-0 text-xs font-bold text-fg-brand-strong hover:underline">
                        Ver detalle ›
                    </button>
                </div>
            </li>
        </template>

        <li x-show="examenes.length === 0"
            class="flex flex-col items-center gap-2 p-8 text-center bg-neutral-primary-soft border border-default rounded-base shadow-xs">
            <p class="text-sm text-body" x-text="cursoContexto !== '' ? 'No hay examenes programados' : 'No hay examenes registrados.'"></p>
        </li>
        <li x-show="examenes.length > 0 && examenesFiltrados.length === 0"
            class="flex flex-col items-center gap-2 p-8 text-center bg-neutral-primary-soft border border-default rounded-base shadow-xs">
            <p class="text-sm text-body">No se encontraron examenes</p>
        </li>
    </ul>

    {{-- Modal Ver detalle: mismo formato que el detalle de la central de
         riesgos (titulo, recuadro destacado, etiquetas en mayuscula y
         "Aceptar"), con los datos del examen, sus reglas y materiales. --}}
    <div x-show="modalDetalle" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-black/50 p-4"
         role="dialog" aria-modal="true" aria-labelledby="titulo-detalle-examen"
         @click.self="cerrarDetalle()" @keydown.escape.window="modalDetalle && cerrarDetalle()">
        <div class="w-full max-w-lg rounded-xl border border-neutral-quaternary bg-neutral-primary-soft shadow-lg">
            <div class="flex items-center gap-2 border-b border-neutral-quaternary px-4 py-3">
                <h2 id="titulo-detalle-examen" class="min-w-0 flex-1 text-[15px] font-semibold text-fg-brand">
                    Detalles del examen
                </h2>

                <button type="button" @click="cerrarDetalle()" aria-label="Cerrar detalles del examen"
                        class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-body hover:bg-neutral-secondary-medium hover:text-heading focus:outline-none">
                    <svg class="h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 17.94 6M18 18 6.06 6"/></svg>
                </button>
            </div>

            <div class="max-h-[65vh] space-y-4 overflow-y-auto p-4">
                {{-- Lo que ya trae la tarjeta se muestra al instante. --}}
                <div class="rounded-xl border border-brand-subtle bg-brand-softer p-4">
                    <p class="font-semibold text-fg-brand-strong wrap-break-word" x-text="examenDetalle ? etiquetaExamen(examenDetalle) : ''"></p>
                    <p class="mt-1 text-sm text-fg-brand">Creado por: <span x-text="detalle ? (detalle.creador || 'Sin registrar') : '...'"></span></p>
                </div>

                <dl class="space-y-4 text-sm">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <dt class="text-[10px] font-medium uppercase tracking-wider text-body">Tipo</dt>
                            <dd class="mt-1 font-medium text-heading" x-text="tipoDe(examenDetalle)"></dd>
                        </div>
                        <div>
                            <dt class="text-[10px] font-medium uppercase tracking-wider text-body">Estado</dt>
                            <dd class="mt-1">
                                <x-ui.badge type="gray" class="font-semibold" x-show="examenDetalle?.estado !== 'En curso' && examenDetalle?.estado !== 'Finalizado'"><span x-text="examenDetalle?.estado || 'Programado'"></span></x-ui.badge>
                                <x-ui.badge type="warning" class="font-semibold" x-show="examenDetalle?.estado === 'En curso'">En curso</x-ui.badge>
                                <x-ui.badge type="brand" class="font-semibold" x-show="examenDetalle?.estado === 'Finalizado'">Finalizado</x-ui.badge>
                            </dd>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <dt class="text-[10px] font-medium uppercase tracking-wider text-body">Fecha</dt>
                            <dd class="mt-1 font-medium text-heading" x-text="examenDetalle ? fechaDe(examenDetalle) : ''"></dd>
                        </div>
                        <div>
                            <dt class="text-[10px] font-medium uppercase tracking-wider text-body">Horario</dt>
                            <dd class="mt-1 font-medium text-heading" x-text="examenDetalle ? horario(examenDetalle) : ''"></dd>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <dt class="text-[10px] font-medium uppercase tracking-wider text-body">Duracion</dt>
                            <dd class="mt-1 font-medium text-heading" x-text="detalle ? (detalle.duracion ? detalle.duracion + ' minutos' : '—') : '...'"></dd>
                        </div>
                        <div>
                            <dt class="text-[10px] font-medium uppercase tracking-wider text-body">Ingresos</dt>
                            <dd class="mt-1 font-medium text-heading" x-text="examenDetalle?.ingresados ?? 0"></dd>
                        </div>
                    </div>
                    <div>
                        <dt class="text-[10px] font-medium uppercase tracking-wider text-body">Ambientes</dt>
                        <dd class="mt-1 font-medium text-heading" x-text="examenDetalle ? ambientesDe(examenDetalle) : ''"></dd>
                    </div>
                </dl>

                {{-- Lo que llega del endpoint de detalle. --}}
                <p x-show="cargandoDetalle" class="py-2 text-center text-sm text-body">Cargando docentes, reglas y materiales...</p>
                <p x-show="! cargandoDetalle && errorDetalle !== ''" x-text="errorDetalle" class="py-2 text-center text-sm text-fg-danger-strong"></p>

                <template x-if="detalle">
                    <dl class="space-y-4 text-sm">
                        {{-- Todos los docentes del examen (creador e invitados
                             que aceptaron) con los cursos que vincularon. --}}
                        <div>
                            <dt class="text-[10px] font-medium uppercase tracking-wider text-body">Cursos y docentes</dt>
                            <dd class="mt-1 space-y-2 text-heading">
                                <template x-for="(participante, indice) in detalle.participantes" :key="indice">
                                    <div>
                                        <p class="font-medium">
                                            <span x-text="participante.docente"></span>
                                            <span class="font-normal text-body" x-text="'(' + participante.rol + ')'"></span>
                                        </p>
                                        <ul class="list-disc ps-5 text-body" x-show="participante.cursos.length > 0">
                                            <template x-for="(nombreCurso, indiceCurso) in participante.cursos" :key="indiceCurso">
                                                <li class="wrap-break-word" x-text="nombreCurso"></li>
                                            </template>
                                        </ul>
                                        <p class="ps-5 text-body" x-show="participante.cursos.length === 0">Sin curso vinculado</p>
                                    </div>
                                </template>
                                <p class="font-medium" x-show="detalle.participantes.length === 0">Sin docentes asignados</p>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-[10px] font-medium uppercase tracking-wider text-body">Reglas del examen</dt>
                            <dd class="mt-1 text-heading">
                                <ul class="list-disc space-y-1 ps-5" x-show="detalle.normas.length > 0">
                                    <template x-for="(norma, indice) in detalle.normas" :key="indice">
                                        <li class="wrap-break-word" x-text="norma"></li>
                                    </template>
                                </ul>
                                <p class="font-medium" x-show="detalle.normas.length === 0">Sin reglas registradas</p>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-[10px] font-medium uppercase tracking-wider text-body">Materiales permitidos</dt>
                            <dd class="mt-1 text-heading">
                                <ul class="list-disc space-y-1 ps-5" x-show="detalle.materiales.length > 0">
                                    <template x-for="(material, indice) in detalle.materiales" :key="indice">
                                        <li class="wrap-break-word" x-text="material"></li>
                                    </template>
                                </ul>
                                <p class="font-medium" x-show="detalle.materiales.length === 0">Sin materiales permitidos</p>
                            </dd>
                        </div>
                    </dl>
                </template>
            </div>

            <div class="flex flex-wrap justify-end gap-2 border-t border-neutral-quaternary px-4 py-3">
                <button type="button" @click="cerrarDetalle()"
                        class="inline-flex items-center justify-center rounded-base border border-transparent bg-brand px-4 py-2 text-sm font-medium text-white shadow-xs hover:bg-brand-strong focus:outline-none focus:ring-4 focus:ring-brand-medium">
                    Aceptar
                </button>
            </div>
        </div>
    </div>
</div>
