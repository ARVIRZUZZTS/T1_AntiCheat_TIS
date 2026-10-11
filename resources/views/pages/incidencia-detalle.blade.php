{{--
    @file    incidencia-detalle.blade.php
    @created 2026-10-10

    @description
    Placeholder del detalle de un registro de la central de riesgos, destino del
    botón "Ver detalle" de las notificaciones (HU 12 · T4). La pantalla real aún
    no existe; esta vista deja la ruta lista y navegable sin confirmar ni
    rechazar la incidencia (el registro permanece "En revisión"). Cuando se
    implemente el detalle, se reemplaza el cuerpo por el componente/consulta
    correspondiente.

    @changelog
    - 2026-10-10  feat: placeholder para dejar lista la ruta del detalle.
--}}

@extends('layouts.app')

@section('title', 'Detalle del registro')

@section('content')
    <nav aria-label="Ruta de navegación">
        <a href="{{ route('notificaciones') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-fg-brand hover:underline">
            <svg class="w-4 h-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m15 19-7-7 7-7"/></svg>
            Volver a notificaciones
        </a>
    </nav>

    <div class="mt-6 rounded-2xl border border-light bg-neutral-primary-soft p-6 shadow-xs">
        <h2 class="text-lg font-semibold text-heading">Detalle del registro #{{ $id }}</h2>
        <p class="mt-2 text-sm text-body">
            Pantalla de detalle pendiente de implementación. La incidencia sigue
            <span class="font-medium text-fg-warning">En revisión</span>: desde aquí no se
            confirma ni se rechaza nada.
        </p>
    </div>
@endsection
