{{--
    @file    modal-estudiante-ingresado.blade.php
    @author  David E. Chavez T. <virzuzz12345@gmail.com>
    @created 2026-10-10
    @updated 2026-10-10

    @description
    Componente UI Blade reutilizable para el modal de estudiante ingresado
    (M2). Confirma que el estudiante ingresó al examen y permite registrar
    una incidencia sobre él. Se abre con el evento 'abrir-modal-ingresado'
    y delega la apertura del modal de tramposo con 'registrar-incidencia'.

    @changelog
    - 2026-10-10  [David E. Chavez T.]  feat: creación inicial del componente.

    @see  resources/views/pages/monitoreo.blade.php
    @see  plan_modales.md (sección 5)
--}}

@props([])

<div
    x-data="{
        show: false,
        nombre: '',
        sis: '',
        materia: '',
        aula: '',
        habilitacion: '',
        horaRegistro: '',
        materialPermitido: '',
        registrador: '',
        abrir(datos) {
            datos = datos || {};
            this.nombre = datos.nombre || '';
            this.sis = datos.sis || '';
            this.materia = datos.materia || '';
            this.aula = datos.aula || '';
            this.habilitacion = datos.habilitacion || 'Habilitada';
            this.horaRegistro = datos.horaRegistro || '';
            this.materialPermitido = datos.materialPermitido || '';
            this.registrador = datos.registrador || '';
            this.show = true;
        }
    }"
    x-show="show"
    x-cloak
    tabindex="-1"
    role="dialog"
    aria-modal="true"
    aria-labelledby="titulo-modal-ingresado"
    @abrir-modal-ingresado.window="abrir($event.detail)"
    @cerrar-modal-ingresado.window="show = false"
    @click.self="show = false"
    @keydown.escape.window="show = false"
    class="overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 flex justify-center items-center w-full md:inset-0 h-full max-h-full bg-overlay-modal/50"
>
    <div class="relative p-4 w-full max-w-md max-h-full">
        <div class="relative bg-neutral-primary-soft border border-default rounded-base shadow-sm p-4 md:p-6">
            {{-- Cabecera --}}
            <div class="flex items-start justify-between gap-3 border-b border-default pb-4 md:pb-5">
                <div>
                    <h3 id="titulo-modal-ingresado" class="text-lg font-medium text-heading">Estudiante Ingresado</h3>
                    <p class="text-sm text-muted"><span x-text="materia"></span> · aula <span x-text="aula"></span></p>
                </div>
                <button
                    type="button"
                    @click="show = false"
                    class="text-body bg-transparent hover:bg-neutral-tertiary hover:text-heading rounded-base text-sm w-9 h-9 ms-auto inline-flex justify-center items-center cursor-pointer"
                >
                    <svg class="w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 17.94 6M18 18 6.06 6"/></svg>
                    <span class="sr-only">Cerrar modal</span>
                </button>
            </div>

            {{-- Cuerpo --}}
            <div class="space-y-4 py-4">
                @include('partials.modales.banner-estado', [
                    'variante' => 'ingresado',
                    'titulo' => 'Estudiante Ingresado',
                    'subtitulo' => 'Estudiante ingreso a dar Examen',
                ])

                @include('partials.modales.ficha-estudiante', [
                    'nombre' => 'nombre',
                    'sis' => 'sis',
                    'tone' => 'brand',
                ])

                @include('partials.modales.filas-detalle', [
                    'filas' => [
                        ['label' => 'Habilitación', 'valor' => 'habilitacion'],
                        ['label' => 'Aula asignada', 'valor' => 'aula'],
                        ['label' => 'Hora de registro', 'valor' => 'horaRegistro'],
                        ['label' => 'Material permitido', 'valor' => 'materialPermitido'],
                        ['label' => 'Registrador', 'valor' => 'registrador'],
                    ],
                ])
            </div>

            {{-- Pie --}}
            <div class="border-t border-default pt-4 space-y-[2vh]">
                <x-ui.button
                    variant="danger"
                    class="w-full"
                    @click="$dispatch('registrar-incidencia', { nombre: nombre, sis: sis, materia: materia, aula: aula }); show = false"
                >Registrar incidencia</x-ui.button>
                <x-ui.button-cancelar class="w-full" @click="show = false">Cancelar</x-ui.button-cancelar>
            </div>
        </div>
    </div>
</div>
