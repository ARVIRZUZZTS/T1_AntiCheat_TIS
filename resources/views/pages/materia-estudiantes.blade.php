{{--
    @file    materia-estudiantes.blade.php
    @author  OchoaCesar <cesareduardonick@gmail.com>
    @created 2026-09-25
    @updated 2026-09-25

    @description
    Vista de detalle de una materia (vista mock): muestra las cards resumen y
    las pestañas Estudiantes / Habilitación / Exámenes / Auxiliares. La
    pestaña Estudiantes (principal) es una calca de pages/monitoreo[.blade].php
    con la columna "Registrar" reemplazada por "Motivo" y "Acciones"
    (Editar / Deshabilitar). Todo con datos mockeados hasta que existan los
    endpoints correspondientes.

    @see  pages/materias.blade.php
    @see  pages/monitoreo.blade.php
--}}

@php
    // Misma lista mock que pages/materias.blade.php; el id llega por la URL.
    $catalogo = [
        'MAT-101' => ['codigo' => 'MAT-101', 'nombre' => 'Matemática I', 'seccion' => 'A'],
        'FIS-201' => ['codigo' => 'FIS-201', 'nombre' => 'Física II', 'seccion' => 'B'],
        'QUM-301' => ['codigo' => 'QUM-301', 'nombre' => 'Química Orgánica', 'seccion' => 'C'],
        'INF-401' => ['codigo' => 'INF-401', 'nombre' => 'Programación IV', 'seccion' => 'A'],
    ];
    $materia = $catalogo[$codigo] ?? ['codigo' => $codigo, 'nombre' => $codigo, 'seccion' => '—'];

    $estadoBadge = fn (string $estado) => match ($estado) {
        'Habilitado' => '<span class="text-xs font-medium px-1.5 py-0.5 rounded-full bg-status-habilitado-bg text-status-habilitado-fg">Habilitado</span>',
        'Deshabilitado' => '<span class="text-xs font-medium px-1.5 py-0.5 rounded-full bg-status-no-habilitado-bg text-status-no-habilitado-fg">Deshabilitado</span>',
        'Pendiente' => '<span class="text-xs font-medium px-1.5 py-0.5 rounded-full bg-status-pendiente-bg text-status-pendiente-fg">Pendiente</span>',
        'Ausente' => '<span class="text-xs font-medium px-1.5 py-0.5 rounded-full bg-neutral-tertiary-soft text-body">Ausente</span>',
        'Sospechoso' => '<span class="text-xs font-medium px-1.5 py-0.5 rounded-full bg-status-en-revision-bg text-status-en-revision-fg">Sospechoso</span>',
        default => '<span class="text-xs font-medium px-1.5 py-0.5 rounded-full bg-status-central-riesgos-bg text-status-central-riesgos-fg">Tramposo</span>',
    };

    // Acciones: Editar (#1B3A73 → token fg-brand) y Deshabilitar (#D32027 → token fg-danger).
    $acciones = '<a href="#" class="font-medium text-fg-brand hover:underline">Editar</a>'
        . '<span class="text-neutral-tertiary-medium mx-1.5">·</span>'
        . '<a href="#" class="font-medium text-fg-danger hover:underline">Deshabilitar</a>';
@endphp

@extends('layouts.app')

@section('title', $materia['nombre'])

