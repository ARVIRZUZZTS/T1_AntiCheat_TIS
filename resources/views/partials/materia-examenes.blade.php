{{--
    @file    materia-examenes.blade.php
    @author  Alex Candia <alex.leonar.candia@gmail.com>
    @created 2026-10-05
    @updated 2026-10-09

    @description
    Sección "Exámenes" de la vista de detalle de una materia: lista los exámenes
    del curso en ítems con el tipo, el estado (Programado o Finalizado), la fecha
    programada, el horario, los ambientes asignados y la cantidad de estudiantes
    que registraron su ingreso, y muestra el botón "Crear examen", que abre el
    modal de alta.

    El listado no llega con la página: el parcial declara un x-data de Alpine
    que consulta GET /api/cursos/{idCurso}/examenes (CursoExamenController)
    cuando se inicializa —la sección vive en un template x-if de la página, así
    que solo se monta al abrir la pestaña— y dibuja los tres estados: cargando
    (placeholders), error (con botón de reintento) y listo (con los ítems o el
    vacío). Es el primer uso de fetch del proyecto; los datos del modal
    (ambientes, materiales y normas) siguen llegando resueltos por la ruta
    materias.detalle.

    Los estados "Programado"/"Finalizado" vienen tal cual del endpoint, que
    colapsa el "en curso" interno del servicio en "Programado".

    @changelog
    - 2026-10-05  [Alex Candia]  feat: creación inicial de la sección.
    - 2026-10-05  [Alex Candia]  feat: la sección pasa el catálogo de ambientes al
      modal de alta, que los carga en su buscador.
    - 2026-10-05  [Alex Candia]  feat: la sección pasa también el catálogo de
      materiales y el de normas, que el modal muestra como casillas.
    - 2026-10-09  [T1]  feat: el listado pasa a consumir con fetch el endpoint
      /api/cursos/{idCurso}/examenes al abrir la pestaña; el ítem muestra
      ambientes e ingresados (antes duración e inscritos), con el estado
      debajo del tipo y en dos etiquetas: Programado y Finalizado.

    @see  App\Http\Controllers\Api\CursoExamenController
    @see  App\Services\Ambiente\ListarAmbientesService
    @see  App\Services\Material\ListarMaterialesService
    @see  App\Services\Norma\ListarNormasService
    @see  resources/views/components/ui/modal-crear-examen.blade.php
    @see  resources/views/pages/materia-estudiantes.blade.php
--}}

