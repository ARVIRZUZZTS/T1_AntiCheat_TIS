{{--
    @file    guest.blade.php
    @author  T1 - Tech One SRL
    @created 2026-10-17
    @updated 2026-10-17
    @description Layout invitado (sin sidebar) para pantallas públicas de autenticación.
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
            <aside class="lg:w-1/2 bg-brand text-neutral-primary">
                @yield('brand')
            </aside>
            <main class="flex-1 flex items-center justify-center p-6">
                @yield('content')
            </main>
        </div>
        @livewireScripts
    </body>
</html>
