{{--
    @file    marca-invitado.blade.php
    @author  T1 - Tech One SRL
    @created 2026-10-17
    @updated 2026-10-17
    @description Panel de marca reutilizado por las pantallas invitadas.
--}}
@props([
    'titulo1' => 'Control de Ingreso',
    'titulo2' => 'a Exámenes masivos',
    'descripcion' => '',
    'stats' => false,
])

<div class="flex h-full flex-col justify-between p-8 lg:p-12">
    <div>
        <span class="inline-flex items-center justify-center w-12 h-12 bg-neutral-primary rounded-base p-1.5">
            <img src="{{ asset('fcytLogo.svg') }}" alt="Logo FCyT" class="h-8 w-auto">
        </span>
        <h1 class="mt-6 text-3xl font-semibold leading-tight">
            {{ $titulo1 }} <span class="block">{{ $titulo2 }}</span>
        </h1>
        <div class="mt-3 h-1 w-16 bg-danger"></div>
        @if ($descripcion)
            <p class="mt-4 text-sm text-brand-subtle">{{ $descripcion }}</p>
        @endif
        @if ($stats)
            <div class="mt-8 flex gap-6 text-sm">
                <div><strong class="block text-lg">&lt; 5 s</strong>respuesta</div>
                <div><strong class="block text-lg">100 %</strong>cobertura</div>
                <div><strong class="block text-lg">24/7</strong>disponible</div>
            </div>
        @endif
    </div>
    <p class="mt-8 text-xs text-brand-subtle">Departamento de Informática y Sistemas — CPTIS-452026-2026</p>
</div>
