{{--
    @file    monitoreo.blade.php
    @author  David E. Chavez T. <virzuzz12345@gmail.com>
    @created 2026-09-29
    @updated 2026-09-29

    @description
    Vista de monitoreo en vivo. Muestra las estadísticas del examen,
    el registro de ingresos de estudiantes y el modal de registro de ingreso.

    @changelog
    - 2026-09-29  [David E. Chavez T.]  feat: vista de monitoreo en vivo.
--}}

@extends('layouts.app')

@section('title', 'Monitor en vivo')

@section('content')
    @php
        $estadoBadge = fn (string $estado) => match ($estado) {
            'Pendiente' => '<span class="text-xs font-medium px-2 py-1 rounded-full border-2 border-gray-400 bg-gray-100 text-gray-400">Pendiente</span>',
            'Ingresó' => '<span class="text-xs font-medium px-2 py-1 rounded-full border-2 border-blue-300 bg-blue-50 text-blue-300">Ingresó</span>',
            'Sospechoso' => '<span class="text-xs font-medium px-2 py-1 rounded-full border-2 border-yellow-400 bg-yellow-100 text-yellow-400">Sospechoso</span>',
            'Rechazado' => '<span class="text-xs font-medium px-2 py-1 rounded-full border-2 border-red-300 bg-red-50 text-red-300">Rechazado</span>',
            'C. de Riesgos' => '<span class="text-xs font-medium px-2 py-1 rounded-full border-2 border-red-500 bg-red-100 text-red-500">C. de Riesgos</span>',
            'Ausente' => '<span class="text-xs font-medium px-2 py-1 rounded-full border-2 border-gray-500 bg-gray-100 text-gray-500">Ausente</span>',
            default => '<span class="text-xs font-medium px-2 py-1 rounded-full border-2 border-gray-400 bg-gray-100 text-gray-400">'.$estado.'</span>',
        };

        $registrarBtn = function (string $nombre, string $sis, string $registro, string $estado): string {
            $clases = 'inline-flex items-center justify-center box-border border border-transparent focus:ring-4 focus:ring-brand-medium shadow-xs font-medium leading-5 rounded-base focus:outline-none text-white bg-brand hover:bg-brand-strong px-3 py-1.5 text-xs';

            if ($estado === 'Pendiente') {
                return sprintf(
                    '<button type="button" class="%s" @click="$dispatch(\'abrir-modal-ingreso\', { nombre: \'%s\', sis: \'%s\' })">Ingreso</button>',
                    $clases,
                    e($nombre),
                    e($sis),
                );
            }

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

    <div class="flex flex-wrap gap-[2vh] px-[2vh]">
        <div class="flex-1 min-w-[200px] flex-1 min-w-[220px] flex items-center justify-between gap-3 bg-neutral-primary-soft p-6 border border-default rounded-base shadow-xs">
            <div>
                <p class="text-sm text-body">En curso</p>
                <p class="text-3xl font-semibold text-fg-brand">{{ substr($examen->hora_inicio, 0, 5) }} – {{ substr($examen->hora_fin, 0, 5) }}</p>
            </div>
            <span class="flex items-center justify-center w-12 h-12 rounded-base bg-neutral-secondary-soft text-fg-brand">
                <svg class="w-6 h-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
            </span>
        </div>
        <div class="flex-1 min-w-[200px] flex items-center justify-between gap-3 bg-neutral-primary-soft p-6 border border-default rounded-base shadow-xs">
            <div>
                <p class="text-3xl font-semibold text-fg-brand">{{ $conteos['inscritos'] }}</p>
                <p class="text-sm text-body">Inscritos</p>
            </div>
            <span class="flex items-center justify-center w-12 h-12 rounded-base bg-neutral-secondary-soft text-fg-brand">
                <svg class="w-6 h-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-width="2" d="M16 19h4a1 1 0 0 0 1-1v-1a3 3 0 0 0-3-3h-2m-2.236-4a3 3 0 1 0 0-4M3 18v-1a3 3 0 0 1 3-3h4a3 3 0 0 1 3 3v1a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1Zm8-10a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>
            </span>
        </div>
        <div class="flex-1 min-w-[200px] flex items-center justify-between gap-3 bg-neutral-primary-soft p-6 border border-default rounded-base shadow-xs">
            <div>
                <p class="text-3xl font-semibold text-fg-success">{{ $conteos['habilitados'] }}</p>
                <p class="text-sm text-body">Habilitados</p>
            </div>
            <span class="flex items-center justify-center w-12 h-12 rounded-base bg-neutral-secondary-soft text-fg-success">
                <svg class="w-6 h-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18Zm-3-9 2 2 4-4"/></svg>
            </span>
        </div>
        <div class="flex-1 min-w-[200px] flex items-center justify-between gap-3 bg-neutral-primary-soft p-6 border border-default rounded-base shadow-xs">
            <div>
                <p class="text-3xl font-semibold text-fg-danger-strong">{{ $conteos['deshabilitados'] }}</p>
                <p class="text-sm text-body">Deshabilitados</p>
            </div>
            <span class="flex items-center justify-center w-12 h-12 rounded-base bg-neutral-secondary-soft text-fg-danger-strong">
                <svg class="w-6 h-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 17.94 6M18 18 6.06 6"/></svg>
            </span>
        </div>
        <div class="flex-1 min-w-[200px] flex items-center justify-between gap-3 bg-neutral-primary-soft p-6 border border-default rounded-base shadow-xs">
            <div>
                <p class="text-3xl font-semibold text-fg-warning">{{ $conteos['sospechosos'] }}</p>
                <p class="text-sm text-body">Sospechosos</p>
            </div>
            <span class="flex items-center justify-center w-12 h-12 rounded-base bg-neutral-secondary-soft text-fg-warning">
                <svg class="w-6 h-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-width="2" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/><path stroke="currentColor" stroke-width="2" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>
            </span>
        </div>
        <div class="flex-1 min-w-[200px] flex items-center justify-between gap-3 bg-neutral-primary-soft p-6 border border-default rounded-base shadow-xs">
            <div>
                <p class="text-3xl font-semibold text-fg-danger-strong">{{ $conteos['tramposos'] }}</p>
                <p class="text-sm text-body">Tramposos</p>
            </div>
            <span class="flex items-center justify-center w-12 h-12 rounded-base bg-neutral-secondary-soft text-fg-danger-strong">
                <svg class="w-6 h-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3 5 6v5c0 4.2 2.8 7.6 7 9 4.2-1.4 7-4.8 7-9V6l-7-3Z"/><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.5 9.5l5 5m0-5-5 5"/></svg>
            </span>
        </div>
    </div>

    <div class="mt-6 mx-[2vh] bg-neutral-primary-soft border border-default rounded-base shadow-xs p-6 px-[2vh]"
        x-data="{
            showModalIngreso: false,
            nombreIngreso: '',
            sisIngreso: '',
            horaIngreso: '',
            errorIngreso: '',
            abrirModalIngreso(nombre, sis) {
                this.nombreIngreso = nombre;
                this.sisIngreso = sis;
                this.horaIngreso = '';
                this.errorIngreso = '';
                this.showModalIngreso = true;
            }
        }"
        @cerrar-modal-ingreso.window="showModalIngreso = false"
        @confirmar-ingreso.window="showModalIngreso = false; alert('Ingreso registrado para ' + nombreIngreso)"
    >
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div class="w-full sm:max-w-md">
                <x-ui.search-input placeholder="Buscar por nombre o código SIS..." :show-button="false" />
            </div>

            <div class="flex flex-wrap gap-2">
                <x-ui.button variant="default">Todos</x-ui.button>
                <x-ui.button variant="secondary">Habilitados</x-ui.button>
                <x-ui.button variant="secondary">Deshabilitados</x-ui.button>
                <x-ui.button variant="secondary">Sospechosos</x-ui.button>
                <x-ui.button variant="secondary">Tramposos</x-ui.button>
            </div>
        </div>

        <h2 class="mt-6 text-lg font-semibold text-heading">Registro de ingresos</h2>

        <div class="mt-4">
            @php
                $filas = [];
                foreach ($estudiantes as $estudiante) {
                    $estadoVisual = match ($estudiante['estado_asistencia']) {
                        'presente' => $estudiante['observaciones'] === 'deshabilitado' ? 'Rechazado' : 'Ingresó',
                        'pendiente' => 'Pendiente',
                        'ausente' => $estudiante['observaciones'] === 'deshabilitado' ? 'Rechazado' : 'Ausente',
                        default => 'C. de Riesgos',
                    };
                    $filas[] = [
                        ['heading' => true, 'value' => $estudiante['nombre'].' '.$estudiante['apellido']],
                        $estudiante['sis'],
                        $estudiante['hora_ingreso'] ? substr($estudiante['hora_ingreso'], 0, 5) : '—',
                        $estudiante['registrador'] ?? '—',
                        ['html' => $estadoBadge($estadoVisual)],
                        ['html' => $registrarBtn($estudiante['nombre'].' '.$estudiante['apellido'], $estudiante['sis'], $estudiante['registrador'] ?? '—', $estadoVisual)],
                    ];
                }
            @endphp
            <x-ui.table
                :headers="['Estudiante', 'Código SIS', 'Hora', 'Registro', 'Estado', 'Registrar']"
                :rows="$filas"
            />
        </div>

        <div class="mt-6 flex justify-end">
            <x-ui.pagination
                :current="$estudiantes->currentPage()"
                :total="$estudiantes->lastPage()"
                :href="request()->url()"
            />
        </div>

        <x-ui.modal-registro-ingreso />
    </div>
</div>
@endsection