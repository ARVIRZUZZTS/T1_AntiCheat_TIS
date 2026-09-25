@extends('layouts.app')

@section('title', 'Exámenes')

@section('content')
    <p class="text-body">
        CRUD de exámenes masivos. Datos mockeados: vendrán del endpoint
        <code class="text-fg-brand">/examenes</code>.
    </p>
    <x-ui.button variant="default" href="#" class="mt-4">Nuevo examen</x-ui.button>

    <div class="mt-6">
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