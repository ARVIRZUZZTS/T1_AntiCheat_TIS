{{--
    @file    examenes.blade.php
    @author  David E. Chavez T. <virzuzz12345@gmail.com>
    @created 2026-09-29
    @updated 2026-09-29

    @description
    Vista de gestión de exámenes. Muestra la lista de exámenes programados.

    @changelog
    - 2026-09-29  [David E. Chavez T.]  feat: vista de exámenes.
--}}

@extends('layouts.app')

@section('title', 'Exámenes')

@section('content')
    <p class="text-body">
        CRUD de exámenes masivos. Datos mockeados: vendrán del endpoint
        <code class="text-fg-brand">/examenes</code>.
    </p>

    <div class="mt-6 px-[2vh]">
        <x-ui.table
            :headers="['Examen', 'Materia', 'Fecha', 'Duración', 'Estado']"
            :rows="[
                [['heading' => true, 'value' => 'Parcial 1 Matemática'], 'Matemática I', '2026-10-05', '90 min', 'Habilitado'],
                [['heading' => true, 'value' => 'Examen Final Física'], 'Física II', '2026-10-12', '120 min', 'Habilitado'],
                [['heading' => true, 'value' => 'Recuperatorio Química'], 'Química Orgánica', '2026-10-19', '90 min', 'Pendiente'],
            ]"
        />
    </div>
@endsection