<div class="flex flex-col gap-[2vh]"
     x-data="{
         examenes: [],
         estado: 'cargando',
         async cargar() {
             this.estado = 'cargando';
             try {
                 const respuesta = await fetch('{{ route('api.cursos.examenes.listar', ['idCurso' => $curso->id_curso]) }}');
                 if (!respuesta.ok) {
                     throw new Error('HTTP ' + respuesta.status);
                 }
                 const cuerpo = await respuesta.json();
                 this.examenes = cuerpo.datos;
                 this.estado = 'listo';
             } catch (error) {
                 this.estado = 'error';
             }
         },
         init() {
             this.cargar();
         },
         horario(examen) {
             return [examen.hora_inicio, examen.hora_fin].filter(Boolean).join(' – ') || '—';
         },
         ambientesDe(examen) {
             return (examen.ambientes || []).map((ambiente) => ambiente.nombre).join(', ') || '—';
         },
     }">
    {{-- Aviso de éxito del alta. Los errores de validación se muestran dentro
         del modal, que además se reabre solo (ver `abrir` más abajo). --}}
    @if (session('mensaje'))
        <x-ui.alert type="success">{{ session('mensaje') }}</x-ui.alert>
    @endif

    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="min-w-0">
            <h2 class="text-xl font-semibold text-heading">Exámenes</h2>
        </div>

        <x-ui.button variant="default" class="shrink-0" @click="$dispatch('abrir-modal-crear-examen')">
            <svg class="w-4 h-4 me-1.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-width="2" d="M12 5v14m7-7H5"/></svg>
            Crear examen
        </x-ui.button>
    </div>

    {{-- Cargando: placeholders con el mismo contorno que los ítems. --}}
    <div class="flex flex-col gap-[2vh]" x-show="estado === 'cargando'">
        <div class="p-4 sm:p-6 bg-neutral-primary-soft border border-default rounded-base shadow-xs animate-pulse" aria-hidden="true">
            <div class="h-5 w-44 rounded bg-neutral-secondary-medium"></div>
            <div class="mt-4 h-4 w-2/3 rounded bg-neutral-secondary-medium"></div>
        </div>
        <div class="p-4 sm:p-6 bg-neutral-primary-soft border border-default rounded-base shadow-xs animate-pulse" aria-hidden="true">
            <div class="h-5 w-40 rounded bg-neutral-secondary-medium"></div>
            <div class="mt-4 h-4 w-1/2 rounded bg-neutral-secondary-medium"></div>
        </div>
    </div>

    {{-- Error de la consulta, con reintento. --}}
    <div x-show="estado === 'error'" x-cloak>
        <x-ui.alert type="danger" title="No se pudieron cargar los exámenes">
            Ocurrió un problema al consultar el listado.
            <div class="mt-2">
                <x-ui.button variant="secondary" @click="cargar()">Reintentar</x-ui.button>
            </div>
        </x-ui.alert>
    </div>

    {{-- Listado: los ítems salen del JSON del endpoint, no de Blade. --}}
    <ul class="flex flex-col gap-[2vh]" x-show="estado === 'listo'" x-cloak>
        <template x-for="examen in examenes" :key="examen.id">
            <li class="p-4 sm:p-6 bg-neutral-primary-soft border border-default rounded-base shadow-xs">
                <h3 class="text-lg font-semibold text-heading" x-text="examen.tipo || 'Sin tipo'"></h3>

                <div class="mt-1.5">
                    <x-ui.badge type="brand" x-show="examen.estado === 'Programado'" x-text="examen.estado"></x-ui.badge>
                    <x-ui.badge type="gray" x-show="examen.estado === 'Finalizado'" x-text="examen.estado"></x-ui.badge>
                </div>

                <dl class="mt-4 flex flex-wrap items-baseline gap-x-10 gap-y-3 text-base">
                    <div class="min-w-0">
                        <dt class="text-sm text-muted font-semibold">Fecha</dt>
                        <dd class="text-heading mt-2">
                            <time :datetime="examen.fecha" x-show="examen.fecha" x-text="examen.fecha"></time>
                            <span x-show="!examen.fecha">—</span>
                        </dd>
                    </div>

                    <div class="min-w-0">
                        <dt class="text-sm text-muted font-semibold">Horario</dt>
                        <dd class="text-heading mt-2" x-text="horario(examen)"></dd>
                    </div>

                    <div class="min-w-0">
                        <dt class="text-sm text-muted font-semibold">Ambientes</dt>
                        <dd class="text-heading mt-2" x-text="ambientesDe(examen)"></dd>
                    </div>

                    <div class="min-w-0">
                        <dt class="text-sm text-muted font-semibold">Ingresos</dt>
                        <dd class="text-heading mt-2" x-text="examen.ingresados"></dd>
                    </div>
                </dl>
            </li>
        </template>

        <li x-show="examenes.length === 0"
            class="flex flex-col items-center gap-2 p-8 text-center bg-neutral-primary-soft border border-default rounded-base shadow-xs">
            <svg class="w-6 h-6 text-fg-disabled" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-width="2" d="M4 10h16m-8-3V4M7 7V4m10 3V4M5 20h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2Zm3-7h.01v.01H8V13Zm4 0h.01v.01H12V13Zm4 0h.01v.01H16V13Zm-8 4h.01v.01H8V17Zm4 0h.01v.01H12V17Zm4 0h.01v.01H16V17Z"/></svg>
            <p class="text-sm text-body">Esta materia todavía no tiene exámenes programados.</p>
        </li>
    </ul>

    <x-ui.modal-crear-examen
        :materia="$curso->nombre_curso"
        :ambientes="$ambientes"
        :materiales="$materiales"
        :normas="$normas"
        :curso-id="$curso->id_curso"
        :abrir="$errors->any()"
    />
</div>
