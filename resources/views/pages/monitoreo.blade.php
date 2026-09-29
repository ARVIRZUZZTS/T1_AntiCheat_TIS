@extends('layouts.app')

@section('title', 'Monitor en vivo')

@section('content')
    <p class="text-body">
        Sesiones de monitoreo del proctor. Datos mockeados: vendrán del endpoint
        <code class="text-fg-brand">/monitoreo</code> (con <code class="text-fg-brand">wire:poll</code> en vivo).
    </p>

    @php
        $estadoBadge = fn (string $estado) => match ($estado) {
            'Habilitado' => '<span class="text-xs font-medium px-1.5 py-0.5 rounded-full bg-status-habilitado-bg text-status-habilitado-fg">Habilitado</span>',
            'Deshabilitado' => '<span class="text-xs font-medium px-1.5 py-0.5 rounded-full bg-status-no-habilitado-bg text-status-no-habilitado-fg">Deshabilitado</span>',
            'Pendiente' => '<span class="text-xs font-medium px-1.5 py-0.5 rounded-full bg-status-pendiente-bg text-status-pendiente-fg">Pendiente</span>',
            'Ausente' => '<span class="text-xs font-medium px-1.5 py-0.5 rounded-full bg-neutral-tertiary-soft text-body">Ausente</span>',
            'Sospechoso' => '<span class="text-xs font-medium px-1.5 py-0.5 rounded-full bg-status-en-revision-bg text-status-en-revision-fg">Sospechoso</span>',
            default => '<span class="text-xs font-medium px-1.5 py-0.5 rounded-full bg-status-central-riesgos-bg text-status-central-riesgos-fg">Tramposo</span>',
        };

        /* Boton de reporte de una fila: lleva al formulario de registro de
           incidencias con el estudiante y el rol de quien lo registro, que es
           el dato que decide si la incidencia queda confirmada o en revision.
           La materia no viaja porque el monitor no la muestra. */
        $registrarBtn = function (string $nombre, string $sis, string $registro): string {
            $clases = 'inline-flex items-center justify-center box-border border border-transparent focus:ring-4 focus:ring-brand-medium shadow-xs font-medium leading-5 rounded-base focus:outline-none text-white bg-brand hover:bg-brand-strong px-3 py-1.5 text-xs';

            return sprintf(
                '<a href="%s" class="%s">Reporte</a>',
                e(route('registrar-incidencia', [
                    'origen' => 'monitoreo',
                    'nombre' => $nombre,
                    'sis' => $sis,
                    'rol' => str_starts_with($registro, 'Aux.') ? 'auxiliar' : 'docente',
                ])),
                $clases,
            );
        };
    @endphp

    <div class="flex flex-wrap gap-[2vh]">
        <div class="flex-1 min-w-[200px] flex-1 min-w-[220px] flex items-center justify-between gap-3 bg-neutral-primary-soft p-6 border border-default rounded-base shadow-xs">
            <div>
                <p class="text-xl font-semibold text-fg-brand">08:00 – 11:00</p>
                <p class="text-sm text-body">En curso</p>
            </div>
            <span class="flex items-center justify-center w-12 h-12 rounded-base bg-neutral-secondary-soft text-fg-brand">
                <svg class="w-6 h-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
            </span>
        </div>
        <div class="flex-1 min-w-[200px] flex items-center justify-between gap-3 bg-neutral-primary-soft p-6 border border-default rounded-base shadow-xs">
            <div>
                <p class="text-3xl font-semibold text-fg-brand">60</p>
                <p class="text-sm text-body">Inscritos</p>
            </div>
            <span class="flex items-center justify-center w-12 h-12 rounded-base bg-neutral-secondary-soft text-fg-brand">
                <svg class="w-6 h-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-width="2" d="M16 19h4a1 1 0 0 0 1-1v-1a3 3 0 0 0-3-3h-2m-2.236-4a3 3 0 1 0 0-4M3 18v-1a3 3 0 0 1 3-3h4a3 3 0 0 1 3 3v1a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1Zm8-10a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>
            </span>
        </div>
        <div class="flex-1 min-w-[200px] flex items-center justify-between gap-3 bg-neutral-primary-soft p-6 border border-default rounded-base shadow-xs">
            <div>
                <p class="text-3xl font-semibold text-fg-success">54</p>
                <p class="text-sm text-body">Habilitados</p>
            </div>
            <span class="flex items-center justify-center w-12 h-12 rounded-base bg-neutral-secondary-soft text-fg-success">
                <svg class="w-6 h-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18Zm-3-9 2 2 4-4"/></svg>
            </span>
        </div>
        <div class="flex-1 min-w-[200px] flex items-center justify-between gap-3 bg-neutral-primary-soft p-6 border border-default rounded-base shadow-xs">
            <div>
                <p class="text-3xl font-semibold text-fg-danger-strong">4</p>
                <p class="text-sm text-body">Deshabilitados</p>
            </div>
            <span class="flex items-center justify-center w-12 h-12 rounded-base bg-neutral-secondary-soft text-fg-danger-strong">
                <svg class="w-6 h-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 17.94 6M18 18 6.06 6"/></svg>
            </span>
        </div>
        <div class="flex-1 min-w-[200px] flex items-center justify-between gap-3 bg-neutral-primary-soft p-6 border border-default rounded-base shadow-xs">
            <div>
                <p class="text-3xl font-semibold text-fg-warning">2</p>
                <p class="text-sm text-body">Sospechosos</p>
            </div>
            <span class="flex items-center justify-center w-12 h-12 rounded-base bg-neutral-secondary-soft text-fg-warning">
                <svg class="w-6 h-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-width="2" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/><path stroke="currentColor" stroke-width="2" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>
            </span>
        </div>
        <div class="flex-1 min-w-[200px] flex items-center justify-between gap-3 bg-neutral-primary-soft p-6 border border-default rounded-base shadow-xs">
            <div>
                <p class="text-3xl font-semibold text-fg-danger-strong">1</p>
                <p class="text-sm text-body">Tramposos</p>
            </div>
            <span class="flex items-center justify-center w-12 h-12 rounded-base bg-neutral-secondary-soft text-fg-danger-strong">
                <svg class="w-6 h-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3 5 6v5c0 4.2 2.8 7.6 7 9 4.2-1.4 7-4.8 7-9V6l-7-3Z"/><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.5 9.5l5 5m0-5-5 5"/></svg>
            </span>
        </div>
    </div>

    <div class="mt-6 bg-neutral-primary-soft border border-default rounded-base shadow-xs p-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div class="w-full sm:max-w-md">
                <x-ui.search-input placeholder="Buscar por nombre o código SIS..." :show-button="false" />
            </div>

            <div class="flex flex-wrap gap-2">
                <x-ui.button variant="default">Todos (60)</x-ui.button>
                <x-ui.button variant="secondary">Habilitados (54)</x-ui.button>
                <x-ui.button variant="secondary">Deshabilitados (4)</x-ui.button>
                <x-ui.button variant="secondary">Sospechosos (2)</x-ui.button>
                <x-ui.button variant="secondary">Tramposos (1)</x-ui.button>
            </div>
        </div>

        <h2 class="mt-6 text-lg font-semibold text-heading">Registro de ingresos</h2>

        <div class="mt-4">
            <x-ui.table
                :headers="['Estudiante', 'Código SIS', 'Hora', 'Registro', 'Estado', 'Registrar']"
                :rows="[
                    [['heading' => true, 'value' => 'Ana López'], '202201013', '08:12', 'Doc. Mariana G.', ['html' => $estadoBadge('Habilitado')], ['html' => $registrarBtn('Ana López', '202201013', 'Doc. Mariana G.')]],
                    [['heading' => true, 'value' => 'Bruno Díaz'], '202101022', '08:20', 'Aux. Jorge S.', ['html' => $estadoBadge('Sospechoso')], ['html' => $registrarBtn('Bruno Díaz', '202101022', 'Aux. Jorge S.')]],
                    [['heading' => true, 'value' => 'Carla Ruiz'], '202201031', '—', '—', ['html' => $estadoBadge('Pendiente')], ['html' => $registrarBtn('Carla Ruiz', '202201031', '—')]],
                    [['heading' => true, 'value' => 'Diego Soto'], '202202045', '—', '—', ['html' => $estadoBadge('Ausente')], ['html' => $registrarBtn('Diego Soto', '202202045', '—')]],
                    [['heading' => true, 'value' => 'Ernesto Vera'], '202002107', '07:58', 'Doc. Mariana G.', ['html' => $estadoBadge('Tramposo')], ['html' => $registrarBtn('Ernesto Vera', '202002107', 'Doc. Mariana G.')]],
                    [['heading' => true, 'value' => 'Fátima Quispe'], '202201056', '08:05', 'Aux. Jorge S.', ['html' => $estadoBadge('Deshabilitado')], ['html' => $registrarBtn('Fátima Quispe', '202201056', 'Aux. Jorge S.')]],
                ]"
            />
        </div>

        <div class="mt-6 flex justify-end">
            <x-ui.pagination :current="2" :total="5" />
        </div>
    </div>
@endsection