<div class="px-4 py-4 sm:px-6 sm:py-6 lg:px-8">
    <div class="mx-auto max-w-2xl rounded-base border border-default bg-neutral-primary-soft p-6 shadow-xs">
        <h2 class="text-lg font-semibold text-heading mb-4">Cargar lista de estudiantes</h2>

        <form wire:submit="cargar" enctype="multipart/form-data" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-body mb-1" for="archivo">Archivo Excel o CSV</label>
                <input id="archivo" type="file" wire:model="archivo" accept=".xlsx,.xls,.csv"
                       class="block w-full text-sm text-body file:mr-4 file:rounded-base file:border-0 file:bg-brand-softer file:text-fg-brand">
                @if ($archivo)
                    <p class="mt-1 text-sm text-body">{{ $archivo->getClientOriginalName() }}</p>
                @endif
                @error('archivo')
                    <p class="mt-1 text-sm text-fg-danger-strong">{{ $message }}</p>
                @enderror
            </div>

            @if ($mensajeError !== '')
                <p class="text-sm text-fg-danger-strong">{{ $mensajeError }}</p>
            @endif

            <div class="flex gap-2">
                <x-ui.button type="submit" wire:loading.attr="disabled" wire:target="cargar">
                    <span wire:loading.remove wire:target="cargar">Cargar</span>
                    <span wire:loading wire:target="cargar">Cargando...</span>
                </x-ui.button>
                <x-ui.button type="button" variant="secondary" wire:click="cancelar">Cancelar</x-ui.button>
            </div>
        </form>
    </div>
</div>
