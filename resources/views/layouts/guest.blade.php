{{--
    @file    guest.blade.php
    @author  T1 - Tech One SRL
    @author  Alisson D. Alvarado <alvaradoalissondalet@gmail.com>
    @created 2026-10-17
    @updated 2026-10-17
    @description Layout invitado (sin sidebar) para pantallas públicas de autenticación.
    @changelog
    - 2026-11-07  [Alisson D. Alvarado]  feat: adaptar el espacio de autenticación a móviles.
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>@yield('title', config('app.name'))</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    <body class="bg-surface-page text-heading antialiased">
        <div class="min-h-screen flex flex-col lg:flex-row">
            <aside class="w-full lg:w-1/2 bg-brand text-neutral-primary">
                @yield('brand')
            </aside>
            <main class="flex min-w-0 flex-1 items-center justify-center px-4 py-6 sm:p-6">
                @yield('content')
            </main>
        </div>
        @livewireScripts
    </body>
</html>
