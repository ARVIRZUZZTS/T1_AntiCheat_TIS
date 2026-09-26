<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        @php
            /*
             * El título llega como sección en las vistas Blade y como dato en los
             * componentes Livewire de página completa. Se resuelve una sola vez para
             * reutilizarlo en el <title> del documento y en el encabezado.
             */
            $tituloPagina = $title ?? (($__env->yieldContent('title')) ?: config('app.name'));
        @endphp

        <title>{{ $tituloPagina }}</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        @livewireStyles
    </head>
    <body class="bg-surface-page text-heading antialiased">
        <x-ui.sidebar title="Control de ingreso Exámenes masivos" />

        <div class="lg:ms-[15%]">
            <header class="bg-neutral-primary-soft border-b border-default">
                <div class="px-6 py-4">
                    <h1 class="text-2xl font-semibold text-heading">{{ $tituloPagina }}</h1>
                </div>
            </header>

            <main class="p-6">
                @yield('content')
            </main>
        </div>

        @livewireScripts
        <script src="https://cdn.jsdelivr.net/npm/flowbite@4.0.1/dist/flowbite.min.js"></script>
    </body>
</html>