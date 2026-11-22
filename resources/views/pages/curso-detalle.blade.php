{{--
    @file    curso-detalle.blade.php
    @author  OchoaCesar <cesareduardonick@gmail.com>
    @created 2026-09-25
    @updated 2026-10-09
    @updated 2026-10-24
    @updated 2026-11-19

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
    - 2026-11-19  [Valery D. Ortuno P]  fix: el estado del curso dice
      "Archivado" en vez de "Finalizado", como en Ver cursos.

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
        $curso->estado === 'EnCurso' ? 'En curso' : 'Archivado',
    ])->filter()->implode(' — ');
@endphp

{{-- El título y el subtítulo los pinta x-ui.page-header (ver
     layouts/app.blade.php); de acá solo sale el texto, para que no existan
     dos encabezados hechos a mano con el mismo dato. --}}
@section('subtitle', $subtituloHeader)

@section('content')
    {{-- El contenido (tarjetas de conteo + pestañas Estudiantes/Habilitaciones)
         vive en un parcial compartido con la página "Ver curso". --}}
    @include('partials.curso-contenido', ['curso' => $curso, 'conteos' => $conteos])
@endsection
