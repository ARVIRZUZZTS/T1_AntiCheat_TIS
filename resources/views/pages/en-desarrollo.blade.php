{{--
    @file    en-desarrollo.blade.php
    @author  Diego Tejerina <josediegotejerinamolina@gmail.com>
    @created 2026-11-15

    @description
    Marcador navegable reutilizable para las subopciones del sidebar cuya
    pantalla todavia no esta construida. Recibe $titulo (para el encabezado) y
    un $descripcion opcional. Evita duplicar una vista casi vacia por cada
    subseccion pendiente: cada ruta placeholder apunta aca con su titulo.

    @changelog
    - 2026-11-15  [Diego Tejerina]  feat: creacion del marcador "En desarrollo".

    @see  resources/views/components/ui/sidebar.blade.php
--}}

@extends('layouts.app')

@section('title', $titulo ?? 'En desarrollo')

@section('content')
    <div class="px-4 py-4 sm:px-6 sm:py-6 lg:px-8">
        <div class="mx-auto max-w-xl rounded-base border border-default bg-neutral-primary-soft p-8 text-center shadow-xs">
            <span class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-brand-softer text-fg-brand">
                <svg class="h-6 w-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                </svg>
            </span>
            <p class="text-sm text-body">{{ $descripcion ?? 'Esta seccion esta en desarrollo.' }}</p>
        </div>
    </div>
@endsection
