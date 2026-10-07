{{--
    @file    materia-estudiantes.blade.php
    @author  OchoaCesar <cesareduardonick@gmail.com>
    @created 2026-09-25
    @updated 2026-10-05

    @description
    Vista de detalle de una materia: muestra las cards resumen (conteos reales
    del curso) y las pestañas Estudiantes / Habilitación / Exámenes /
    Auxiliares. La pestaña Estudiantes incrusta el componente Livewire
    EstudiantesCurso, que consume la misma fuente que el endpoint
    GET /api/cursos/{idCurso}/estudiantes/estado (búsqueda, filtros con
    contadores, paginación y los modales Habilitar/Deshabilitar conectados al
    servicio de cambio de estado). La pestaña Exámenes incrusta el parcial
    partials/materia-examenes, con el listado que resuelve
    ListarExamenesCursoService y el botón "Crear examen". Las pestañas
    Habilitación y Auxiliares quedan vacías a la espera de sus endpoints.

    @changelog
    - 2026-09-25  [OchoaCesar]  feat: creación inicial de la vista (mock).
    - 2026-09-26  [Alisson D. Alvarado]  feat: buscador y modales en la vista mock.
    - 2026-09-28  [OchoaCesar]  refactor: conectar la pestaña Estudiantes al
      componente Livewire (datos reales), quitar mocks y usar conteos reales en las cards.
    - 2026-10-05  [Alex Candia]  feat: la pestaña Exámenes muestra el listado de
      exámenes de la materia con el botón "Crear examen".

    @see  App\Livewire\Examenes\EstudiantesCurso
    @see  App\Services\Examen\ListarEstudiantesCursoConEstadoService
    @see  App\Services\Examen\ListarExamenesCursoService
    @see  resources/views/partials/materia-examenes.blade.php
    @see  pages/materias.blade.php
--}}

@extends('layouts.app')

@section('title', $curso->nombre_curso)

@section('content')
    @php
        // Después de crear un examen (o de fallar la validación) la página vuelve
        // a cargar y, si arrancara siempre en Estudiantes, el aviso y el modal
        // quedarían escondidos detrás de otra pestaña.
        $tabInicial = ($errors->any() || session()->has('mensaje')) ? 'examenes' : 'estudiantes';
    @endphp

    <div class="flex flex-wrap gap-[2vh]">
        <div class="flex-1 min-w-[200px] flex items-center justify-between gap-3 bg-neutral-primary-soft p-6 border border-default rounded-base shadow-xs">
            <div>
                <p class="text-3xl font-semibold text-fg-brand">{{ $conteos['todos'] }}</p>
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

    <div x-data="{ tab: '{{ $tabInicial }}' }" class="mt-6">
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

        {{-- Pestaña Estudiantes: componente Livewire con los datos reales. --}}
        <section x-show="tab === 'estudiantes'" x-cloak class="mt-6">
            @livewire(\App\Livewire\Examenes\EstudiantesCurso::class, ['curso' => $curso], key($curso->id_curso))
        </section>

        {{-- Pestaña Exámenes: listado del parcial, con el botón Crear examen. --}}
        <section x-show="tab === 'examenes'" x-cloak class="mt-6">
            @include('partials.materia-examenes', ['curso' => $curso, 'examenes' => $examenes])
        </section>

        {{-- Pestañas pendientes de endpoint: vacías por ahora. --}}
        <section x-show="tab === 'habilitacion'" x-cloak class="mt-6"></section>
        <section x-show="tab === 'auxiliares'" x-cloak class="mt-6"></section>
    </div>
@endsection