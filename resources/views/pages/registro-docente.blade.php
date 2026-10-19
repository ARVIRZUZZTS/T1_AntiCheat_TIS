{{--
    @file    registro-docente.blade.php
    @author  T1 - Tech One SRL
    @created 2026-10-17
    @updated 2026-10-17
    @description Formulario de registro de docente (mock de vista).
--}}
@extends('layouts.guest')

@section('title', 'Registrar docente')

@section('brand')
    @include('partials.marca-invitado', [
        'titulo1' => 'Asínete como',
        'titulo2' => 'docente',
        'descripcion' => 'Tu cuenta será revisada por un docente ya habilitado antes de darte acceso.',
        'stats' => false,
    ])
@endsection

@section('content')
    <div class="w-full max-w-md">
        <h2 class="mb-6 text-2xl font-semibold text-heading">Registrar nuevo docente</h2>

        <form method="POST" action="#"
              x-data="{
                  nombre: '', apellido: '', correo: '', codSis: '', password: '', password2: '',
                  ver1: false, ver2: false,
                  errores: {},
                  validar() {
                      this.errores = {};
                      if (!this.nombre) this.errores.nombre = 'Requerido';
                      if (!this.apellido) this.errores.apellido = 'Requerido';
                      if (!/^[^\\s@]+@[^\\s@]+\\.[^\\s@]+$/.test(this.correo)) this.errores.correo = 'Correo inválido';
                      if (!this.codSis) this.errores.codSis = 'Requerido';
                      if (this.password.length < 8) this.errores.password = 'Mínimo 8 caracteres';
                      if (this.password !== this.password2) this.errores.password2 = 'No coincide';
                      if (Object.keys(this.errores).length) return;
                      window.location.href = '{{ route('solicitud-revision') }}' + '?sis=' + encodeURIComponent(this.codSis) + '&nombre=' + encodeURIComponent(this.nombre);
                  }
              }"
              @submit.prevent="validar()">
            @csrf

            <div class="mb-4">
                <x-ui.input name="nombre" label="NOMBRE" placeholder="Nombre" required x-model="nombre" />
                <p x-show="errores.nombre" x-text="errores.nombre" class="mt-1 text-sm text-fg-danger-strong"></p>
            </div>
            <div class="mb-4">
                <x-ui.input name="apellido" label="APELLIDO" placeholder="Apellido" required x-model="apellido" />
                <p x-show="errores.apellido" x-text="errores.apellido" class="mt-1 text-sm text-fg-danger-strong"></p>
            </div>
            <div class="mb-4">
                <x-ui.input name="correo" type="email" label="CORREO" placeholder="nombre.apellido@umss.edu" required x-model="correo" />
                <p x-show="errores.correo" x-text="errores.correo" class="mt-1 text-sm text-fg-danger-strong"></p>
            </div>
            <div class="mb-4">
                <x-ui.input name="cod_sis" label="CÓDIGO SIS" placeholder="202301310" required x-model="codSis" />
                <p x-show="errores.codSis" x-text="errores.codSis" class="mt-1 text-sm text-fg-danger-strong"></p>
            </div>
            <div class="mb-4 relative">
                <x-ui.input name="password" label="CONTRASEÑA" type="password" required x-model="password" x-ref="p1" />
                <button type="button" class="absolute end-3 top-9 text-body" @click="ver1=!ver1; $refs.p1.type = ver1?'text':'password'">
                    <span x-text="ver1 ? 'Ocultar' : 'Ver'"></span>
                </button>
                <p x-show="errores.password" x-text="errores.password" class="mt-1 text-sm text-fg-danger-strong"></p>
            </div>
            <div class="mb-4 relative">
                <x-ui.input name="password_confirmation" label="CONFIRMAR CONTRASEÑA" type="password" required x-model="password2" x-ref="p2" />
                <button type="button" class="absolute end-3 top-9 text-body" @click="ver2=!ver2; $refs.p2.type = ver2?'text':'password'">
                    <span x-text="ver2 ? 'Ocultar' : 'Ver'"></span>
                </button>
                <p x-show="errores.password2" x-text="errores.password2" class="mt-1 text-sm text-fg-danger-strong"></p>
            </div>

            <x-ui.alert type="warning" title="Necesitas 2 aprobaciones para activarte">
                Dos docentes ya verificados deben aceptar tu solicitud.
            </x-ui.alert>

            <x-ui.button type="submit" class="w-full justify-center">Registrarme</x-ui.button>

            <p class="mt-4 text-center text-sm text-body">
                ¿Ya tienes cuenta?
                <a href="{{ route('login') }}" class="font-semibold text-fg-brand">Inicia sesión</a>
            </p>
        </form>
    </div>
@endsection
