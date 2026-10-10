{{--
    @file    modal-no-habilitado.blade.php
    @author  David E. Chavez T. <virzuzz12345@gmail.com>
    @created 2026-10-10
    @updated 2026-10-10

    @description
    Componente UI Blade reutilizable para el modal de estudiante no
    habilitado (M3). Avisa que el docente no habilitó al estudiante y ofrece
    permitir el ingreso o rechazarlo. Se abre con el evento
    'abrir-modal-no-habilitado' y emite 'permitir-ingreso' o
    'rechazar-ingreso'.

    @changelog
    - 2026-10-10  [David E. Chavez T.]  feat: creación inicial del componente.

    @see  resources/views/pages/monitoreo.blade.php
    @see  plan_modales.md (sección 6)
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
        aulaActual: '',
        horaRegistro: '',
        materialPermitido: '',
        abrir(datos) {
            datos = datos || {};
            this.nombre = datos.nombre || '';
            this.sis = datos.sis || '';
            this.materia = datos.materia || '';
            this.aula = datos.aula || '';
            this.habilitacion = datos.habilitacion || 'No habilitado';
            this.aulaActual = datos.aulaActual || datos.aula || '';
            this.horaRegistro = datos.horaRegistro || '';
            this.materialPermitido = datos.materialPermitido || '';
            this.show = true;
        }
    }"
    x-show="show"
    x-cloak
    tabindex="-1"
    role="dialog"
    aria-modal="true"
    aria-labelledby="titulo-modal-no-habilitado"
    @abrir-modal-no-habilitado.window="abrir($event.detail)"
    @cerrar-modal-no-habilitado.window="show = false"
    @click.self="show = false"
    @keydown.escape.window="show = false"
    class="overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 flex justify-center items-center w-full md:inset-0 h-full max-h-full bg-overlay-modal/50"
>
    <div class="relative p-4 w-full max-w-md max-h-full">
        <div class="relative bg-neutral-primary-soft border border-default rounded-base shadow-sm p-4 md:p-6">
            {{-- Cabecera --}}
            <div class="flex items-start justify-between gap-3 border-b border-default pb-4 md:pb-5">
                <div>
                    <h3 id="titulo-modal-no-habilitado" class="text-lg font-medium text-heading">Registro de Ingreso</h3>
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
                    'variante' => 'no-habilitado',
                    'titulo' => 'No puede ingresar',
                    'subtitulo' => 'No habilitado por el docente',
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
                        ['label' => 'Aula actual', 'valor' => 'aulaActual'],
                        ['label' => 'Hora de registro', 'valor' => 'horaRegistro'],
                        ['label' => 'Material permitido', 'valor' => 'materialPermitido'],
                    ],
                ])
            </div>

            {{-- Pie --}}
            <div class="border-t border-default pt-4 space-y-[2vh]">
                <x-ui.button
                    variant="default"
                    class="w-full"
                    @click="$dispatch('permitir-ingreso', { sis: sis }); show = false"
                >Permitir ingreso a este examen</x-ui.button>
                <div class="flex gap-[2vh]">
                    <x-ui.button-cancelar class="flex-1" @click="show = false">Cancelar</x-ui.button-cancelar>
                    <x-ui.button
                        variant="danger"
                        class="flex-1"
                        @click="$dispatch('rechazar-ingreso', { sis: sis }); show = false"
                    >Rechazar</x-ui.button>
                </div>
            </div>
        </div>
    </div>
</div>
