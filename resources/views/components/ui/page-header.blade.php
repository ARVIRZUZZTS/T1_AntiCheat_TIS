{{--
    @file    page-header.blade.php
    @author  Diego Tejerina <josediegotejerinamolina@gmail.com>
    @created 2026-10-12

    @description
    Barra de encabezado de página, única para mobile y desktop: evita que
    cada vista arme su propio header y termine mostrando el título o el
    subtítulo dos veces. A la izquierda va el hamburguesa (abre el Drawer de
    Flowbite del sidebar, solo visible en mobile, `lg:hidden`) o, si se pasa
    $back, una flecha de volver (visible en mobile y desktop, reemplaza al
    hamburguesa). Al centro, título en negrita y subtítulo en gris debajo.
    A la derecha, $actions es un slot opcional para botones propios de la
    vista (por ejemplo "Cargar CSV").

    El `<button>` del hamburguesa usa los mismos atributos `data-drawer-*`
    que ya trae components/ui/sidebar.blade.php: Flowbite los detecta solo
    con esos atributos, así que no hace falta JS propio.

    @see  resources/views/components/ui/sidebar.blade.php
    @see  resources/views/layouts/app.blade.php

    @changelog
    - 2026-10-12  [Diego Tejerina]  feat: creación inicial del componente.
    - 2026-10-12  [Diego Tejerina]  fix: el ícono de la hamburguesa pasa de
      `text-body` (gris) a `text-fg-brand` (el azul oscuro de la marca, el
      mismo del sidebar), para todas las vistas.
--}}

@props([
    'title' => null,
    'subtitle' => null,
    'back' => null,
])

<header class="sticky top-0 z-20 shrink-0 bg-neutral-primary-soft border-b border-default">
    <div class="flex items-center gap-3 px-4 py-3 sm:px-6 lg:px-8">
        @if ($back)
            <a href="{{ $back }}" aria-label="Volver"
               class="shrink-0 inline-flex items-center justify-center w-10 h-10 rounded-full text-body hover:bg-neutral-secondary-medium hover:text-heading focus:outline-none">
                <svg class="w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m15 19-7-7 7-7"/></svg>
            </a>
        @else
            <button type="button" data-drawer-target="sidebar" data-drawer-toggle="sidebar" aria-controls="sidebar"
                    class="lg:hidden shrink-0 inline-flex items-center justify-center w-10 h-10 rounded-base text-fg-brand hover:bg-neutral-secondary-medium focus:outline-none">
                <span class="sr-only">Abrir menú</span>
                <svg class="w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-width="2" d="M5 7h14M5 12h14M5 17h10"/></svg>
            </button>
        @endif

        <div class="min-w-0 flex-1">
            <h1 class="text-lg sm:text-2xl font-bold text-heading truncate">{{ $title }}</h1>
            @if ($subtitle)
                <p class="text-sm text-muted truncate">{{ $subtitle }}</p>
            @endif
        </div>

        @isset($actions)
            <div class="shrink-0">{{ $actions }}</div>
        @endisset
    </div>
</header>
