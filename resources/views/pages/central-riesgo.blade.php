{{--
    @file    central-riesgo.blade.php
    @author  David E. Chavez T. <virzuzz12345@gmail.com>
    @created 2026-09-29
    @updated 2026-10-10

    @description
    Pantalla de la central de riesgos. Interfaz responsive: en móvil la lista
    de estudiantes se muestra como cards y en desktop como tabla dentro de un
    panel. Incluye tabs tipo píldora (Mis materias / Institución) que solo
    cambian el estado activo, un buscador aún sin funcionalidad y un banner de
    bloqueo automático. La lista está mockeada (aún no hay endpoints) y ya
    viene ordenada de la más reciente a la más antigua.

    @changelog
    - 2026-09-24  [David E. Chavez T.]  feat: creación inicial de la vista.
    - 2026-09-28  [Candy]  feat: botón de registrar incidencia con entrada a
      `origen=central-riesgo`, sin estudiante ni rol en la URL.
    - 2026-09-29  [David E. Chavez T.]  refactor: header y espaciado consistente.
    - 2026-10-09  [Alisson D. Alvarado]  feat: rediseño de la pantalla con tabs
      tipo píldora, buscador (sin funcionalidad) y lista mock en cards (móvil)
      y tabla completa clickeable (desktop), con estados vacíos y banner de
      bloqueo automático.
    - 2026-10-10  [T1]  fix: reponer el botón "Registrar incidencia" que se
      perdió en el rediseño; abre el formulario con `origen=central-riesgo`.

    @see  App\Services\CentralRiesgo\ListarAlertasService
--}}

@extends('layouts.app')

@section('title', 'Central de riesgos')

