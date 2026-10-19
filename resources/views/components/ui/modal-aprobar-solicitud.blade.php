{{--
    @file    modal-aprobar-solicitud.blade.php
    @author  T1 - Tech One SRL
    @created 2026-10-17
    @updated 2026-10-17
    @description Modal de confirmación de aprobación de solicitud de docente.
--}}
<div x-show="modal.abierta && modal.tipo === 'aprobar'" x-cloak
     @click.self="modal.abierta = false" @keydown.escape.window="modal.abierta = false"
     class="fixed inset-0 z-50 flex items-center justify-center bg-overlay-modal/50 p-4">
    <div class="w-full max-w-md rounded-base border border-default bg-neutral-primary-soft p-4 shadow-sm md:p-6">
        <div class="mb-4 flex items-center justify-between border-b border-default pb-3">
            <h3 class="text-lg font-medium text-heading">Aprobar solicitud</h3>
            <button type="button" class="text-body" @click="modal.abierta = false">✕</button>
        </div>
        <p class="mb-4 text-sm text-body">
            La cuenta del docente quedará habilitada cuando 2 docentes verificados la aprueben.
        </p>
        <p class="mb-4 text-sm font-semibold text-heading">
            Nombre: <span x-text="modal.nombre"></span> (SIS: <span x-text="modal.sis"></span>)
        </p>
        <div class="flex flex-col gap-3 sm:flex-row">
            <x-ui.button type="button" class="w-full justify-center sm:w-auto" @click="aprobar(modal.id)">Aprobar</x-ui.button>
            <x-ui.button-cancelar type="button" class="w-full justify-center sm:w-auto" @click="modal.abierta = false">Cancelar</x-ui.button-cancelar>
        </div>
    </div>
</div>
