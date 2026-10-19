{{--
    @file    modal-rechazar-solicitud.blade.php
    @author  T1 - Tech One SRL
    @created 2026-10-17
    @updated 2026-10-17
    @description Modal de rechazo de solicitud de docente con motivo.
--}}
<div x-show="modal.abierta && modal.tipo === 'rechazar'" x-cloak
     @click.self="modal.abierta = false" @keydown.escape.window="modal.abierta = false"
     class="fixed inset-0 z-50 flex items-center justify-center bg-overlay-modal/50 p-4">
    <div class="w-full max-w-md rounded-base border border-default bg-neutral-primary-soft p-4 shadow-sm md:p-6">
        <div class="mb-4 flex items-center justify-between border-b border-default pb-3">
            <h3 class="text-lg font-medium text-heading">Rechazar solicitud</h3>
            <button type="button" class="text-body" @click="modal.abierta = false">✕</button>
        </div>
        <div class="mb-4">
            <label class="mb-2 block text-sm font-medium text-heading">MOTIVO DEL RECHAZO</label>
            <textarea x-model="modal.motivo" rows="3"
                      class="block w-full rounded-base border border-default-medium bg-neutral-secondary-medium px-3 py-2.5 text-sm text-heading"></textarea>
            <p x-show="modal.error" x-text="modal.error" class="mt-1 text-sm text-fg-danger-strong"></p>
        </div>
        <div class="flex flex-col gap-3 sm:flex-row">
            <x-ui.button variant="danger" type="button" class="w-full justify-center sm:w-auto" @click="rechazar(modal.id)">Rechazar</x-ui.button>
            <x-ui.button-cancelar type="button" class="w-full justify-center sm:w-auto" @click="modal.abierta = false">Cancelar</x-ui.button-cancelar>
        </div>
    </div>
</div>
