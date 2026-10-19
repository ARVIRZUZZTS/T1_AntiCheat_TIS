@extends('layouts.app')

@section('title', 'Monitor en vivo - Mock')

<div class="flex flex-col gap-6 px-[2vh] mt-[2vh]">
    <h2 class="text-xl font-semibold text-heading">Mock de formatos (solo prueba)</h2>

    <div class="flex gap-2">
        <x-ui.button variant="default">Ingreso</x-ui.button>
        <x-ui.button variant="secondary">Monitor</x-ui.button>
    </div>

    <div class="grid grid-cols-2 gap-4 lg:grid-cols-6">
        <x-ui.stat-card value="08:00 – 09:30" label="En curso" tone="brand" label-first nowrap />
        <x-ui.stat-card value="30" label="Inscritos" tone="brand" />
        <x-ui.stat-card value="25" label="Habilitados" tone="success" />
        <x-ui.stat-card value="5" label="Deshabilitados" tone="danger" />
        <x-ui.stat-card value="2" label="Sospechosos" tone="warning" />
        <x-ui.stat-card value="1" label="Tramposos" tone="danger" />
    </div>

    <div class="bg-neutral-primary-soft border border-default rounded-base shadow-xs p-6">
        <h3 class="text-lg font-semibold text-heading mb-4">Vista Ingreso</h3>
        <x-ui.table compact :headers="['Estudiante', 'Código SIS', 'Estados', 'Acción']" :rows="[
            [['heading' => true, 'value' => 'Ana Pérez'], 'SIS001', ['html' => '<span class=\'inline-flex px-2 py-1 text-xs rounded bg-green-100 text-green-800\'>Habilitado</span> <span class=\'inline-flex px-2 py-1 text-xs rounded bg-gray-100 text-gray-800\'>Pendiente</span>'], ['html' => '<button class=\'px-3 py-1.5 text-xs text-white bg-brand rounded\'>Ingreso</button>']],
            [['heading' => true, 'value' => 'Luis Gómez'], 'SIS002', ['html' => '<span class=\'inline-flex px-2 py-1 text-xs rounded bg-red-100 text-red-800\'>Deshabilitado</span> <span class=\'inline-flex px-2 py-1 text-xs rounded bg-blue-100 text-blue-800\'>Ingresó</span>'], ['html' => '<button class=\'px-3 py-1.5 text-xs text-white bg-brand rounded\'>Reportar</button>']],
        ]" />
    </div>

    <div class="bg-neutral-primary-soft border border-default rounded-base shadow-xs p-6">
        <h3 class="text-lg font-semibold text-heading mb-4">Vista Monitor</h3>
        <x-ui.table compact :headers="['Estudiante', 'Código SIS', 'Hora', 'Registro', 'Estados', 'Acción']" :rows="[
            [['heading' => true, 'value' => 'Ana Pérez'], 'SIS001', '08:05', 'Docente', ['html' => '<span class=\'inline-flex px-2 py-1 text-xs rounded bg-green-100 text-green-800\'>Habilitado</span> <span class=\'inline-flex px-2 py-1 text-xs rounded bg-blue-100 text-blue-800\'>Ingresó</span>'], ['html' => '<button class=\'px-3 py-1.5 text-xs text-white bg-brand rounded\'>Reportar</button>']],
        ]" />
    </div>
</div>