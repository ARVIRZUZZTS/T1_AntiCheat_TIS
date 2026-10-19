{{--
    @file    solicitud-revision.blade.php
    @author  T1 - Tech One SRL
    @created 2026-10-17
    @updated 2026-10-17
    @description Estado de revisión de una cuenta solicitante.
--}}
@extends('layouts.guest')

@php
    $aprobaciones = (int) request('aprobaciones', 0);
    $sis = request('sis', '202301310');
    $nombre = request('nombre', '');
    $habilitada = $aprobaciones >= 2;
@endphp

@section('title', 'Solicitud en revisión')

@section('brand')
    @include('partials.marca-invitado', [
        'titulo1' => 'Control de Ingreso',
        'titulo2' => 'a Exámenes masivos',
        'descripcion' => 'Estado de tu solicitud de registro.',
        'stats' => false,
    ])
@endsection

@section('content')
    <div class="w-full max-w-md">
        <div class="rounded-base border border-default bg-neutral-primary-soft p-6 shadow-xs">
            <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full {{ $habilitada ? 'bg-success-soft text-fg-success-strong' : 'bg-warning-soft text-fg-warning' }}">
                <span class="text-xl font-bold">{{ $habilitada ? '✓' : '!' }}</span>
            </div>

            <h2 class="mb-2 text-center text-xl font-semibold text-heading">
                {{ $habilitada ? 'Cuenta habilitada' : 'Solicitud en revisión' }}
            </h2>

            <p class="mb-4 text-center text-sm text-body">
                @if ($habilitada)
                    Tu cuenta ya fue verificada por 2 docentes. Ya puedes iniciar sesión.
                @else
                    Necesitas 2 aprobaciones de docentes ya verificados para poder ingresar.
                @endif
            </p>

            <div class="mb-2 h-2 w-full rounded-full bg-neutral-secondary-soft">
                <div class="h-2 rounded-full bg-brand" style="width: {{ min(100, $aprobaciones * 50) }}%"></div>
            </div>
            <p class="mb-4 text-center text-sm text-body">
                {{ $aprobaciones }} de 2 docentes han aprobado
            </p>

            <div class="mb-4 flex items-center justify-between text-sm">
                <span class="text-body">Código SIS registrado</span>
                <span class="font-mono text-heading">{{ $sis }}</span>
            </div>

            <p class="text-center text-xs text-muted">Te notificaremos por correo cuando tu cuenta sea validada.</p>
        </div>

        <div class="mt-6 text-center">
            <x-ui.button variant="secondary" href="{{ route('login') }}" class="justify-center">
                {{ $habilitada ? 'Iniciar sesión' : 'Volver a iniciar sesión' }}
            </x-ui.button>
        </div>
    </div>
@endsection
