{{--
    @file    banner-estado.blade.php
    @author  David E. Chavez T. <virzuzz12345@gmail.com>
    @created 2026-10-10
    @updated 2026-10-10

    @description
    Partial compartido con el banner de estado de los modales de ingreso.
    Recibe $variante (puede-ingresar, ingresado, no-habilitado, central-riesgos),
    $titulo y $subtitulo. Pinta un bloque con borde izquierdo de acento, un
    icono circular y los dos textos centrados. Los colores salen únicamente
    de los tokens definidos en app.css.

    @changelog
    - 2026-10-10  [David E. Chavez T.]  feat: creación inicial del partial.

    @see  resources/views/components/ui/modal-registro-ingreso.blade.php
    @see  plan_modales.md (sección 4.4)
--}}

@php
    $v = [
        'puede-ingresar' => [
            'contenedor' => 'bg-status-habilitado-bg border-brand',
            'icono'      => 'bg-brand',
            'titulo'     => 'text-fg-brand',
            'subtitulo'  => 'text-body',
            'glifo'      => 'check',
        ],
        'ingresado' => [
            'contenedor' => 'bg-status-habilitado-bg border-brand',
            'icono'      => 'bg-brand',
            'titulo'     => 'text-fg-brand',
            'subtitulo'  => 'text-fg-brand',
            'glifo'      => 'check',
        ],
        'no-habilitado' => [
            'contenedor' => 'bg-status-no-habilitado-bg border-danger',
            'icono'      => 'bg-danger',
            'titulo'     => 'text-fg-danger-strong',
            'subtitulo'  => 'text-fg-danger',
            'glifo'      => 'prohibido',
        ],
        'central-riesgos' => [
            'contenedor' => 'bg-status-central-riesgos-bg border-status-central-riesgos-fg',
            'icono'      => 'bg-danger',
            'titulo'     => 'text-status-central-riesgos-fg',
            'subtitulo'  => 'text-fg-danger',
            'glifo'      => 'escudo',
        ],
    ][$variante] ?? [
        'contenedor' => 'bg-neutral-secondary-soft border-default',
        'icono'      => 'bg-brand',
        'titulo'     => 'text-heading',
        'subtitulo'  => 'text-body',
        'glifo'      => 'check',
    ];
@endphp

<div class="rounded-base border-l-4 p-5 text-center md:p-6 {{ $v['contenedor'] }}">
    <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-full {{ $v['icono'] }}">
        @if ($v['glifo'] === 'check')
            <svg class="h-7 w-7 text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="m5 13 4 4L19 7"/>
            </svg>
        @elseif ($v['glifo'] === 'prohibido')
            <svg class="h-7 w-7 text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2"/>
                <path stroke="currentColor" stroke-linecap="round" stroke-width="2" d="m5.6 5.6 12.8 12.8"/>
            </svg>
        @else
            <svg class="h-7 w-7 text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3.5 5 6.4v5.1c0 4.1 2.8 7.4 7 8.9 4.2-1.5 7-4.8 7-8.9V6.4L12 3.5Z"/>
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8.5v2.5m0 2.5h.01"/>
            </svg>
        @endif
    </span>

    <p class="mt-3 text-xl font-bold {{ $v['titulo'] }}">{{ $titulo }}</p>
    <p class="mt-1 text-sm {{ $v['subtitulo'] }}">{{ $subtitulo }}</p>
</div>
