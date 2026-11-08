{{--
    @file    registro-docente.blade.php
    @author  T1 - Tech One SRL
    @author  Alisson D. Alvarado <alvaradoalissondalet@gmail.com>
    @created 2026-10-17
    @updated 2026-11-07
    @description Formulario de registro de docente conectado al backend.
    @changelog
    - 2026-11-07  [Alisson D. Alvarado]  feat: validar los siete campos en el navegador.
    - 2026-11-07  [Alisson D. Alvarado]  feat: limitar el código SIS y compactar el formulario.
    - 2026-11-07  [Alisson D. Alvarado]  feat: conectar el registro con el servicio de cuentas.
    - 2026-11-07  [Alisson D. Alvarado]  feat: redirigir al inicio de sesión tras crear la cuenta.
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

        <form novalidate
              x-data="{
                  nombre: '', apellido: '', usuario: '', correo: '', codSis: '', password: '', password2: '',
                  ver1: false, ver2: false,
                  errores: {}, intento: false, creado: false, enviando: false, errorGeneral: '',
                  validar() {
                      this.errores = {};
                      if (!this.nombre.trim()) this.errores.nombre = 'El nombre es obligatorio';
                      if (!this.apellido.trim()) this.errores.apellido = 'El apellido es obligatorio';
                      if (!this.usuario.trim()) this.errores.usuario = 'El nombre de usuario es obligatorio';
                      if (!this.correo.trim()) this.errores.correo = 'El correo es obligatorio';
                      else if (!/^[^\s@]+@umss\.edu$/i.test(this.correo.trim())) {
                          this.errores.correo = 'El correo electrónico no es válido';
                      }
                      if (!this.codSis.trim()) this.errores.codSis = 'El código SIS es obligatorio';
                      else if (!/^\d{9}$/.test(this.codSis)) {
                          this.errores.codSis = 'El código SIS debe tener 9 dígitos';
                      }
                      if (!this.password.trim()) this.errores.password = 'La contraseña es obligatoria';
                      else if (this.password.trim().length < 8) {
                          this.errores.password = 'La contraseña debe tener al menos 8 caracteres';
                      }
                      if (!this.password2.trim()) {
                          this.errores.password2 = 'La confirmación de contraseña es obligatoria';
                      } else if (this.password.trim() !== this.password2.trim()) {
                          this.errores.password2 = 'Las contraseñas no coinciden';
                      }
                  },
                  async registrar() {
                      if (this.enviando) return;
                      this.intento = true;
                      this.creado = false;
                      this.errorGeneral = '';
                      this.validar();
                      if (Object.keys(this.errores).length) return;
                      this.enviando = true;
                      try {
                          const respuesta = await fetch(@js(route('api.auth.registro')), {
                              method: 'POST',
                              headers: { 'Content-Type': 'application/json', 'Accept': 'application/json',
                                  'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                              body: JSON.stringify({ nombre: this.nombre, apellido: this.apellido,
                                  nombreUsuario: this.usuario, correo: this.correo, codigoSis: this.codSis,
                                  contrasena: this.password, confirmarContrasena: this.password2 })
                          });
                          const datos = await respuesta.json();
                          if (!respuesta.ok) {
                              const campos = { nombreUsuario: 'usuario', codigoSis: 'codSis',
                                  contrasena: 'password', confirmarContrasena: 'password2' };
                              for (const [campo, mensaje] of Object.entries(datos.errores || {})) {
                                  this.errores[campos[campo] || campo] = mensaje;
                              }
                              if (!Object.keys(this.errores).length) this.errorGeneral = 'No se pudo crear la cuenta.';
                              return;
                          }
                          window.location.assign(@js(route('login')));
                      } catch (error) {
                          this.errorGeneral = 'No se pudo conectar con el servidor. Inténtalo nuevamente.';
                      } finally {
                          this.enviando = false;
                      }
                  }
              }"
              @input="if (intento) validar(); creado = false"
              @submit.prevent="registrar()">
            <p x-cloak x-show="creado" role="status" class="mb-4 text-sm text-fg-brand">
                Cuenta creada exitosamente. Ya puedes iniciar sesión.
            </p>
            <p x-cloak x-show="errorGeneral" x-text="errorGeneral" role="alert"
               class="mb-4 text-sm text-fg-danger-strong"></p>

            <div class="mb-2">
                <x-ui.input name="nombre" label="Nombre" placeholder="Nombre" required x-model="nombre"
                            aria-describedby="error-nombre" x-bind:aria-invalid="!!errores.nombre" />
                <p id="error-nombre" x-text="errores.nombre || ''" aria-live="polite"
                   class="min-h-4 text-sm text-fg-danger-strong"></p>
            </div>
            <div class="mb-2">
                <x-ui.input name="apellido" label="Apellido" placeholder="Apellido" required x-model="apellido"
                            aria-describedby="error-apellido" x-bind:aria-invalid="!!errores.apellido" />
                <p id="error-apellido" x-text="errores.apellido || ''" aria-live="polite"
                   class="min-h-4 text-sm text-fg-danger-strong"></p>
            </div>
            <div class="mb-2">
                <x-ui.input name="usuario" label="Nombre de usuario" placeholder="Nombre de usuario" required
                            x-model="usuario" autocomplete="username" aria-describedby="error-usuario"
                            x-bind:aria-invalid="!!errores.usuario" />
                <p id="error-usuario" x-text="errores.usuario || ''" aria-live="polite"
                   class="min-h-4 text-sm text-fg-danger-strong"></p>
            </div>
            <div class="mb-2">
                <x-ui.input name="correo" type="email" label="Correo electrónico" placeholder="nombre.apellido@umss.edu"
                            required x-model="correo" aria-describedby="error-correo"
                            x-bind:aria-invalid="!!errores.correo" />
                <p id="error-correo" x-text="errores.correo || ''" aria-live="polite"
                   class="min-h-4 text-sm text-fg-danger-strong"></p>
            </div>
            <div class="mb-2">
                <x-ui.input name="cod_sis" label="Código SIS" placeholder="202301310" required x-model="codSis"
                            inputmode="numeric" maxlength="9" pattern="[0-9]{9}"
                            @input="codSis = $event.target.value.replace(/[^0-9]/g, '').slice(0, 9); $event.target.value = codSis"
                            aria-describedby="error-cod-sis" x-bind:aria-invalid="!!errores.codSis" />
                <p id="error-cod-sis" x-text="errores.codSis || ''" aria-live="polite"
                   class="min-h-4 text-sm text-fg-danger-strong"></p>
            </div>
            <div class="mb-2 relative">
                <x-ui.input name="password" label="Contraseña" type="password" required x-model="password" x-ref="p1"
                            autocomplete="new-password" aria-describedby="error-password"
                            x-bind:aria-invalid="!!errores.password" />
                <button type="button" class="absolute end-3 top-9 text-body" aria-label="Mostrar u ocultar contraseña"
                        @click="ver1 = !ver1; $refs.p1.type = ver1 ? 'text' : 'password'">
                    <span x-text="ver1 ? 'Ocultar' : 'Ver'"></span>
                </button>
                <p id="error-password" x-text="errores.password || ''" aria-live="polite"
                   class="min-h-4 text-sm text-fg-danger-strong"></p>
            </div>
            <div class="mb-2 relative">
                <x-ui.input name="password_confirmation" label="Confirmar contraseña" type="password" required
                            x-model="password2" x-ref="p2" autocomplete="new-password"
                            aria-describedby="error-password-confirmation" x-bind:aria-invalid="!!errores.password2" />
                <button type="button" class="absolute end-3 top-9 text-body"
                        aria-label="Mostrar u ocultar confirmación de contraseña"
                        @click="ver2 = !ver2; $refs.p2.type = ver2 ? 'text' : 'password'">
                    <span x-text="ver2 ? 'Ocultar' : 'Ver'></span>
                </button>
                <p id="error-password-confirmation" x-text="errores.password2 || ''" aria-live="polite"
                   class="min-h-4 text-sm text-fg-danger-strong"></p>
            </div>

            <x-ui.button type="submit" class="w-full justify-center" x-bind:disabled="enviando">
                <span x-text="enviando ? 'Registrando...' : 'Registrarme'"></span>
            </x-ui.button>

            <p class="mt-4 text-center text-sm text-body">
                ¿Ya tienes cuenta?
                <a href="{{ route('login') }}" class="font-semibold text-fg-brand">Inicia sesión</a>
            </p>
        </form>
    </div>
@endsection
