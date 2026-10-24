{{--
    @file    curso-detalle.blade.php
    @author  OchoaCesar <cesareduardonick@gmail.com>
    @created 2026-09-25
    @updated 2026-10-09
    @updated 2026-10-24

    @description
    Vista de un curso (a la que se llega con "Ver curso"): muestra las cards
    resumen (conteos reales del curso) y las secciones Estudiantes /
    Habilitaciones / Auxiliares. Estudiantes incrusta el componente
    Livewire EstudiantesCurso en solo lectura, que consume la misma fuente que
    el endpoint GET /api/cursos/{idCurso}/estudiantes/estado (búsqueda,
    filtros con contadores y paginación); Habilitaciones incrusta el mismo
    componente en modo habilitación, con los modales Habilitar/Deshabilitar
    conectados al servicio de cambio de estado. Auxiliares queda vacía a la
    espera de su HU.

    Los exámenes del curso ya no viven acá: se ven y se crean desde la sección
    Exámenes del sidebar.

    @changelog
    - 2026-09-25  [OchoaCesar]  feat: creación inicial de la vista (mock).
    - 2026-09-26  [Alisson D. Alvarado]  feat: buscador y modales en la vista mock.
    - 2026-09-28  [OchoaCesar]  refactor: conectar la pestaña Estudiantes al
      componente Livewire (datos reales), quitar mocks y usar conteos reales en las cards.
    - 2026-10-05  [Alex Candia]  feat: la pestaña Exámenes muestra el listado de
      exámenes de la materia con el botón "Crear examen".
    - 2026-10-08  [OchoaCesar]  refactor: mover header a @section('header') y añadir margen superior al contenido para mantener espaciado.
    - 2026-10-09  [OchoaCesar]  feat: la pestaña Exámenes pasa a template x-if y el
      parcial consulta el listado al endpoint con fetch al abrirse.
    - 2026-10-12  [Diego Tejerina]  refactor: el @section('header') hecho a mano
      se reemplaza por @section('subtitle') + @section('header-actions'), que
      alimentan a x-ui.page-header (layouts/app.blade.php) — evita tener dos
      formas distintas de armar el título/subtítulo de la vista. Tarjetas de
      conteo a grid (2 columnas en mobile, 5ta a lo ancho; 5 en desktop) en vez
      de flex-wrap, pestañas en una sola fila con scroll horizontal propio en
      vez de partirse en dos líneas, y padding horizontal consistente
      (px-4/sm:px-6/lg:px-8) en las tarjetas, la fila de pestañas y las
      pestañas sin componente propio — antes quedaban pegadas al borde.
      Ninguno de estos cambios toca la lógica de cambio de pestaña ni la de
      los componentes incrustados.
    - 2026-10-24  [Valery D. Ortuno P]  refactor: la vista pasa de
      materia-estudiantes.blade.php a curso-detalle.blade.php. Se quitan las
      tarjetas Sospechosos y Tramposos y se suma "Central de Riesgo"; la
      pestaña Exámenes sale (pasa a la sección Exámenes) y las pestañas se
      llaman Estudiantes / Habilitaciones / Auxiliares. "Cargar CSV" pasa del
      encabezado a la fila de pestañas y solo se muestra en Estudiantes. La
      fila de pestañas pierde la línea inferior, igual que en Reportes.

    @see  App\Http\Controllers\CursoController
    @see  App\Livewire\Examenes\EstudiantesCurso
    @see  App\Services\Examen\ListarEstudiantesCursoConEstadoService
    @see  pages/cursos.blade.php
--}}

@extends('layouts.app')

@section('title', $curso->nombre_curso)

@php
    $docenteNombre = trim(($curso->docente?->nombre_usuario ?? '') . ' ' . ($curso->docente?->apellido ?? ''));
    $subtituloHeader = collect([
        $docenteNombre !== '' ? 'Docente: ' . $docenteNombre : null,
        $curso->estado === 'EnCurso' ? 'En curso' : 'Finalizado',
    ])->filter()->implode(' — ');
@endphp

{{-- El título y el subtítulo los pinta x-ui.page-header (ver
     layouts/app.blade.php); de acá solo sale el texto, para que no existan
     dos encabezados hechos a mano con el mismo dato. --}}
@section('subtitle', $subtituloHeader)

