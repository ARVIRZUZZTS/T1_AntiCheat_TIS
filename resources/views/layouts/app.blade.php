{{--
    @file    app.blade.php
    @author  David E. Chavez T. <virzuzz12345@gmail.com>
    @created 2026-09-29
    @updated 2026-09-29

    @description
    Layout principal del panel. Estructura flex con sidebar + main.
    El sidebar siempre está visible y el main contiene el contenido dinámico.

    @changelog
    - 2026-09-29  [David E. Chavez T.]  feat: estructura flex con sidebar + main.
--}}

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        <title>@yield('title', $title ?? config('app.name'))</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        @livewireStyles
    </head>
    <body class="bg-surface-page text-heading antialiased">
        <div class="flex">
            <x-ui.sidebar title="Control de ingreso Exámenes masivos" :items="$sidebarItems ?? []" :show-trigger="$sidebarShowTrigger ?? true" />

            @if (isset($slot))
                <main class="flex-1 lg:h-screen lg:overflow-hidden">
                    {{ $slot }}
                </main>
            @else
                <main class="flex-1">
                    <header class="bg-neutral-primary-soft border-b border-default mb-[2vh]">
                        <div class="px-6 py-4">
                            <h1 class="text-2xl font-semibold text-heading">@yield('title', config('app.name'))</h1>
                        </div>
                    </header>

                    @yield('content')
                </main>
            @endif
        </div>

        @livewireScripts
        <script src="https://cdn.jsdelivr.net/npm/flowbite@4.0.1/dist/flowbite.min.js"></script>
    </body>
</html>