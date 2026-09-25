@extends('layouts.app')

@section('title', 'Usuarios y roles')

@section('content')
    <p class="text-body">
        Gestión de usuarios, roles y permisos. Datos mockeados: vendrán del endpoint
        <code class="text-fg-brand">/usuarios</code>.
    </p>

    <div class="mt-6">
        <x-ui.table
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