@section('content')
    {{-- Mismo padding horizontal (px-4 mobile, sm:px-6, lg:px-8 desktop) en
         cada sección de esta vista: las tarjetas, la fila de pestañas y las
         pestañas sin componente propio (Auxiliares). Estudiantes y
         Habilitaciones quedan FUERA a propósito: el componente Livewire ya
         trae su propio p-4 sm:p-6 (lo necesita también cuando se abre solo,
         en la ruta /cursos/{curso}/estudiantes/estado) y sumarle este padding
         por fuera la dejaría con más margen que las demás pestañas. --}}

    {{-- Tarjetas de conteo: grid de 2 columnas en mobile y 4 en desktop. --}}
    <div class="grid grid-cols-2 gap-4 px-4 pt-4 sm:px-6 sm:pt-6 lg:grid-cols-4 lg:px-8">
        <x-ui.stat-card :value="$conteos['todos']" label="Inscritos" tone="brand">
            <x-slot:icon>
                <svg class="w-5 h-5 sm:w-6 sm:h-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-width="2" d="M16 19h4a1 1 0 0 0 1-1v-1a3 3 0 0 0-3-3h-2m-2.236-4a3 3 0 1 0 0-4M3 18v-1a3 3 0 0 1 3-3h4a3 3 0 0 1 3 3v1a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1Zm8-10a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>
            </x-slot:icon>
        </x-ui.stat-card>

        <x-ui.stat-card :value="$conteos['habilitados']" label="Habilitados" tone="success">
            <x-slot:icon>
                <svg class="w-5 h-5 sm:w-6 sm:h-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18Zm-3-9 2 2 4-4"/></svg>
            </x-slot:icon>
        </x-ui.stat-card>

        <x-ui.stat-card :value="$conteos['deshabilitados']" label="Deshabilitados" tone="danger">
            <x-slot:icon>
                <svg class="w-5 h-5 sm:w-6 sm:h-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 17.94 6M18 18 6.06 6"/></svg>
            </x-slot:icon>
        </x-ui.stat-card>

        {{-- Estudiantes del curso que están en la central de riesgos por una
             incidencia de este curso; es el mismo número que el filtro
             "Central de Riesgo" de la lista (ver delCursoEnCentralRiesgo()). --}}
        <x-ui.stat-card :value="$conteos['tramposos']" label="Central de Riesgo" tone="danger">
            <x-slot:icon>
                <svg class="w-5 h-5 sm:w-6 sm:h-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3 5 6v5c0 4.2 2.8 7.6 7 9 4.2-1.4 7-4.8 7-9V6l-7-3Z"/><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.5 9.5l5 5m0-5-5 5"/></svg>
            </x-slot:icon>
        </x-ui.stat-card>
    </div>

    <div x-data="{ tab: 'estudiantes' }" class="mt-6">
        @php
            $tabClases = 'shrink-0 whitespace-nowrap inline-flex items-center justify-center box-border border focus:ring-4 shadow-xs font-medium leading-5 rounded-base focus:outline-none px-4 py-2.5 text-sm';
            $tabActivo = 'text-white bg-brand border-transparent hover:bg-brand-strong focus:ring-brand-medium';
            $tabInactivo = 'text-body bg-neutral-secondary-medium border-default-medium hover:bg-neutral-tertiary-medium hover:text-heading focus:ring-neutral-tertiary';
        @endphp

        {{-- Fila única: pestañas con scroll horizontal propio (estilo Flowbite
             Tabs) y, a la derecha, "Cargar CSV", que solo aplica a Estudiantes. --}}
        <div class="flex items-center justify-between gap-3 px-4 sm:px-6 lg:px-8">
            <nav class="flex min-w-0 gap-2 overflow-x-auto" aria-label="Secciones del curso">
                <button type="button" @click="tab = 'estudiantes'" :class="tab === 'estudiantes' ? '{{ $tabActivo }}' : '{{ $tabInactivo }}'" class="{{ $tabClases }}">Estudiantes</button>
                <button type="button" @click="tab = 'habilitacion'" :class="tab === 'habilitacion' ? '{{ $tabActivo }}' : '{{ $tabInactivo }}'" class="{{ $tabClases }}">Habilitaciones</button>
                <button type="button" @click="tab = 'auxiliares'" :class="tab === 'auxiliares' ? '{{ $tabActivo }}' : '{{ $tabInactivo }}'" class="{{ $tabClases }}">Auxiliares</button>
            </nav>

            <x-ui.button variant="tertiary" pill class="shrink-0" x-show="tab === 'estudiantes'">
                <svg class="w-4 h-4 me-1.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 16V4m0 0L8 8m4-4 4 4M4 16v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2"/></svg>
                Cargar CSV
            </x-ui.button>
        </div>

        {{-- Estudiantes: componente Livewire con los datos reales. --}}
        <section x-show="tab === 'estudiantes'" x-cloak class="mt-4">
            @livewire(\App\Livewire\Examenes\EstudiantesCurso::class, ['curso' => $curso, 'soloLectura' => true], key('estudiantes-'.$curso->id_curso))
        </section>

        {{-- Habilitaciones: el mismo componente con los modales de cambio de estado. --}}
        <section x-show="tab === 'habilitacion'" x-cloak class="mt-4">
            @livewire(\App\Livewire\Examenes\EstudiantesCurso::class, ['curso' => $curso, 'modoHabilitacion' => true], key('habilitacion-'.$curso->id_curso))
        </section>

        {{-- Auxiliares: pendiente de su HU, vacía por ahora. --}}
        <section x-show="tab === 'auxiliares'" x-cloak class="mt-4 px-4 sm:px-6 lg:px-8"></section>
    </div>
@endsection
