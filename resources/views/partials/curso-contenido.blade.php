{{--
    @file    curso-contenido.blade.php
    @author  OchoaCesar <cesareduardonick@gmail.com>
    @created 2026-11-15

    @description
    Contenido de un curso (tarjetas de conteo + pestañas Estudiantes /
    Habilitaciones). Es el contenido original de pages/curso-detalle (OchoaCesar),
    extraído a un parcial para reutilizarlo también en las páginas "Ver
    estudiantes" y "Habilitar estudiantes" (select de curso + este contenido
    debajo). Espera $curso y $conteos. Si llega $soloTab ('estudiantes' o
    'habilitacion'), oculta las pestañas y renderiza solo esa sección. El botón
    "Cargar CSV" enlaza a su página propia (subopción del sidebar), conservando
    el curso en el query.

    @changelog
    - 2026-09-25  [OchoaCesar]  feat: contenido del detalle de curso (tarjetas
      y pestañas Estudiantes / Habilitaciones).
    - 2026-11-15  [Diego Tejerina]  refactor: se extrae ese contenido a un
      parcial reutilizable (detalle + "Ver curso"); "Cargar CSV" apunta a su
      página.
    - 2026-11-15  [Alex Candia]  refactor: $soloTab acota el parcial a una sola
      sección y evita cargar el componente Livewire que no se muestra.
    - 2026-11-16  [Alex Candia]  fix: el x-show del botón "Cargar CSV" va como
      atributo fijo (un @if dentro del tag x-ui.button rompía la vista).

    @see  resources/views/pages/curso-detalle.blade.php
    @see  resources/views/pages/curso-ver.blade.php
    @see  App\Livewire\Examenes\EstudiantesCurso
--}}

@php
    $soloTab = $soloTab ?? null;
    $muestraCsv = $soloTab === null || $soloTab === 'estudiantes';
@endphp

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
         incidencia de este curso; mismo número que el filtro "Central de
         Riesgo" de la lista. --}}
    <x-ui.stat-card :value="$conteos['tramposos']" label="Central de Riesgo" tone="danger">
        <x-slot:icon>
            <svg class="w-5 h-5 sm:w-6 sm:h-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3 5 6v5c0 4.2 2.8 7.6 7 9 4.2-1.4 7-4.8 7-9V6l-7-3Z"/><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.5 9.5l5 5m0-5-5 5"/></svg>
        </x-slot:icon>
    </x-ui.stat-card>
</div>

<div x-data="{ tab: @js($soloTab ?? 'estudiantes') }" class="mt-6">
    @php
        $tabClases = 'shrink-0 whitespace-nowrap inline-flex items-center justify-center box-border border focus:ring-4 shadow-xs font-medium leading-5 rounded-base focus:outline-none px-4 py-2.5 text-sm';
        $tabActivo = 'text-white bg-brand border-transparent hover:bg-brand-strong focus:ring-brand-medium';
        $tabInactivo = 'text-body bg-neutral-secondary-medium border-default-medium hover:bg-neutral-tertiary-medium hover:text-heading focus:ring-neutral-tertiary';
    @endphp

    @if ($muestraCsv)
        {{-- Fila única: pestañas con scroll horizontal propio y, a la derecha,
             "Cargar CSV", que solo aplica a Estudiantes y lleva a su página. En
             las páginas acotadas no hay pestañas: solo queda el botón. --}}
        <div class="{{ $soloTab === null ? 'flex items-center justify-between gap-3' : 'flex justify-end' }} px-4 sm:px-6 lg:px-8">
            @if ($soloTab === null)
                <nav class="flex min-w-0 gap-2 overflow-x-auto" aria-label="Secciones del curso">
                    <button type="button" @click="tab = 'estudiantes'" :class="tab === 'estudiantes' ? '{{ $tabActivo }}' : '{{ $tabInactivo }}'" class="{{ $tabClases }}">Estudiantes</button>
                    <button type="button" @click="tab = 'habilitacion'" :class="tab === 'habilitacion' ? '{{ $tabActivo }}' : '{{ $tabInactivo }}'" class="{{ $tabClases }}">Habilitaciones</button>
                </nav>
            @endif

            {{-- El x-show va fijo y no con @if dentro del tag: Blade no compila
                 directivas dentro de componentes y deja roto el archivo
                 compilado. En "Ver estudiantes" el tab inicial ya es
                 estudiantes; en "Habilitar" no se renderiza ($muestraCsv). --}}
            <x-ui.button variant="tertiary" pill class="shrink-0"
                         x-show="tab === 'estudiantes'"
                         :href="route('cursos.cargar-csv', ['curso' => $curso->id_curso])">
                <svg class="w-4 h-4 me-1.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 16V4m0 0L8 8m4-4 4 4M4 16v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2"/></svg>
                Cargar CSV
            </x-ui.button>
        </div>
    @endif

    @if ($soloTab === null || $soloTab === 'estudiantes')
        {{-- Estudiantes: componente Livewire con los datos reales. --}}
        <section x-show="tab === 'estudiantes'" x-cloak class="mt-4">
            @livewire(\App\Livewire\Examenes\EstudiantesCurso::class, ['curso' => $curso, 'soloLectura' => true], key('estudiantes-'.$curso->id_curso))
        </section>
    @endif

    @if ($soloTab === null || $soloTab === 'habilitacion')
        {{-- Habilitaciones: el mismo componente con los modales de cambio de estado. --}}
        <section x-show="tab === 'habilitacion'" x-cloak class="mt-4">
            @livewire(\App\Livewire\Examenes\EstudiantesCurso::class, ['curso' => $curso, 'modoHabilitacion' => true], key('habilitacion-'.$curso->id_curso))
        </section>
    @endif
</div>
