{{--
    @file    login.blade.php
    @author  T1 - Tech One SRL
    @created 2026-10-17
    @updated 2026-10-17
    @description Pantalla de inicio de sesión.
    @updated 2026-11-07
    @updated 2026-11-08
    @author Alisson D. Alvarado <alvaradoalissondalet@gmail.com>
    @changelog
    - 2026-11-07  [Alisson D. Alvarado]  feat: identificar el acceso a crear cuenta.
    - 2026-11-07  [Alisson D. Alvarado]  feat: conectar el inicio de sesión con el backend.
    - 2026-11-08  [Valery D. Ortuno P]  fix: el acceso rápido llena el nombre de
      usuario (Roberto, Patricia, Diego) y no el código SIS: el login busca por
      nombre de usuario y con el SIS rechazaba las credenciales.
--}}
@extends('layouts.guest')

@section('title', 'Iniciar sesión')

@section('brand')
    @include('partials.marca-invitado', [
        'titulo1' => 'Control de Ingreso',
        'titulo2' => 'a Exámenes masivos',
        'descripcion' => 'Accede con tu nombre de usuario para gestionar exámenes y monitoreo.',
        'stats' => true,
    ])
@endsection

@section('content')
    <div class="w-full max-w-md" x-data="{
        codSis: '{{ old('cod_sis') }}',
        password: '',
        verPassword: false,
        enviando: false,
        error: '',
        rellenar(cod, pass) { this.codSis = cod; this.password = pass; },
        async ingresar() {
            if (this.enviando) return;
            this.enviando = true;
            this.error = '';
            try {
                const respuesta = await fetch(@js(route('api.auth.login')), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                    body: JSON.stringify({ nombreUsuario: this.codSis, contrasena: this.password })
                });
                const datos = await respuesta.json();
                if (!respuesta.ok) {
                    this.error = datos.mensaje || 'Introduce tu usuario y contraseña.';
                    return;
                }
                window.location.assign(@js(route('inicio')));
            } catch (error) {
                this.error = 'No se pudo conectar con el servidor. Inténtalo nuevamente.';
            } finally {
                this.enviando = false;
            }
        }
    }">
        <h2 class="mb-6 text-2xl font-semibold text-heading">Iniciar sesión</h2>

        @if ($errors->any())
            <x-ui.alert type="danger" title="Error">
                {{ $errors->first() }}
            </x-ui.alert>
        @endif

        <p x-cloak x-show="error" x-text="error" role="alert" class="mb-4 text-sm text-fg-danger-strong"></p>
        <form method="POST" action="{{ route('login.store') }}" @submit.prevent="ingresar()">
            @csrf

            <div class="mb-4">
                <x-ui.input name="cod_sis" label="NOMBRE DE USUARIO" placeholder="Nombre de usuario"
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

            <div class="mb-4 flex flex-wrap items-center justify-end gap-3">
                <a href="#" class="text-sm font-semibold text-fg-brand">¿Olvidaste tu contraseña?</a>
            </div>

            <x-ui.button type="submit" class="w-full justify-center" x-bind:disabled="enviando">Entrar</x-ui.button>

            <p class="mt-4 text-center text-sm text-body">
                El sistema asigna la vista según tu cuenta: docente o auxiliar.
            </p>
            <p class="mt-2 text-center text-sm text-body">
                ¿Eres docente y no tienes cuenta?
                <a href="{{ route('registro') }}" class="font-semibold text-fg-brand">Crear cuenta</a>
            </p>
        </form>

        <div class="mt-6 rounded-base border border-dashed border-default p-4">
            <p class="mb-3 text-sm font-medium text-heading">Acceso rápido — usuarios de prueba</p>
            <div class="flex flex-col gap-2">
                <x-ui.button variant="secondary" type="button" class="w-full justify-center"
                             @click="rellenar('Roberto', 'pass123')">Docente — Roberto Silva</x-ui.button>
                <x-ui.button variant="secondary" type="button" class="w-full justify-center"
                             @click="rellenar('Patricia', 'pass456')">Docente — Patricia Rojas</x-ui.button>
                <x-ui.button variant="secondary" type="button" class="w-full justify-center"
                             @click="rellenar('Diego', 'pass789')">Auxiliar — Diego Mendoza</x-ui.button>
            </div>
            <p class="mt-3 text-xs text-muted">Credenciales de la BD de prueba — el backend las valida.</p>
        </div>
    </div>
@endsection
