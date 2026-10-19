{{--
    @file    login.blade.php
    @author  T1 - Tech One SRL
    @created 2026-10-17
    @updated 2026-10-17
    @description Pantalla de inicio de sesión.
--}}
@extends('layouts.guest')

@section('title', 'Iniciar sesión')

@section('brand')
    @include('partials.marca-invitado', [
        'titulo1' => 'Control de Ingreso',
        'titulo2' => 'a Exámenes masivos',
        'descripcion' => 'Accede con tu código SIS para gestionar exámenes y monitoreo.',
        'stats' => true,
    ])
@endsection

@section('content')
    <div class="w-full max-w-md" x-data="{
        codSis: '{{ old('cod_sis') }}',
        password: '',
        verPassword: false,
        recordar: false,
        rellenar(cod, pass) { this.codSis = cod; this.password = pass; }
    }">
        <h2 class="mb-6 text-2xl font-semibold text-heading">Iniciar sesión</h2>

        @if ($errors->any())
            <x-ui.alert type="danger" title="Error">
                {{ $errors->first() }}
            </x-ui.alert>
        @endif

        <form method="POST" action="{{ route('login.store') }}">
            @csrf

            <div class="mb-4">
                <x-ui.input name="cod_sis" label="USUARIO O CÓDIGO SIS" placeholder="202301310"
                            required autocomplete="username" x-model="codSis" />
            </div>

            <div class="mb-4 relative">
                <x-ui.input name="password" label="CONTRASEÑA" type="password" placeholder="••••••••"
                            required autocomplete="current-password" x-model="password" x-ref="pass" />
                <button type="button"
                        class="absolute end-3 top-9 text-body"
                        @click="verPassword = !verPassword; $refs.pass.type = verPassword ? 'text' : 'password'">
                    <span x-text="verPassword ? 'Ocultar' : 'Ver'"></span>
                </button>
            </div>

            <div class="mb-4 flex items-center justify-between">
                <x-ui.checkbox name="remember" label="Mantener sesión iniciada" x-model="recordar" />
                <a href="#" class="text-sm font-semibold text-fg-brand">¿Olvidaste tu contraseña?</a>
            </div>

            <x-ui.button type="submit" class="w-full justify-center">Entrar</x-ui.button>

            <p class="mt-4 text-center text-sm text-body">
                El sistema asigna la vista según tu código SIS: docente o auxiliar.
            </p>
            <p class="mt-2 text-center text-sm text-body">
                ¿Eres docente y no tienes cuenta?
                <a href="{{ route('registro') }}" class="font-semibold text-fg-brand">Regístrate aquí</a>
            </p>
        </form>

        <div class="mt-6 rounded-base border border-dashed border-default p-4">
            <p class="mb-3 text-sm font-medium text-heading">Acceso rápido — usuarios de prueba</p>
            <div class="flex flex-col gap-2">
                <x-ui.button variant="secondary" type="button" class="w-full justify-center"
                             @click="rellenar('DOC001', 'pass123')">Docente — Roberto Silva</x-ui.button>
                <x-ui.button variant="secondary" type="button" class="w-full justify-center"
                             @click="rellenar('DOC002', 'pass456')">Docente — Patricia Rojas</x-ui.button>
                <x-ui.button variant="secondary" type="button" class="w-full justify-center"
                             @click="rellenar('AUX001', 'pass789')">Auxiliar — Diego Mendoza</x-ui.button>
            </div>
            <p class="mt-3 text-xs text-muted">Credenciales de la BD de prueba — el backend las valida.</p>
        </div>
    </div>
@endsection
