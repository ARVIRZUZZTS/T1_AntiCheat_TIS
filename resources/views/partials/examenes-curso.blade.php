{{--
    @file    examenes-curso.blade.php
    @author  Valery D. Ortuno P. <valerydariana98@gmail.com>
    @created 2026-10-05
    @updated 2026-10-10
    @updated 2026-10-24
    @updated 2026-10-25
    @updated 2026-10-26
    @updated 2026-11-04
    @updated 2026-11-05
    @updated 2026-11-14

    @description
    Parcial de la sección Exámenes. En la página única lista todos los exámenes
    del docente (propios o con invitación aceptada), pasados y futuros, con el
    curso como parte del nombre; en la página de compatibilidad por curso
    conserva el listado acotado a ese curso. Los selectores de Editar, Invitar y
    Dividir siguen mostrando solo los exámenes futuros.

    El parcial muestra cuatro pestañas: "Ver Lista de Examenes", "Crear Examen",
    "Editar Examen" y "Dividir Examen". Al cambiar de pestaña solo cambia el
    contenido de abajo; no se navega a otra página.

    La pestaña de lista es de solo lectura: las tarjetas se consultan con fetch
    al endpoint global GET /api/examenes (ExamenDocenteController) o al endpoint
    por curso GET /api/cursos/{idCurso}/examenes (CursoExamenController). Desde
    la lista se abre el modal para invitar a otro docente.

    Crear Examen incrusta el mismo formulario de pages/examen-crear. En la
    página única el curso se elige en un selector obligatorio del formulario; en
    la página por curso queda fijo. Si la validación falla, StoreExamenRequest
    redirige con errores y la página vuelve a abrir esa pestaña con el curso
    elegido.

    Editar Examen lista solo los exámenes propios con fecha futura y precarga el
    mismo formulario con GET /api/examenes/{examen}/edicion. "Guardar cambios"
    envía el formulario con PUT /api/examenes/{examen}/edicion (Task #261); al
    guardar, el parcial refresca el listado para que el selector muestre los
    datos nuevos.

    La división usa los ambientes que ya devuelve el endpoint. Si el examen
    tiene una sola aula, en lugar de los controles se muestra un texto simple
    avisando que no se puede dividir.

    El listado no llega con la página: el parcial declara un x-data de Alpine
    que consulta el endpoint cuando se abre. Los estados de carga, error y
    lista vacía se mantienen.

    @changelog
    - 2026-10-05  [Alex Candia]  feat: creación inicial del parcial de exámenes
      de una materia.
    - 2026-10-09  [Diego Tejerina]  feat: lista de exámenes desde su API,
      tarjetas horizontales y estados de carga, error y vacío.
    - 2026-10-10  [OchoaCesar]  feat: se muestran tipo, estado, fecha, horario,
      ambientes e ingresados; sin "Ver Detalles" ni inscritos.
    - 2026-10-24  [Valery D. Ortuno P]  refactor: el parcial pasa de
      materia-examenes.blade.php a examenes-curso.blade.php y lo incrusta la
      página de exámenes del curso. El botón "Crear examen" y el modal de alta
      salen (el alta es una página propia desde la sección Exámenes); se suman
      "Invitar docente" y "Dividir examen", que se activan al seleccionar una
      tarjeta; un clic fuera de las tarjetas suelta la selección. Los textos
      visibles van sin acentos.
    - 2026-10-25  [Alex Candia]  feat: las acciones pasan a cuatro pestañas en
      la misma página: Ver Lista de Examenes (solo lectura), Crear Examen
      (formulario incrustado), Invitar Docente a Examen y Dividir Examen. El
      examen de invitación/división se elige en un selector. La división con una
      sola aula muestra solo un texto simple y el botón "Dividir" todavía no
      ejecuta la división.
    - 2026-10-25  [Alex Candia]  refactor: el parcial recibe la lista de materias
      y el curso inicial; agrega el selector arriba y oculta las cuatro pestañas
      mientras no haya materia. El fetch, el formulario y las pestañas se
      actualizan sin salir de la página.
    - 2026-10-26  [Alisson D. Alvarado]  feat: conecta el botón "Dividir" con la
      vista previa y los modales de división de examen.
    - 2026-11-04  [Alex Candia]  fix: los selectores de Invitar y Dividir ya no
      ofrecen exámenes con fecha u hora anteriores al momento actual. El listado
      completo se conserva en "Ver Lista de Examenes".
    - 2026-11-04  [Alex Candia]  feat: nueva pestaña "Editar Examen" a la derecha
      de "Crear Examen", por ahora solo con el selector de exámenes disponibles.
    - 2026-11-05  [Alex Candia]  feat: Editar Examen carga el detalle del examen
      propio elegido y precarga el formulario de alta. No guarda cambios: el
      submit está anulado y el botón queda inerte hasta implementar el guardado.
    - 2026-11-07  [Diego Tejerina]  feat: "Guardar cambios" guarda la edición con
      PUT /api/examenes/{examen}/edicion y, al terminar, refresca el listado
      (refrescarExamenes) sin perder la selección ni el aviso de éxito (#261).
    - 2026-11-14  [Alex Candia]  feat: se quita el selector de materia. La página
      única lista todos los exámenes de todos los cursos del docente, pasados y
      futuros, con el curso como parte del nombre, y usa selectores
      independientes para Editar y Dividir; Crear Examen recibe los cursos para
      elegir el curso en el formulario.

    @see  App\Http\Controllers\Api\CursoExamenController
    @see  App\Http\Controllers\Api\ExamenDocenteController
    @see  App\Http\Controllers\Api\ExamenEdicionController
    @see  App\Http\Controllers\ExamenController
    @see  resources/views/pages/examenes-curso.blade.php
    @see  resources/views/components/ui/form-crear-examen.blade.php
--}}

@php
    $curso = $curso ?? null;
    $cursos = $cursos ?? ($curso !== null ? collect([$curso]) : collect());
    $cursoInicial = $cursoInicial ?? $curso;
    $puedeCrearExamen = $puedeCrearExamen ?? true;
    $ambientes = $ambientes ?? [];
    $materiales = $materiales ?? [];
    $normas = $normas ?? [];

    // La pagina unica lista los examenes de todos los cursos del docente. La
    // pagina de compatibilidad por curso conserva su listado acotado.
    $listadoGlobal = $curso === null;
    $urlExamenes = $listadoGlobal
        ? route('api.examenes.listar')
        : route('api.cursos.examenes.listar', ['idCurso' => '__CURSO__']);

    // Mismas clases de pestañas que usa el detalle del curso.
    $tabClases = 'shrink-0 whitespace-nowrap inline-flex items-center justify-center box-border border focus:ring-4 shadow-xs font-medium leading-5 rounded-base focus:outline-none px-4 py-2.5 text-sm';
    $tabActivo = 'text-white bg-brand border-transparent hover:bg-brand-strong focus:ring-brand-medium';
    $tabInactivo = 'text-body bg-neutral-secondary-medium border-default-medium hover:bg-neutral-tertiary-medium hover:text-heading focus:ring-neutral-tertiary';

    $opcionesDivision = [
        'carrera' => 'Por carrera',
        'apellido' => 'Por orden alfabetico',
    ];

    $opcionesCursos = $cursos->mapWithKeys(function ($curso) {
        $etiqueta = $curso->nombre_curso;

        if (!empty($curso->es_anfitrion)) {
            $etiqueta .= ' (Anfitrion)';
        }

        return [$curso->id_curso => $etiqueta];
    })->all();

    // Si el alta falló, se vuelve a abrir la pestaña del formulario.
    $tabInicial = $tabInicial ?? (($errors->any() && $puedeCrearExamen) ? 'crear' : 'lista');
@endphp

<div class="flex flex-col gap-4"
     x-data="{
         cursoFijo: @js((string) ($curso?->id_curso ?? '')),
         urlExamenes: @js($urlExamenes),
         examenes: [],
         ahora: @js(now()->format('Y-m-d H:i')),
         estado: 'listo',
         peticion: 0,
         tab: @js($tabInicial),
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
                 const url = this.urlExamenes.replace('__CURSO__', this.cursoFijo);
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
                 const url = this.urlExamenes.replace('__CURSO__', this.cursoFijo);
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
     }"
     @examen-editado-guardado.window="refrescarExamenes()">
    @if (session('mensaje'))
        <x-ui.modal-aviso :mensaje="session('mensaje')" />
    @endif

    <div x-cloak class="flex flex-col gap-4">
    <header class="flex flex-col gap-3">

        <nav class="flex min-w-0 gap-2 overflow-x-auto px-3" aria-label="Opciones de examenes">
            <button type="button" class="{{ $tabClases }}"
                    :class="tab === 'lista' ? '{{ $tabActivo }}' : '{{ $tabInactivo }}'"
                    @click="tab = 'lista'">
                Ver Lista de Examenes
            </button>

            @if ($puedeCrearExamen)
                <button type="button" class="{{ $tabClases }}"
                        :class="tab === 'crear' ? '{{ $tabActivo }}' : '{{ $tabInactivo }}'"
                        @click="tab = 'crear'">
                    Crear Examen
                </button>
            @else
                <button type="button" class="{{ $tabClases }} {{ $tabInactivo }} opacity-50 cursor-not-allowed"
                        title="{{ $listadoGlobal ? 'No hay cursos activos para crear examenes' : 'El curso ya finalizo' }}" disabled>
                    Crear Examen
                </button>
            @endif

            <button type="button" class="{{ $tabClases }}"
                    :class="tab === 'editar' ? '{{ $tabActivo }}' : '{{ $tabInactivo }}'"
                    @click="abrirEditar()">
                Editar Examen
            </button>

            <button type="button" class="{{ $tabClases }}"
                    :class="tab === 'dividir' ? '{{ $tabActivo }}' : '{{ $tabInactivo }}'"
                    @click="tab = 'dividir'">
                Dividir Examen
            </button>
        </nav>
    </header>

    {{-- Ver Lista de Examenes: solo lectura, sin selección ni acciones. --}}
    <section x-show="tab === 'lista'" x-cloak class="flex flex-col gap-4">
        <div class="flex flex-col gap-4" x-show="estado === 'cargando'">
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
                </li>
            </template>

            <li x-show="examenes.length === 0"
                class="flex flex-col items-center gap-2 p-8 text-center bg-neutral-primary-soft border border-default rounded-base shadow-xs">
                <svg class="w-6 h-6 text-fg-disabled" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-width="2" d="M4 10h16m-8-3V4M7 7V4m10 3V4M5 20h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2Zm3-7h.01v.01H8V13Zm4 0h.01v.01H12V13Zm4 0h.01v.01H16V13Zm-8 4h.01v.01H8V17Zm4 0h.01v.01H12V17Zm4 0h.01v.01H16V17Z"/></svg>
                <p class="text-sm text-body">No hay examenes registrados.</p>
            </li>
        </ul>
    </section>

    {{-- Crear Examen: el mismo formulario del alta, incrustado en la pestaña. --}}
    @if ($puedeCrearExamen)
        <section x-show="tab === 'crear'" x-cloak>
            <div class="mx-auto w-full max-w-2xl rounded-base border border-default bg-neutral-primary-soft p-4 shadow-sm md:p-6">
                <x-ui.form-crear-examen
                    :nombre-curso="$cursoInicial?->nombre_curso ?? ''"
                    :cursos="$listadoGlobal ? $opcionesCursos : []"
                    :ambientes="$ambientes"
                    :materiales="$materiales"
                    :normas="$normas"
                    :curso-id="$cursoInicial?->id_curso"
                    :accion-url="route('cursos.examenes.store', ['curso' => '__CURSO__'])"
                    :cancelar-url="route('examenes')"
                />
            </div>
        </section>
    @endif

    {{-- Editar Examen: se elige uno propio y se precarga el mismo formulario
         del alta. Al cambiar la seleccion se consulta el detalle del examen. --}}
    <section x-show="tab === 'editar'" x-cloak class="flex flex-col gap-4">
        <p class="px-3 text-sm text-body" x-show="estado === 'cargando'">Cargando examenes...</p>

        <div x-show="estado === 'error'" x-cloak>
            <x-ui.alert type="danger" title="No se pudieron cargar los examenes">
                Ocurrio un problema al consultar el listado.
                <div class="mt-2">
                    <x-ui.button variant="secondary" @click="cargar()">Reintentar</x-ui.button>
                </div>
            </x-ui.alert>
        </div>

        <p class="px-3 text-sm text-body" x-show="estado === 'listo' && examenesDisponibles.length === 0">
            No hay examenes futuros para mostrar.
        </p>

        <p class="px-3 text-sm text-body" x-show="estado === 'listo' && examenesDisponibles.length > 0 && examenesEditables.length === 0">
            No hay examenes propios disponibles para editar.
        </p>

        <div x-show="estado === 'listo' && examenesEditables.length > 0" class="min-w-0 max-w-md px-3">
            <label for="examen-editar" class="block mb-2.5 text-sm font-medium text-heading">Examen</label>
            <select id="examen-editar" x-model="examenEdicionId" @change="seleccionarEdicion()"
                    class="block w-full px-3 py-2.5 border text-sm rounded-base shadow-xs bg-neutral-secondary-medium border-default-medium text-heading focus:ring-brand focus:border-brand">
                <option value="" disabled>Selecciona un examen</option>
                <template x-for="examen in examenesEditables" :key="'editar-' + examen.id">
                    <option :value="String(examen.id)" x-text="etiquetaExamen(examen)"></option>
                </template>
            </select>
        </div>

        <p class="px-3 text-sm text-body" x-show="cargandoEdicion">Cargando datos del examen...</p>

        <div x-show="! cargandoEdicion && errorEdicion !== ''" x-cloak>
            <x-ui.alert type="danger" title="No se pudieron cargar los datos del examen">
                <span x-text="errorEdicion"></span>
                <div class="mt-2">
                    <x-ui.button variant="secondary" @click="cargarExamenEdicion(true)">Reintentar</x-ui.button>
                </div>
            </x-ui.alert>
        </div>

        <div x-show="examenEdicionId !== '' && ! cargandoEdicion && errorEdicion === ''"
             class="mx-auto w-full max-w-2xl rounded-base border border-default bg-neutral-primary-soft p-4 shadow-sm md:p-6">
            <x-ui.form-crear-examen
                :nombre-curso="$cursoInicial?->nombre_curso ?? ''"
                :ambientes="$ambientes"
                :materiales="$materiales"
                :normas="$normas"
                :curso-id="$cursoInicial?->id_curso"
                modo="editar"
                id-prefijo="editar"
                :cancelar-url="route('examenes')"
                :accion-url="route('api.examenes.edicion.actualizar', ['examen' => '__ID__'])"
            />
        </div>
    </section>
    {{-- Invitar Docente a Examen: se elige el examen; la invitación todavía no
         tiene flujo de guardado. --}}
    <section x-show="tab === 'invitar'" x-cloak class="flex flex-col gap-4">
        <p class="px-3 text-sm text-body" x-show="estado === 'cargando'">Cargando examenes...</p>

        <div x-show="estado === 'error'" x-cloak>
            <x-ui.alert type="danger" title="No se pudieron cargar los examenes">
                Ocurrio un problema al consultar el listado.
                <div class="mt-2">
                    <x-ui.button variant="secondary" @click="cargar()">Reintentar</x-ui.button>
                </div>
            </x-ui.alert>
        </div>

        <p class="px-3 text-sm text-body" x-show="estado === 'listo' && examenesDisponibles.length === 0">
            No hay examenes futuros para mostrar.
        </p>

        <div x-show="estado === 'listo' && examenesDisponibles.length > 0"
             class="flex flex-col gap-3 sm:flex-row sm:items-end">
            <div class="min-w-0 flex-1 sm:max-w-md">
                <label for="examen-invitar" class="block mb-2.5 text-sm font-medium text-heading">Examen</label>
                <select id="examen-invitar" x-model="seleccionado" @change="criterioDivision = ''"
                        class="block w-full px-3 py-2.5 border text-sm rounded-base shadow-xs bg-neutral-secondary-medium border-default-medium text-heading focus:ring-brand focus:border-brand">
                    <option value="" disabled>Selecciona un examen</option>
                    <template x-for="examen in examenesDisponibles" :key="'invitar-' + examen.id">
                        <option :value="examen.id" x-text="etiquetaExamen(examen)"></option>
                    </template>
                </select>
            </div>

            <x-ui.button variant="default"
                         x-bind:disabled="seleccionado === ''"
                         x-bind:class="seleccionado === '' ? 'opacity-50 cursor-not-allowed' : ''"
                         @click.prevent="abrirModalInvitar()">
                Invitar docente
            </x-ui.button>
            <x-ui.button variant="secondary"
                         x-bind:disabled="seleccionado === ''"
                         x-bind:class="seleccionado === '' ? 'opacity-50 cursor-not-allowed' : ''"
                         @click="abrirModalInvitaciones()">
                Invitaciones
            </x-ui.button>
        </div>
    </section>

    {{-- Dividir Examen: selector de examen y, si tiene dos o más aulas, el
         criterio de división. Con una sola aula solo se muestra el aviso. --}}
    <section x-show="tab === 'dividir'" x-cloak class="flex flex-col gap-4">
        <p class="px-3 text-sm text-body" x-show="estado === 'cargando'">Cargando examenes...</p>

        <div x-show="estado === 'error'" x-cloak>
            <x-ui.alert type="danger" title="No se pudieron cargar los examenes">
                Ocurrio un problema al consultar el listado.
                <div class="mt-2">
                    <x-ui.button variant="secondary" @click="cargar()">Reintentar</x-ui.button>
                </div>
            </x-ui.alert>
        </div>

        <p class="px-3 text-sm text-body" x-show="estado === 'listo' && examenesDisponibles.length === 0">
            No hay examenes futuros para mostrar.
        </p>

        <div x-show="estado === 'listo' && examenesDisponibles.length > 0" class="flex flex-col gap-4">
            <div class="min-w-0 max-w-md">
                <label for="examen-dividir" class="block mb-2.5 text-sm font-medium text-heading">Examen</label>
                <select id="examen-dividir" x-model="examenDivisionId"
                        @change="criterioDivision = ''; errorDivision = ''"
                        class="block w-full px-3 py-2.5 border text-sm rounded-base shadow-xs bg-neutral-secondary-medium border-default-medium text-heading focus:ring-brand focus:border-brand">
                    <option value="" disabled>Selecciona un examen</option>
                    <template x-for="examen in examenesDisponibles" :key="'dividir-' + examen.id">
                        <option :value="examen.id" x-text="etiquetaExamen(examen)"></option>
                    </template>
                </select>
            </div>

            <template x-if="examenDivisionSeleccionado && ! puedeDividir">
                <p class="px-3 text-sm text-body">
                    No se puede dividir el examen, solo se cuenta con un aula
                </p>
            </template>

            <template x-if="puedeDividir">
                <div class="flex flex-col gap-4">
                    <div class="flex flex-wrap items-baseline gap-x-2 px-3">
                        <h3 class="text-xl font-bold text-heading" x-text="examenDivisionSeleccionado?.tipo || 'Sin tipo'"></h3>
                        <p class="text-sm font-medium text-body" x-show="examenDivisionSeleccionado?.curso" x-text="'— ' + examenDivisionSeleccionado?.curso"></p>
                    </div>

                    <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
                        <div class="min-w-0 flex-1 sm:max-w-xs">
                            <x-ui.select
                                label="Dividir por"
                                name="division_por"
                                x-model="criterioDivision"
                                @change="errorDivision = ''"
                                :options="$opcionesDivision"
                                placeholder="Selecciona un criterio"
                            />
                        </div>

                        <x-ui.button
                            variant="default"
                            @click="abrirModalDivision()"
                            x-bind:disabled="criterioDivision === ''"
                            x-bind:class="criterioDivision === '' ? 'opacity-50 cursor-not-allowed' : ''"
                        >
                            Dividir
                        </x-ui.button>
                    </div>

                    <div x-show="errorDivision !== ''" x-cloak>
                        <x-ui.alert type="danger" title="No se pudo preparar la division">
                            <span x-text="errorDivision"></span>
                        </x-ui.alert>
                    </div>
                </div>
            </template>
        </div>
    </section>
    </div>

    {{-- Modal: invitar docente a compartir el examen seleccionado. --}}
    <div x-show="modalInvitar" x-cloak class="overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 flex justify-center items-center w-full md:inset-0 h-full max-h-full bg-overlay-modal/50" @click.self="cerrarModalInvitar()" @keydown.escape.window="cerrarModalInvitar()">
        <div class="relative p-4 w-full max-w-2xl max-h-full">
            <div class="relative bg-neutral-primary-soft border border-default rounded-base shadow-sm p-4 md:p-6">
                <div class="flex items-start justify-between gap-3 border-b border-default pb-4">
                    <div>
                        <h3 class="text-lg font-medium text-heading">Invitar docente</h3>
                        <p class="text-sm text-muted">Busca por SIS, nombre o correo y selecciona un docente.</p>
                    </div>
                    <button type="button" @click="cerrarModalInvitar()" class="text-body bg-transparent hover:bg-neutral-tertiary hover:text-heading rounded-base text-sm w-9 h-9 ms-auto inline-flex justify-center items-center cursor-pointer">
                        <svg class="w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 17.94 6M18 18 6.06 6"/></svg>
                    </button>
                </div>

                <div class="py-4 space-y-4">
                    <div class="flex gap-2">
                        <input type="text" x-model="busquedaDocente" @input="programarBusqueda()" @keydown.enter.prevent="buscarDocentesAhora()" placeholder="SIS, nombre o correo" class="w-full rounded-base border border-default bg-neutral-primary-soft px-3 py-2 text-sm text-heading" />
                        <button type="button" @click="buscarDocentesAhora()" class="inline-flex items-center justify-center rounded-base border border-default px-4 py-2 text-sm font-medium text-heading hover:bg-neutral-secondary-medium">Buscar</button>
                    </div>

                    <p x-show="buscandoDocentes" class="text-sm text-body">Buscando docentes...</p>
                    <p x-show="errorBusqueda" x-text="errorBusqueda" class="text-sm text-fg-danger-strong"></p>
                    <p x-show="errorInvitacion" x-text="errorInvitacion" class="text-sm text-fg-danger-strong"></p>

                    <div class="max-h-72 overflow-y-auto space-y-2">
                        <template x-for="docente in docentes" :key="docente.id">
                            <button type="button"
                                    @click="seleccionarDocente(docente)"
                                    :class="docenteSeleccionado && docenteSeleccionado.id === docente.id ? 'border-brand ring-2 ring-brand' : 'border-default hover:border-brand-subtle'"
                                    class="w-full text-left rounded-base border bg-neutral-primary-soft px-4 py-3 shadow-xs">
                                <p class="font-medium text-heading" x-text="docente.nombre"></p>
                                <p class="text-sm text-body" x-text="docente.correo || ''"></p>
                                <p class="text-xs font-mono text-muted" x-text="'SIS: ' + (docente.sis || docente.id)"></p>
                            </button>
                        </template>
                    </div>
                </div>

                <div class="border-t border-default pt-4 flex flex-col gap-2 sm:flex-row sm:justify-end">
                    <button type="button" @click="cerrarModalInvitar()" class="inline-flex items-center justify-center rounded-base border border-default px-4 py-2 text-sm font-medium text-heading hover:bg-neutral-secondary-medium">Cancelar</button>
                    <button type="button" @click="enviarInvitacion()" :disabled="!docenteSeleccionado || enviandoInvitacion" class="inline-flex items-center justify-center rounded-base border border-transparent bg-brand px-4 py-2 text-sm font-medium text-white hover:bg-brand-strong disabled:cursor-not-allowed disabled:opacity-50">
                        <span x-text="enviandoInvitacion ? 'Enviando...' : 'Invitar'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal: invitaciones enviadas del examen. --}}
    <div x-show="modalInvitaciones" x-cloak class="overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 flex justify-center items-center w-full md:inset-0 h-full max-h-full bg-overlay-modal/50" @click.self="cerrarModalInvitaciones()" @keydown.escape.window="cerrarModalInvitaciones()">
        <div class="relative p-4 w-full max-w-2xl max-h-full">
            <div class="relative bg-neutral-primary-soft border border-default rounded-base shadow-sm p-4 md:p-6">
                <div class="flex items-start justify-between gap-3 border-b border-default pb-4">
                    <div>
                        <h3 class="text-lg font-medium text-heading">Invitaciones enviadas</h3>
                        <p class="text-sm text-muted">Cancela las que sigan pendientes.</p>
                    </div>
                    <button type="button" @click="cerrarModalInvitaciones()" class="text-body bg-transparent hover:bg-neutral-tertiary hover:text-heading rounded-base text-sm w-9 h-9 ms-auto inline-flex justify-center items-center cursor-pointer">
                        <svg class="w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 17.94 6M18 18 6.06 6"/></svg>
                    </button>
                </div>
                <div class="py-4 space-y-2 max-h-72 overflow-y-auto">
                    <p x-show="cargandoInvitaciones" class="text-sm text-body">Cargando invitaciones...</p>
                    <template x-for="invitacion in invitaciones" :key="invitacion.id">
                        <div class="rounded-base border border-default bg-neutral-primary-soft px-4 py-3 shadow-xs flex items-center justify-between gap-3">
                            <div>
                                <p class="font-medium text-heading" x-text="invitacion.invitado?.nombre || ''"></p>
                                <p class="text-xs text-muted" x-text="invitacion.estado"></p>
                            </div>
                            <button type="button" x-show="invitacion.estado === 'pendiente'" @click="cancelarInvitacion(invitacion.id)" class="inline-flex items-center justify-center rounded-base border border-default px-3 py-1.5 text-xs font-medium text-heading hover:bg-neutral-secondary-medium">Cancelar</button>
                        </div>
                    </template>
                    <p x-show="!cargandoInvitaciones && invitaciones.length === 0" class="text-sm text-body">No hay invitaciones enviadas.</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal de exito de invitacion. --}}
    <div x-show="modalExitoInvitacion" x-cloak class="overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 flex justify-center items-center w-full md:inset-0 h-full max-h-full bg-overlay-modal/50" @click.self="modalExitoInvitacion = false" @keydown.escape.window="modalExitoInvitacion = false">
        <div class="relative p-4 w-full max-w-md max-h-full">
            <div class="relative bg-neutral-primary-soft border border-default rounded-base shadow-sm p-4 md:p-6 text-center">
                <h3 class="text-lg font-medium text-heading">Invitacion enviada con exito</h3>
                <p class="mt-2 text-sm text-body" x-text="resumenInvitacion ? (resumenInvitacion.invitado?.nombre || '') : ''"></p>
                <p class="mt-1 text-xs text-muted" x-text="resumenInvitacion ? ((resumenInvitacion.examen?.tipo || 'Examen') + ' - ' + (resumenInvitacion.examen?.materias || []).join(', ')) : ''"></p>
                <div class="mt-4">
                    <button type="button" @click="modalExitoInvitacion = false" class="inline-flex items-center justify-center rounded-base border border-transparent bg-brand px-4 py-2 text-sm font-medium text-white hover:bg-brand-strong">Aceptar</button>
                </div>
            </div>
        </div>
    </div>
</div>
    {{-- Modales de division de examen --}}
    <x-ui.modal-dividir-examen />
    <x-ui.modal-division-exitosa />