@section('content')
    <p class="text-body">
        Estudiantes de la materia {{ $materia['codigo'] }} — Sección {{ $materia['seccion'] }}.
        Datos mockeados: vendrán del endpoint
        <code class="text-fg-brand">/materias/{codigo}/estudiantes</code>.
    </p>

    <div class="flex flex-wrap gap-[2vh]">
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

    <div x-data="{ tab: 'estudiantes' }" class="mt-6">
        {{-- Pestañas de la materia (Alpine toggla el estado en runtime) --}}
        @php
            $tabClases = 'inline-flex items-center justify-center box-border border focus:ring-4 shadow-xs font-medium leading-5 rounded-base focus:outline-none px-4 py-2.5 text-sm';
            $tabActivo = 'text-white bg-brand border-transparent hover:bg-brand-strong focus:ring-brand-medium';
            $tabInactivo = 'text-body bg-neutral-secondary-medium border-default-medium hover:bg-neutral-tertiary-medium hover:text-heading focus:ring-neutral-tertiary';
        @endphp

        <nav class="flex flex-wrap gap-2 border-b border-default pb-4" aria-label="Secciones de la materia">
            <button type="button" @click="tab = 'estudiantes'" :class="tab === 'estudiantes' ? '{{ $tabActivo }}' : '{{ $tabInactivo }}'" class="{{ $tabClases }}">Estudiantes</button>
            <button type="button" @click="tab = 'habilitacion'" :class="tab === 'habilitacion' ? '{{ $tabActivo }}' : '{{ $tabInactivo }}'" class="{{ $tabClases }}">Habilitación</button>
            <button type="button" @click="tab = 'examenes'" :class="tab === 'examenes' ? '{{ $tabActivo }}' : '{{ $tabInactivo }}'" class="{{ $tabClases }}">Exámenes</button>
            <button type="button" @click="tab = 'auxiliares'" :class="tab === 'auxiliares' ? '{{ $tabActivo }}' : '{{ $tabInactivo }}'" class="{{ $tabClases }}">Auxiliares</button>
        </nav>

        {{-- Pestaña principal: Estudiantes (calca de monitoreo) --}}
        <section x-show="tab === 'estudiantes'" x-cloak class="mt-6 bg-neutral-primary-soft border border-default rounded-base shadow-xs p-6">
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
                    :headers="['Estudiante', 'Código SIS', 'Hora', 'Registro', 'Estado', 'Motivo', 'Acciones']"
                    :rows="[
                        [['heading' => true, 'value' => 'Ana López'], '202201013', '08:12', 'Doc. Mariana G.', ['html' => $estadoBadge('Habilitado')], '—', ['html' => $acciones]],
                        [['heading' => true, 'value' => 'Bruno Díaz'], '202101022', '08:20', 'Aux. Jorge S.', ['html' => $estadoBadge('Sospechoso')], '—', ['html' => $acciones]],
                        [['heading' => true, 'value' => 'Carla Ruiz'], '202201031', '—', '—', ['html' => $estadoBadge('Pendiente')], '—', ['html' => $acciones]],
                        [['heading' => true, 'value' => 'Diego Soto'], '202202045', '—', '—', ['html' => $estadoBadge('Ausente')], '—', ['html' => $acciones]],
                        [['heading' => true, 'value' => 'Ernesto Vera'], '202002107', '07:58', 'Doc. Mariana G.', ['html' => $estadoBadge('Tramposo')], 'Suplantación de identidad', ['html' => $acciones]],
                        [['heading' => true, 'value' => 'Fátima Quispe'], '202201056', '08:05', 'Aux. Jorge S.', ['html' => $estadoBadge('Deshabilitado')], 'No cumple requisitos', ['html' => $acciones]],
                    ]"
                />
            </div>

            <div class="mt-6 flex justify-end">
                <x-ui.pagination :current="2" :total="5" />
            </div>
        </section>

        {{-- Pestañas pendientes (mocks hasta tener endpoint) --}}
        <div x-show="tab === 'habilitacion'" x-cloak class="mt-6">
            <x-ui.card title="Habilitación (mock)">
                <p class="text-body">
                    Gestión de habilitación de estudiantes. Datos mockeados: vendrán del endpoint
                    <code class="text-fg-brand">/materias/{codigo}/habilitacion</code>.
                </p>
            </x-ui.card>
        </div>

        <div x-show="tab === 'examenes'" x-cloak class="mt-6">
            <x-ui.card title="Exámenes (mock)">
                <p class="text-body">
                    Exámenes de la materia. Datos mockeados: vendrán del endpoint
                    <code class="text-fg-brand">/materias/{codigo}/examenes</code>.
                </p>
            </x-ui.card>
        </div>

        <div x-show="tab === 'auxiliares'" x-cloak class="mt-6">
            <x-ui.card title="Auxiliares (mock)">
                <p class="text-body">
                    Auxiliares de la materia. Datos mockeados: vendrán del endpoint
                    <code class="text-fg-brand">/materias/{codigo}/auxiliares</code>.
                </p>
            </x-ui.card>
        </div>
    </div>
@endsection