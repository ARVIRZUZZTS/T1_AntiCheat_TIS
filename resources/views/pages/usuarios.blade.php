{{--
    @file    usuarios.blade.php
    @author  David E. Chavez T. <virzuzz12345@gmail.com>
    @created 2026-09-29
    @updated 2026-09-29

    @description
    Vista de gestión de usuarios y roles. Muestra la lista de usuarios del sistema.

    @changelog
    - 2026-09-29  [David E. Chavez T.]  feat: vista de usuarios y roles.
    - 2026-10-12  [Diego Tejerina]  fix: la tabla fija un ancho mínimo
      (min-w-[640px]) para que en mobile las columnas no se aplasten; el
      scroll horizontal queda dentro de la tabla, no en la página.
--}}

@extends('layouts.app')

@section('title', 'Usuarios y roles')

@section('content')
    <p class="text-body">
        Gestión de usuarios, roles y permisos. Datos mockeados: vendrán del endpoint
        <code class="text-fg-brand">/usuarios</code>.
    </p>

    <div class="mt-6 px-[2vh]">
        <x-ui.table
            min-width="min-w-[640px]"
            :headers="['Usuario', 'Email', 'Rol', 'Estado']"
            :rows="[
                [['heading' => true, 'value' => 'Valery Ortuno'], 'valery@t1.com', 'Administrador', 'Activo'],
                [['heading' => true, 'value' => 'David Chavez'], 'david@t1.com', 'Proctor', 'Activo'],
                [['heading' => true, 'value' => 'Alex Candia'], 'alex@t1.com', 'Proctor', 'En revisión'],
                [['heading' => true, 'value' => 'Alisson Alvarado'], 'alisson@t1.com', 'Docente', 'Activo'],
            ]"
        />
    </div>
@endsection