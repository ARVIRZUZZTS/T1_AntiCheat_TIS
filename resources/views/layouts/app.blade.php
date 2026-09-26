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
        {{-- $sidebarShowTrigger permite que una vista puntual oculte el boton
             hamburguesa de mobile (ej. una vista de detalle que ya trae su
             propia flecha de "volver"), sin afectar al resto de las paginas
             que no lo pasan (quedan con el trigger por defecto). --}}
        <x-ui.sidebar title="Control de ingreso Exámenes masivos" :items="$sidebarItems ?? []" :show-trigger="$sidebarShowTrigger ?? true" />

        {{-- Paginas Blade clasicas (@extends/@yield) caen en el @else. Componentes
             Livewire full-page (via #[Layout('layouts.app', [...])]) llegan aqui
             con $slot definido: manejan su propio encabezado/scroll, por eso no
             se les envuelve con el <header>/<main class="p-6"> generico. --}}
        @if (isset($slot))
            <div class="lg:ms-[15%] lg:h-screen lg:overflow-hidden">
                {{ $slot }}
            </div>
        @else
            <div class="lg:ms-[15%]">
                <header class="bg-neutral-primary-soft border-b border-default">
                    <div class="px-6 py-4">
                        <h1 class="text-2xl font-semibold text-heading">@yield('title', config('app.name'))</h1>
                    </div>
                </header>

                <main class="p-6">
                    @yield('content')
                </main>
            </div>
        @endif

        @livewireScripts
        <script src="https://cdn.jsdelivr.net/npm/flowbite@4.0.1/dist/flowbite.min.js"></script>
    </body>
</html>