{{--
    @file    modal-restaurar-curso.blade.php
    @author  Equipo T1
    @created 2026-11-21
    @updated 2026-11-22

    @description
    Modal de confirmación para restaurar un curso archivado. Se usa desde la
    pestaña «Restaurar» de la página única Archivar/Restaurar cursos
    (Refs #326).
--}}

@props([
    'cursoId' => null,
    'nombreCurso' => '',
])

@if ($cursoId !== null)
    <div
        x-data="{ abierto: $wire.entangle('modalAbierto') }"
        x-show="abierto"
        x-cloak
        x-effect="if (abierto) $nextTick(() => $refs.confirmarRestaurar?.focus())"
        x-on:keydown.escape.window="$wire.cancelar()"
        x-on:click.self="$wire.cancelar()"
        x-on:keydown.tab="
            if ($event.shiftKey && document.activeElement === $refs.confirmarRestaurar) {
                $event.preventDefault();
                $refs.cancelarRestaurar.focus();
            } else if (!$event.shiftKey && document.activeElement === $refs.cancelarRestaurar) {
                $event.preventDefault();
                $refs.confirmarRestaurar.focus();
            }
        "
        class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-neutral-950/60 p-4"
    >
        <div
            class="w-full max-w-md rounded-base border border-default bg-neutral-primary-soft shadow-xl"
            role="dialog"
            aria-modal="true"
            aria-labelledby="modal-restaurar-curso-titulo"
            aria-describedby="modal-restaurar-curso-descripcion"
        >
            <div class="border-b border-default px-5 py-4">
                <h2 id="modal-restaurar-curso-titulo" class="text-lg font-semibold text-heading">Restaurar curso</h2>
            </div>

            <div class="px-5 py-4">
                <p id="modal-restaurar-curso-descripcion" class="text-sm text-body">
                    ¿Restaurar el curso <span class="font-semibold text-heading">{{ $nombreCurso }}</span>?
                    Volverá a estado En curso y podrás seguir gestionándolo.
                </p>
            </div>

            <div class="flex flex-col-reverse gap-2 border-t border-default px-5 py-4 sm:flex-row sm:justify-end">
                <x-ui.button
                    type="button"
                    variant="ghost"
                    size="sm"
                    wire:click="cancelar"
                    x-ref="cancelarRestaurar"
                >
                    Cancelar
                </x-ui.button>
                <x-ui.button
                    type="button"
                    variant="secondary"
                    size="sm"
                    wire:click="confirmarRestauracion"
                    wire:loading.attr="disabled"
                    x-ref="confirmarRestaurar"
                >
                    Restaurar
                </x-ui.button>
            </div>
        </div>
    </div>
@endif
