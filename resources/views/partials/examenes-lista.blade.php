{{--
    @file    examenes-lista.blade.php
    @author  Alex Candia
    @created 2026-11-18
    @updated 2026-11-18

    @description
    Listado exclusivo de la subopción «Ver lista de examenes». No contiene la
    barra de pestañas ni los formularios, selectores o modales de Crear, Editar,
    Invitar o Dividir: esas acciones viven en sus propias páginas del submenú.
--}}

@php
    $curso = $curso ?? null;
    $cursos = $cursos ?? ($curso !== null ? collect([$curso]) : collect());
    $cursoInicial = $cursoInicial ?? $curso;
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
         // Contexto de la pestana de lista: vacio = todos los cursos.
         cursoLista: @js($listadoGlobal ? '' : (string) ($curso?->id_curso ?? '')),
         examenes: [],
         ahora: @js(now()->format('Y-m-d H:i')),
         estado: 'listo',
         peticion: 0,
         tab: 'lista',
         // Cada pestana conserva su propia seleccion.
         seleccionado: '',
         examenEdicionId: '',
         examenDivisionId: '',
         examenEdicionCargadoId: '',
         cargandoEdicion: false,
         errorEdicion: '',
         peticionEdicion: 0,
         urlDetalleExamen: @js(route('api.examenes.edicion.mostrar', ['examen' => '__ID__'])),
         criterioDivision: '',
         errorDivision: '',
         urlPreviewDivision: @js(route('api.examenes.dividir.preview', ['examen' => '__ID__'])),
         csrfToken: document.querySelector('meta[name=csrf-token]')?.getAttribute('content') || '',
         // Eliminacion de examen del curso.
         modalEliminar: false,
         examenEliminarId: '',
         eliminando: false,
         errorEliminacion: '',
         avisoEliminacion: '',
         exitoEliminacion: false,
         mensajeNoIniciado: 'El examen ya inició o finalizó',
         mensajeConDatos: 'El examen tiene ingresos o incidencias registradas',
         // Modal de invitacion a docente.
         modalInvitar: false,
         examenInvitarId: '',
         busquedaDocente: '',
         buscandoDocentes: false,
         docentes: [],
         docenteSeleccionado: null,
         errorBusqueda: '',
         enviandoInvitacion: false,
         errorInvitacion: '',
         temporizadorBusqueda: null,
         modalExitoInvitacion: false,
         resumenInvitacion: null,
         modalInvitaciones: false,
         invitaciones: [],
         cargandoInvitaciones: false,
         get examenSeleccionado() {
             return this.examenes.find((examen) => String(examen.id) === String(this.seleccionado)) || null;
         },
         get examenEdicionSeleccionado() {
             return this.examenes.find((examen) => String(examen.id) === String(this.examenEdicionId)) || null;
         },
         get examenDivisionSeleccionado() {
             return this.examenes.find((examen) => String(examen.id) === String(this.examenDivisionId)) || null;
         },
         get examenEliminarSeleccionado() {
             return this.examenes.find((examen) => String(examen.id) === String(this.examenEliminarId)) || null;
         },
         get cursoContexto() {
             return this.cursoLista || this.cursoFijo;
         },
         get urlActual() {
             return this.cursoContexto !== ''
                 ? this.urlListaCurso.replace('__CURSO__', this.cursoContexto)
                 : this.urlListaGlobal;
         },
         get examenesDisponibles() {
             return this.examenes.filter((examen) => this.examenDisponible(examen));
         },
         get examenesEditables() {
             return this.examenesDisponibles.filter((examen) => Boolean(examen.es_propio));
         },
         get aulasDelExamen() {
             return this.examenDivisionSeleccionado?.ambientes?.length ?? 0;
         },
         get puedeDividir() {
             return this.aulasDelExamen > 1;
         },
         async cargar() {
             const peticion = ++this.peticion;

             this.examenEdicionCargadoId = '';
             this.cargandoEdicion = false;
             this.errorEdicion = '';
             this.limpiarSeleccion();

              this.estado = 'cargando';
              try {
                  const url = this.urlActual;
                  const respuesta = await fetch(url);
                 if (!respuesta.ok) {
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
         examenDisponible(examen) {
             if (! examen.fecha) {
                 return false;
             }

             const inicio = examen.fecha + ' ' + (examen.hora_inicio || '00:00');

             return inicio > this.ahora;
         },
         etiquetaExamen(examen) {
             return [examen.tipo || 'Sin tipo', examen.curso || '']
                 .filter(Boolean)
                 .join(' - ');
         },
         abrirEditar() {
             this.tab = 'editar';

             const actual = this.examenesEditables.find(
                 (examen) => String(examen.id) === String(this.examenEdicionId)
             );

             if (! actual) {
                 this.examenEdicionId = '';
                 this.examenEdicionCargadoId = '';
             }
         },
         seleccionarEdicion() {
             this.criterioDivision = '';
             this.errorDivision = '';
             this.cargarExamenEdicion(true);
         },
         async cargarExamenEdicion(forzar = true) {
             const id = String(this.examenEdicionId ?? '');

             if (id === '') {
                 return;
             }

             if (! forzar && id === this.examenEdicionCargadoId) {
                 return;
             }

             const peticion = ++this.peticionEdicion;

             this.cargandoEdicion = true;
             this.errorEdicion = '';

             try {
                 const url = this.urlDetalleExamen.replace('__ID__', id);
                 const respuesta = await fetch(url, {
                     headers: { 'Accept': 'application/json' },
                     credentials: 'same-origin',
                 });
                 const cuerpo = await respuesta.json().catch(() => ({}));

                 if (peticion !== this.peticionEdicion) {
                     return;
                 }

                 if (! respuesta.ok) {
                     this.examenEdicionCargadoId = '';
                     this.errorEdicion = cuerpo?.message || 'No se pudieron cargar los datos del examen.';

                     return;
                 }

                 const seleccionado = this.examenEdicionSeleccionado;
                 const datos = cuerpo.datos ?? {};

                 window.dispatchEvent(new CustomEvent('editar-examen-datos', {
                     detail: {
                         ...datos,
                         curso_id: seleccionado?.cursos?.[0]?.id ?? '',
                         curso_nombre: seleccionado?.curso ?? '',
                     },
                 }));
                 this.examenEdicionCargadoId = id;
             } catch (error) {
                 if (peticion === this.peticionEdicion) {
                     this.examenEdicionCargadoId = '';
                     this.errorEdicion = 'No se pudo conectar con el servidor.';
                 }
             } finally {
                 if (peticion === this.peticionEdicion) {
                     this.cargandoEdicion = false;
                 }
             }
         },
         async abrirModalDivision() {
             if (this.examenDivisionId === '' || this.criterioDivision === '' || ! this.puedeDividir) {
                 return;
             }

             this.errorDivision = '';

             try {
                 const url = this.urlPreviewDivision.replace('__ID__', this.examenDivisionId);
                 const respuesta = await fetch(url, {
                     method: 'POST',
                     headers: {
                         'Content-Type': 'application/json',
                         'Accept': 'application/json',
                         'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                     },
                     body: JSON.stringify({
                         ambientes: (this.examenDivisionSeleccionado.ambientes || []).map((ambiente) => ambiente.id),
                         division_por: this.criterioDivision
                     })
                 });
                 const cuerpo = await respuesta.json().catch(() => ({}));

                 if (! respuesta.ok || ! cuerpo.exito) {
                     this.errorDivision = cuerpo.mensaje || cuerpo.message || 'No se pudo preparar la division.';
                     return;
                 }

                 window.dispatchEvent(new CustomEvent('abrir-modal-dividir', {
                     detail: {
                         examenId: this.examenDivisionId,
                         ambientes: cuerpo.ambientes,
                         divisionPor: this.criterioDivision,
                         total: cuerpo.total_estudiantes
                     }
                 }));
             } catch (e) {
                 this.errorDivision = 'No se pudo conectar con el servidor.';
             }
         },
         limpiarSeleccion() {
             this.seleccionado = '';
             this.examenEdicionId = '';
             this.examenDivisionId = '';
             this.criterioDivision = '';
             this.errorDivision = '';
         },
         abrirModalInvitar() {
             if (this.seleccionado === '' || this.seleccionado === null) {
                 return;
             }
             this.examenInvitarId = String(this.seleccionado);
             this.busquedaDocente = '';
             this.docentes = [];
             this.docenteSeleccionado = null;
             this.errorBusqueda = '';
             this.errorInvitacion = '';
             this.enviandoInvitacion = false;
             this.modalInvitar = true;
         },
         cerrarModalInvitar() {
             this.modalInvitar = false;
             if (this.temporizadorBusqueda) {
                 clearTimeout(this.temporizadorBusqueda);
                 this.temporizadorBusqueda = null;
             }
         },
         programarBusqueda() {
             if (this.temporizadorBusqueda) {
                 clearTimeout(this.temporizadorBusqueda);
             }
             this.temporizadorBusqueda = setTimeout(() => this.buscarDocentes(), 1000);
         },
         buscarDocentesAhora() {
             if (this.temporizadorBusqueda) {
                 clearTimeout(this.temporizadorBusqueda);
                 this.temporizadorBusqueda = null;
             }
             this.buscarDocentes();
         },
         async buscarDocentes() {
             const termino = this.busquedaDocente.trim();
             if (termino.length < 2) {
                 this.docentes = [];
                 this.errorBusqueda = termino.length === 0 ? '' : 'La busqueda debe tener al menos 2 caracteres.';
                 return;
             }
             this.buscandoDocentes = true;
             this.errorBusqueda = '';
             try {
                 const url = '/api/examenes/' + this.examenInvitarId + '/docentes-invitables?busqueda=' + encodeURIComponent(termino);
                 const respuesta = await fetch(url, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' });
                 const cuerpo = await respuesta.json();
                 if (!respuesta.ok) {
                     throw new Error(cuerpo?.errors?.busqueda?.[0] || cuerpo?.message || 'No se pudo buscar docentes.');
                 }
                 this.docentes = cuerpo.data || [];
                 if (this.docentes.length === 0) {
                     this.errorBusqueda = 'No se encontraron docentes.';
                 }
             } catch (error) {
                 this.docentes = [];
                 this.errorBusqueda = error.message || 'No se pudo buscar docentes.';
             } finally {
                 this.buscandoDocentes = false;
             }
         },
         seleccionarDocente(docente) {
             this.docenteSeleccionado = docente;
             this.errorInvitacion = '';
         },
         async enviarInvitacion() {
             if (!this.docenteSeleccionado) {
                 this.errorInvitacion = 'Debe seleccionar un docente.';
                 return;
             }
             this.enviandoInvitacion = true;
             this.errorInvitacion = '';
             try {
                 const respuesta = await fetch('/api/examenes/' + this.examenInvitarId + '/invitaciones', {
                     method: 'POST',
                     credentials: 'same-origin',
                     headers: {
                         'Accept': 'application/json',
                         'Content-Type': 'application/json',
                         'X-CSRF-TOKEN': this.csrfToken,
                         'X-Requested-With': 'XMLHttpRequest',
                     },
                     body: JSON.stringify({ docente_invitado_id: this.docenteSeleccionado.id }),
                 });
                 const cuerpo = await respuesta.json();
                 if (!respuesta.ok) {
                     const errores = cuerpo?.errors || {};
                     this.errorInvitacion = errores.docente_invitado_id?.[0] || cuerpo?.message || 'No se pudo enviar la invitacion.';
                     return;
                 }
                 this.resumenInvitacion = cuerpo.data;
                 this.modalInvitar = false;
                 this.modalExitoInvitacion = true;
             } catch (error) {
                 this.errorInvitacion = error.message || 'No se pudo enviar la invitacion.';
             } finally {
                 this.enviandoInvitacion = false;
             }
         },
         abrirModalInvitaciones() {
             if (this.seleccionado === '' || this.seleccionado === null) {
                 return;
             }
             this.examenInvitarId = String(this.seleccionado);
             this.modalInvitaciones = true;
             this.cargarInvitaciones();
         },
         cerrarModalInvitaciones() {
             this.modalInvitaciones = false;
         },
         async cargarInvitaciones() {
             this.cargandoInvitaciones = true;
             try {
                 const respuesta = await fetch('/api/examenes/' + this.examenInvitarId + '/invitaciones', { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' });
                 const cuerpo = await respuesta.json();
                 if (!respuesta.ok) {
                     throw new Error(cuerpo?.message || 'No se pudieron cargar las invitaciones.');
                 }
                 this.invitaciones = cuerpo.data || [];
             } catch (error) {
                 this.invitaciones = [];
             } finally {
                 this.cargandoInvitaciones = false;
             }
         },
         async cancelarInvitacion(idInvitacion) {
             try {
                 const respuesta = await fetch('/api/invitaciones/' + idInvitacion + '/cancelar', {
                     method: 'POST',
                     credentials: 'same-origin',
                     headers: {
                         'Accept': 'application/json',
                         'X-CSRF-TOKEN': this.csrfToken,
                         'X-Requested-With': 'XMLHttpRequest',
                     }
                 });
                 if (!respuesta.ok) {
                     throw new Error('No se pudo cancelar.');
                 }
                 await this.cargarInvitaciones();
             } catch (error) {
             }
         },
         fechaDe(examen) {
             if (!examen.fecha) {
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
         // Tras guardar una edicion (Task #261), refresca el listado para que el
         // selector muestre el tipo, la fecha y el horario nuevos. No cambia la
         // seleccion ni reenvia los datos al formulario, asi el aviso de exito
         // del formulario sigue a la vista.
         async refrescarExamenes() {
             try {
                 const url = this.urlActual;
                 const respuesta = await fetch(url, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' });
                 if (! respuesta.ok) {
                     return;
                 }
                 const cuerpo = await respuesta.json();
                 this.examenes = cuerpo.datos ?? [];
             } catch (error) {
                 // El listado se pone al dia al recargar la pagina.
             }
         },
          // El bloqueo ocurre al presionar 'Eliminar' en la tarjeta, con los
          // datos del listado; el servidor vuelve a validar al confirmar.
         motivoBloqueoEliminar(examen) {
             if (examen.fecha) {
                 const inicio = examen.fecha + ' ' + (examen.hora_inicio || '00:00');
                 if (inicio <= this.ahora) {
                     return this.mensajeNoIniciado;
                 }
             }

             if (Number(examen.ingresados || 0) > 0 || Number(examen.incidencias || 0) > 0) {
                 return this.mensajeConDatos;
             }

             return '';
         },
         abrirModalEliminar(examen) {
             this.avisoEliminacion = '';
             this.errorEliminacion = '';

             const motivo = this.motivoBloqueoEliminar(examen);
             if (motivo !== '') {
                 this.avisoEliminacion = motivo;
                 return;
             }

             this.examenEliminarId = String(examen.id);
             this.modalEliminar = true;
         },
         cerrarModalEliminar() {
             this.modalEliminar = false;
             this.examenEliminarId = '';
             this.errorEliminacion = '';
         },
         async confirmarEliminar() {
             const examen = this.examenEliminarSeleccionado;
             if (! examen || this.cursoContexto === '' || this.eliminando) {
                 return;
             }

             this.eliminando = true;
             this.errorEliminacion = '';

             try {
                 const respuesta = await fetch('/api/examenes/' + examen.id + '/cursos/' + this.cursoContexto, {
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
                     this.errorEliminacion = cuerpo?.errors?.eliminar?.[0]
                         || cuerpo?.message
                         || 'No se pudo eliminar el examen.';
                     return;
                 }

                 this.modalEliminar = false;
                 this.examenEliminarId = '';
                 this.exitoEliminacion = true;
                 await this.refrescarExamenes();
             } catch (error) {
                 this.errorEliminacion = 'No se pudo conectar con el servidor.';
             } finally {
                 this.eliminando = false;
             }
         },
     }"
     @examen-editado-guardado.window="refrescarExamenes()">
    @if (session('mensaje'))
        <x-ui.modal-aviso :mensaje="session('mensaje')" />
    @endif

    <div x-cloak class="flex flex-col gap-4">
    {{-- Ver Lista de Examenes: selector de curso y Eliminar por tarjeta. --}}
    <section x-show="tab === 'lista'" x-cloak class="flex flex-col gap-4">
        {{-- Selector de curso: solo en la pagina unica; con un curso elegido
             la lista se acota a ese curso y cada tarjeta ofrece Eliminar. --}}
        @if ($listadoGlobal)
            <div class="flex flex-col gap-1.5">
                <div class="min-w-0 sm:max-w-xs">
                    <label for="examen-curso-lista" class="block mb-2.5 text-sm font-medium text-heading">Curso</label>
                    <select id="examen-curso-lista"
                            x-model="cursoLista"
                            @change="avisoEliminacion = ''; exitoEliminacion = false; cargar()"
                            class="block w-full px-3 py-2.5 border text-sm rounded-base shadow-xs bg-neutral-secondary-medium border-default-medium text-heading focus:ring-brand focus:border-brand">
                        <option value="">Todos los cursos</option>
                        @foreach ($opcionesCursos as $idCursoLista => $nombreCursoLista)
                            <option value="{{ $idCursoLista }}">{{ $nombreCursoLista }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        @endif

        <div x-show="avisoEliminacion !== ''" x-cloak>
            <x-ui.alert type="danger" title="No se puede eliminar el examen:">
                <span x-text="avisoEliminacion"></span>
            </x-ui.alert>
        </div>

        <div x-show="estado === 'cargando'" x-cloak class="flex flex-col gap-4">
            @foreach ([1, 2] as $placeholder)
                <div class="animate-pulse border border-default bg-neutral-primary-soft rounded-base shadow-xs px-5 py-6 sm:px-8 flex flex-col gap-4 lg:flex-row lg:items-center lg:gap-6" aria-hidden="true">
                    <div class="flex items-center gap-4 lg:w-72">
                        <div class="h-12 w-12 shrink-0 rounded-lg bg-neutral-secondary-medium"></div>
                        <div class="flex flex-col gap-2">
                            <div class="h-5 w-40 rounded bg-neutral-secondary-medium"></div>
                            <div class="h-4 w-24 rounded-full bg-neutral-secondary-medium"></div>
                        </div>
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
                    {{-- Ícono, nombre con curso y estado. --}}
                    <div class="flex items-center gap-4 lg:w-72">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-lg bg-brand-softer text-fg-brand-strong">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-6 w-6" aria-hidden="true"><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/><path d="M10 9H8"/><path d="M16 13H8"/><path d="M16 17H8"/></svg>
                        </div>
                        <div class="flex min-w-0 flex-col items-start gap-1">
                            <h3 class="text-xl font-bold text-heading" x-text="etiquetaExamen(examen)"></h3>
                            <div style="display:flex; gap:0.5rem; flex-wrap:wrap;">
                            <span x-show="examen.es_propio" class="inline-flex items-center px-1.5 py-0.5 text-xs font-medium rounded-full border bg-brand-softer border-brand-subtle text-fg-brand-strong">Propio</span>
                            <span x-show="examen.es_anfitrion" class="inline-flex items-center px-1.5 py-0.5 text-xs font-medium rounded-full border bg-warning-soft border-warning-subtle text-fg-warning">Anfitrion</span>
                            <span x-show="!examen.es_propio" class="inline-flex items-center px-1.5 py-0.5 text-xs font-medium rounded-full border bg-success-soft border-success-subtle text-fg-success-strong">Invitado</span>
                                <x-ui.badge type="gray" class="mt-1" x-show="examen.estado === 'Programado'">
                                    <span x-text="examen.estado"></span>
                                </x-ui.badge>
                                <x-ui.badge type="warning" class="mt-1" x-show="examen.estado === 'En curso'">
                                    <span x-text="examen.estado"></span>
                                </x-ui.badge>
                                <x-ui.badge type="brand" class="mt-1 gap-1" x-show="examen.estado === 'Finalizado'">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-3 w-3 shrink-0" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
                                    <span x-text="examen.estado"></span>
                                </x-ui.badge>
                            </div>
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

                        {{-- Acciones de la tarjeta: solo con un curso concreto. --}}
                        <div class="flex shrink-0 items-center justify-end" x-show="cursoContexto !== ''" x-cloak>
                            <x-ui.button variant="danger" @click="abrirModalEliminar(examen)">
                                Eliminar
                            </x-ui.button>
                        </div>
                    </li>
            </template>

            <li x-show="examenes.length === 0"
                class="flex flex-col items-center gap-2 p-8 text-center bg-neutral-primary-soft border border-default rounded-base shadow-xs">
                <svg class="w-6 h-6 text-fg-disabled" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-width="2" d="M4 10h16m-8-3V4M7 7V4m10 3V4M5 20h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2Zm3-7h.01v.01H8V13Zm4 0h.01v.01H12V13Zm4 0h.01v.01H16V13Zm-8 4h.01v.01H8V17Zm4 0h.01v.01H12V17Zm4 0h.01v.01H16V17Z"/></svg>
                <p class="text-sm text-body" x-text="cursoContexto !== '' ? 'No hay exámenes programados' : 'No hay examenes registrados.'"></p>
            </li>
        </ul>
    </section>

    </div>

    {{-- Modal: confirmar la eliminacion del examen del curso. --}}
    <div x-show="modalEliminar" x-cloak class="overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 flex justify-center items-center w-full md:inset-0 h-full max-h-full bg-overlay-modal/50" @click.self="cerrarModalEliminar()" @keydown.escape.window="cerrarModalEliminar()">
        <div class="relative p-4 w-full max-w-md max-h-full">
            <div class="relative bg-neutral-primary-soft border border-default rounded-base shadow-sm p-4 md:p-6">
                <div class="flex items-start justify-between gap-3 border-b border-default pb-4">
                    <div>
                        <h3 class="text-lg font-medium text-heading">Eliminar examen</h3>
                        <p class="text-sm text-muted">El examen dejara de aparecer en la lista de este curso.</p>
                    </div>
                    <button type="button" @click="cerrarModalEliminar()" class="text-body bg-transparent hover:bg-neutral-tertiary hover:text-heading rounded-base text-sm w-9 h-9 ms-auto inline-flex justify-center items-center cursor-pointer">
                        <svg class="w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 17.94 6M18 18 6.06 6"/></svg>
                        <span class="sr-only">Cerrar modal</span>
                    </button>
                </div>

                <div class="py-4 space-y-3">
                    <p class="text-sm text-body">Se eliminara el examen del curso. Esta accion no se puede deshacer.</p>
                    <p class="text-sm font-semibold text-heading" x-text="examenEliminarSeleccionado?.nombre || ''"></p>
                    <p x-show="errorEliminacion !== ''" x-text="errorEliminacion" class="text-sm text-fg-danger-strong"></p>
                </div>

                <div class="border-t border-default pt-4 flex flex-col gap-3 sm:flex-row sm:justify-end">
                    <x-ui.button-cancelar class="w-full justify-center sm:w-auto" @click="cerrarModalEliminar()">Cancelar</x-ui.button-cancelar>
                    <x-ui.button variant="danger" class="w-full justify-center sm:w-auto"
                                 @click="confirmarEliminar()"
                                 x-bind:disabled="eliminando"
                                 x-bind:class="eliminando ? 'cursor-not-allowed opacity-50' : ''">
                        <span x-text="eliminando ? 'Eliminando...' : 'Eliminar'"></span>
                    </x-ui.button>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal de exito de eliminacion. --}}
    <div x-show="exitoEliminacion" x-cloak class="overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 flex justify-center items-center w-full md:inset-0 h-full max-h-full bg-overlay-modal/50" @click.self="exitoEliminacion = false" @keydown.escape.window="exitoEliminacion = false">
        <div class="relative p-4 w-full max-w-md max-h-full">
            <div class="relative bg-neutral-primary-soft border border-default rounded-base shadow-sm p-4 md:p-6 text-center">
                <h3 class="text-lg font-medium text-heading">Examen eliminado con exito</h3>
                <p class="mt-2 text-sm text-body">El examen ya no está en la lista de cursos.</p>
                <div class="mt-4">
                    <button type="button" @click="exitoEliminacion = false" class="inline-flex items-center justify-center rounded-base border border-transparent bg-brand px-4 py-2 text-sm font-medium text-white hover:bg-brand-strong">Aceptar</button>
                </div>
            </div>
        </div>
    </div>
</div>