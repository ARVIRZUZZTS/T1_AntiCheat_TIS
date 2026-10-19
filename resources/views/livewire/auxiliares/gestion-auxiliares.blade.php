@section('title', 'Auxiliares')

<div class="flex flex-col gap-6 px-[2vh] mt-[2vh]">
    <h2 class="text-xl font-semibold text-heading">Auxiliares</h2>

    @if ($mensajeExito !== '')
        <x-ui.alert type="success" title="Listo">{{ $mensajeExito }}</x-ui.alert>
    @endif
    @if ($mensajeError !== '')
        <x-ui.alert type="danger" title="Error">{{ $mensajeError }}</x-ui.alert>
    @endif

    <div class="bg-neutral-primary-soft border border-default rounded-base shadow-xs p-6">
        <h3 class="text-lg font-semibold text-heading mb-4">Crear auxiliar</h3>
        <form wire:submit="crear" class="grid gap-4 sm:grid-cols-2">
            <x-ui.input name="nombre" label="Nombre" wire:model="nombre" />
            <x-ui.input name="apellido" label="Apellido" wire:model="apellido" />
            <x-ui.input name="codSis" label="Código SIS" wire:model="codSis" />
            <x-ui.input name="password" type="password" label="Contraseña" wire:model="password" />
            <div class="sm:col-span-2">
                <label class="block text-sm font-medium text-heading mb-1">Curso</label>
                <select wire:model="idCurso" class="w-full rounded-base border border-default px-3 py-2">
                    @foreach ($cursos as $curso)
                        <option value="{{ $curso->id_curso }}">{{ $curso->nombre_curso }}</option>
                    @endforeach
                </select>
            </div>
            <div class="sm:col-span-2">
                <x-ui.button type="submit" variant="default">Crear auxiliar</x-ui.button>
            </div>
        </form>
    </div>

    <div class="bg-neutral-primary-soft border border-default rounded-base shadow-xs p-6">
        <h3 class="text-lg font-semibold text-heading mb-4">Auxiliares asignados</h3>
        <x-ui.table compact :headers="['Auxiliar', 'Código SIS', 'Curso', 'Estado']" :rows="$auxiliares->map(fn($a) => [
            ['heading' => true, 'value' => $a->nombre_usuario.' '.$a->apellido],
            $a->cod_sis,
            $a->nombre_curso,
            $a->estado,
        ])->toArray()" />
    </div>
</div>