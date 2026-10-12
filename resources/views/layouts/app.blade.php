{{--
    @file    app.blade.php
    @author  David E. Chavez T. <virzuzz12345@gmail.com>
    @created 2026-09-29
    @updated 2026-10-11

    @description
    Layout principal del panel. Estructura flex con sidebar + main.
    El sidebar siempre está visible y el main contiene el contenido dinámico.

    @changelog
    - 2026-09-29  [David E. Chavez T.]  feat: estructura flex con sidebar + main.
    - 2026-10-08  [OchoaCesar]  refactor: permitir header custom vía @hasSection('header') para evitar header doble en detalle de materia.
    - 2026-10-11  [OchoaCesar]  feat: meta csrf-token para las peticiones fetch con token (modales de incidencia).
    - 2026-10-12  [Diego Tejerina]  fix: reinicializar los componentes JS de
      Flowbite (drawer, etc.) en `livewire:navigated` y tras cada morph de
      Livewire; sin esto dejaban de responder después de la primera
      navegación.
    - 2026-10-12  [Diego Tejerina]  fix: `<main>` pasa a `min-w-0` (sin esto,
      un `flex-1` dentro de un flex en fila no puede achicarse por debajo del
      ancho mínimo de su contenido, y cualquier tabla ancha empujaba TODA la
      página a un scroll horizontal). El div raíz pasa a `items-start`: con el
      sidebar en `fixed` (ver components/ui/sidebar.blade.php) ya no cuenta
      como ítem del flex, así que el botón hamburguesa quedaba como único
      hermano angosto de `<main>` y el `align-items: stretch` por defecto lo
      estiraba y centraba en toda la altura de la página en vez de dejarlo
      arriba.
    - 2026-10-12  [Diego Tejerina]  fix: `overflow-x-hidden` en el `<body>`
      como resguardo final: el sidebar off-canvas (fixed + translateX(-100%))
      queda con su caja en left=-288px cuando está cerrado, y eso basta para
      que el navegador considere la página "desplazable" unos pixeles hacia la
      izquierda aunque nada se vea mal; con esto ya no hay forma de arrastrar
      la página horizontalmente en ninguna vista.
    - 2026-10-12  [Diego Tejerina]  fix: el hamburguesa deja de vivir dentro de
      x-ui.sidebar. Al ser `<aside>` `fixed`, ya no cuenta como ítem del flex
      del layout, y el botón hamburguesa (el único hermano angosto que quedaba
      junto a `<main>`) se ponía en fila con todo el contenido en vez de
      arriba: la "columna de 88px" que empujaba la página entera. Ahora
      x-ui.page-header dibuja una franja de ancho completo (hamburguesa o
      flecha de volver + título + subtítulo) arriba de `<main>`, dentro del
      mismo contenedor en columna, para mobile y desktop. El título/subtítulo
      de cada vista salen de @section('title')/@section('subtitle') (o de
      $title/$subtitle si la vista los pasa como variable), y $backRoute
      cambia el hamburguesa por la flecha en las vistas de detalle.
    - 2026-10-12  [Diego Tejerina]  fix: la rama `@else` también acepta
      @section('back-route', ...) para fijar la flecha de volver. Los
      componentes Livewire que se renderizan con `->extends('layouts.app')`
      (ese método de Livewire, no el `@extends` nativo de Blade) no
      propagan una variable PHP suelta como `$backRoute` hacia el layout —
      por eso funcionaba en páginas Blade clásicas (`materia-estudiantes`,
      que pasa `backRoute` como dato de vista) pero no en
      `registrar-incidencia.blade.php`, que la fijaba con un `@php` suelto
      y la hamburguesa seguía apareciendo en vez de la flecha.
--}}

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="overflow-x-hidden">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>@yield('title', $title ?? config('app.name'))</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        @livewireStyles
    </head>
    <body class="bg-surface-page text-heading antialiased overflow-x-hidden">
        <div class="flex">
            <x-ui.sidebar title-main="Control de Ingreso" title-sub="Exámenes masivos" :items="$sidebarItems ?? []" />

            <div class="flex-1 min-w-0 flex flex-col lg:h-screen">
                @if (isset($slot))
                    <x-ui.page-header
                        :title="$title ?? config('app.name')"
                        :subtitle="$subtitle ?? null"
                        :back="$backRoute ?? null"
                    />

                    <main class="flex-1 min-w-0 lg:min-h-0 lg:overflow-hidden">
                        {{ $slot }}
                    </main>
                @else
                    @hasSection('header')
                        @yield('header')
                    @else
                        <x-ui.page-header
                            :title="trim($__env->yieldContent('title', config('app.name')))"
                            :subtitle="$__env->yieldContent('subtitle') ?: null"
                            :back="$__env->yieldContent('back-route') ?: ($backRoute ?? null)"
                        >
                            @hasSection('header-actions')
                                <x-slot:actions>@yield('header-actions')</x-slot:actions>
                            @endif
                        </x-ui.page-header>
                    @endif

                    <main class="flex-1 min-w-0">
                        @yield('content')
                    </main>
                @endif
            </div>
        </div>

        @livewireScripts
        <script src="https://cdn.jsdelivr.net/npm/flowbite@4.0.1/dist/flowbite.min.js"></script>
        <script>
            // El drawer (y cualquier otro componente JS de Flowbite) solo se
            // inicializa una vez, al cargar la pagina. Una navegacion con
            // wire:navigate reemplaza el <body> sin recargar el script, y un
            // morph de Livewire puede volver a pintar el boton hamburguesa o
            // el propio drawer; sin reinicializar, esos elementos quedan sin
            // su listener de Flowbite y dejan de abrir/cerrar.
            document.addEventListener('livewire:navigated', function () {
                if (window.initFlowbite) {
                    window.initFlowbite();
                }
            });

            document.addEventListener('livewire:init', function () {
                Livewire.hook('morph.updated', function () {
                    if (window.initFlowbite) {
                        window.initFlowbite();
                    }
                });
            });
        </script>
    </body>
</html>