@section('content')
    @php
        // Datos de ejemplo: la central aún no tiene endpoints, por eso la lista
        // está mockeada y ya ordenada de la más reciente a la más antigua.
        $estudiantes = [
            ['nombre' => 'Ana María Gutiérrez', 'sis' => '20181234567', 'materia' => 'Matemática Discreta', 'estado' => 'Confirmado'],
            ['nombre' => 'Luis Fernando Rojas', 'sis' => '20179876543', 'materia' => 'Física Básica', 'estado' => 'Confirmado'],
            ['nombre' => 'Carol Nina Mamani', 'sis' => '20204567890', 'materia' => 'Introducción a la Programación', 'estado' => 'Confirmado'],
            ['nombre' => 'Diego Alejandro Salas', 'sis' => '20193456789', 'materia' => 'Base de Datos', 'estado' => 'Confirmado'],
        ];

        // Cuando el buscador tenga funcionalidad, este flag distinguirá el estado
        // vacío por búsqueda (sin coincidencias) del vacío por ausencia total.
        $sinCoincidencias = false;

        $iniciales = static function (string $nombre): string {
            return implode('', array_map(
                fn (string $parte) => mb_strtoupper(mb_substr($parte, 0, 1)),
                preg_split('/\s+/', trim($nombre)) ?: []
            ));
        };
    @endphp

    <div class="px-4 pt-[2vh] pb-8 sm:px-6 lg:px-[2vh]" x-data="{ tab: 'mis-materias' }">
        <div class="flex flex-wrap items-center justify-between gap-3">
            {{-- Tabs tipo píldora: por ahora solo cambian el estado activo. --}}
            <div class="flex gap-2" role="tablist" aria-label="Ámbito de la central">
                <button type="button" role="tab" @click="tab = 'mis-materias'"
                        :class="tab === 'mis-materias' ? 'bg-brand text-white border-transparent' : 'bg-neutral-secondary-medium text-body border-neutral-quaternary'"
                        class="inline-flex items-center justify-center rounded-full border px-5 py-2.5 text-sm font-semibold shadow-xs focus:outline-none focus:ring-4 focus:ring-brand-medium">
                    Mis materias
                </button>
                <button type="button" role="tab" @click="tab = 'institucion'"
                        :class="tab === 'institucion' ? 'bg-brand text-white border-transparent' : 'bg-neutral-secondary-medium text-body border-neutral-quaternary'"
                        class="inline-flex items-center justify-center rounded-full border px-5 py-2.5 text-sm font-semibold shadow-xs focus:outline-none focus:ring-4 focus:ring-brand-medium">
                    Institución
                </button>
            </div>

            {{-- Acceso al registro de incidencia desde la central de riesgo: abre
                 el formulario en blanco (origen=central-riesgo) para que la
                 persona busque al estudiante con la lupa. --}}
            <x-ui.button :href="route('registrar-incidencia', ['origen' => 'central-riesgo'])" size="sm">
                Registrar incidencia
            </x-ui.button>
        </div>

        {{-- Buscador: solo la interfaz, aún sin funcionalidad. --}}
        <div class="relative mt-5">
            <div class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-3">
                <svg class="h-4 w-4 text-body" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-width="2" d="m21 21-3.5-3.5M17 10a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z"/></svg>
            </div>
            <input type="search" placeholder="Buscar por nombre o SIS" aria-label="Buscar por nombre o SIS"
                   class="w-full rounded-[10px] border border-neutral-tertiary bg-neutral-primary-soft py-3.5 ps-10 pe-4 text-sm text-heading shadow-xs placeholder:text-fg-disabled focus:border-brand focus:ring-brand focus:outline-none" />
        </div>

        @if (count($estudiantes) === 0)
            <p class="mt-6 rounded-xl border border-default bg-neutral-primary-soft p-8 text-center text-sm text-body">
                {{ $sinCoincidencias ? 'No se encontró ninguna coincidencia' : 'No hay estudiantes registrados' }}
            </p>
        @else
            {{-- Móvil: cards de estudiantes. --}}
            <div class="mt-5 space-y-3 lg:hidden">
                @foreach ($estudiantes as $estudiante)
                    <article class="rounded-xl border border-neutral-quaternary bg-neutral-primary-soft p-4">
                        <div class="flex items-center gap-3">
                            <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-neutral-secondary-medium text-sm font-semibold text-fg-brand">
                                {{ $iniciales($estudiante['nombre']) }}
                            </span>
                            <div class="min-w-0">
                                <p class="truncate text-sm font-bold text-fg-brand">{{ $estudiante['nombre'] }}</p>
                                <p class="truncate text-[11px] text-body">{{ $estudiante['sis'] }} · {{ $estudiante['materia'] }}</p>
                            </div>
                        </div>
                        <div class="mt-3 flex items-center justify-between">
                            <x-ui.badge type="danger" class="font-semibold">{{ $estudiante['estado'] }}</x-ui.badge>
                            <a href="#" class="text-xs font-bold text-fg-danger-strong">Ver detalle ›</a>
                        </div>
                    </article>
                @endforeach
            </div>

            {{-- Desktop: panel con tabla. --}}
            <div class="mt-6 hidden lg:block">
                <div class="overflow-hidden rounded-xl border border-neutral-quaternary bg-neutral-primary-soft shadow-xs">
                    <div class="border-b border-neutral-quaternary px-4 py-4">
                        <h2 class="text-[15px] font-semibold text-fg-brand">Estudiantes en mis materias</h2>
                    </div>
                    <table class="w-full text-left text-sm text-body">
                        <thead>
                            <tr class="border-b border-neutral-quaternary">
                                <th scope="col" class="w-[40%] px-4 py-3 text-[10px] font-medium uppercase tracking-wider text-body">Estudiante</th>
                                <th scope="col" class="px-4 py-3 text-center text-[10px] font-medium uppercase tracking-wider text-body">SIS</th>
                                <th scope="col" class="px-4 py-3 text-center text-[10px] font-medium uppercase tracking-wider text-body">Materia</th>
                                <th scope="col" class="px-4 py-3 text-center text-[10px] font-medium uppercase tracking-wider text-body">Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($estudiantes as $estudiante)
                                {{-- Toda la fila lleva al detalle (pendiente de endpoint), por eso no se repite el link "Ver detalle". --}}
                                <tr class="cursor-pointer border-b border-neutral-quaternary transition-colors hover:bg-[#F7F8FC] last:border-b-0" role="link" tabindex="0">
                                    <td class="px-4 py-4">
                                        <div class="flex items-center gap-3">
                                            <span class="inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-neutral-tertiary text-[10px] font-semibold text-fg-brand">
                                                {{ $iniciales($estudiante['nombre']) }}
                                            </span>
                                            <div class="min-w-0">
                                                <p class="truncate text-sm font-bold text-fg-brand">{{ $estudiante['nombre'] }}</p>
                                                <p class="text-xs text-body">Toca para ver el detalle</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-4 text-center text-body">{{ $estudiante['sis'] }}</td>
                                    <td class="px-4 py-4 text-center text-body">{{ $estudiante['materia'] }}</td>
                                    <td class="px-4 py-4 text-center">
                                        <x-ui.badge type="danger" class="font-semibold">{{ $estudiante['estado'] }}</x-ui.badge>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Banner de alerta al final. --}}
            <div class="mt-6 rounded-xl border border-danger-subtle bg-danger-soft p-4 lg:mt-8">
                <p class="font-semibold text-fg-danger-strong">Bloqueo automático activo</p>
                <p class="mt-1 text-sm text-fg-danger">Estos estudiantes no podrán ingresar a tus exámenes.</p>
            </div>
        @endif
    </div>
@endsection