{{--
    @file    archivar-curso.blade.php
    @author  Equipo T1
    @created 2026-11-21

    @description
    Vista de la subsección «Archivar curso»: cursos activos del docente con un
    botón «Archivar» por curso, modal de confirmación y aviso de éxito.
--}}

@section('title', 'Archivar curso')

<div class="px-4 py-4 sm:px-6 sm:py-6 lg:px-8">
    <div class="mx-auto w-full max-w-2xl overflow-hidden rounded-base border border-default bg-neutral-primary-soft shadow-xs">
        @if ($cursos->isEmpty())
            <p class="p-8 text-center text-sm text-body">No tienes cursos en curso para archivar.</p>
        @else
            <ul>
                @foreach ($cursos as $curso)
                    <li class="flex flex-col gap-3 border-b border-default p-4 last:border-b-0 sm:flex-row sm:items-center sm:justify-between sm:gap-4 sm:p-5">
                        <p class="min-w-0 font-medium text-heading break-words">{{ $curso->nombre_curso }}</p>

                        <x-ui.button
                            type="button"
                            variant="secondary"
                            size="sm"
                            class="w-full min-h-11 shrink-0 sm:w-auto"
                            aria-label="Archivar curso {{ $curso->nombre_curso }}"
                            wire:click="abrirModal({{ $curso->id_curso }})"
                        >
                            Archivar
                        </x-ui.button>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>

    @if ($mostrarExito)
        <div
            role="status"
            aria-live="polite"
            class="fixed top-4 end-4 z-[60] w-[calc(100%-2rem)] max-w-sm"
        >
            <div class="flex items-center gap-3 rounded-base border border-default bg-neutral-primary-soft p-4 shadow-xs">
                <span class="inline-flex shrink-0 items-center justify-center w-8 h-8 rounded bg-success-soft text-fg-success-strong">
                    <svg class="w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 11.917 9.724 16.5 19 7.5"/></svg>
                </span>

                <p class="min-w-0 flex-1 text-sm text-heading">{{ $mensajeExito }}</p>

                <button
                    type="button"
                    wire:click="$set('mostrarExito', false)"
                    class="inline-flex shrink-0 items-center justify-center w-8 h-8 rounded-base text-body hover:bg-neutral-secondary-medium hover:text-heading"
                >
                    <span class="sr-only">Cerrar aviso</span>
                    <svg class="w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 17.94 6M18 18 6.06 6"/></svg>
                </button>
            </div>
        </div>
    @endif

    <x-ui.modal-archivar-curso
        id="modal-archivar-curso"
        :abierto="$modalAbierto"
        :nombre="$cursoNombre"
        :archivando="$archivando"
        :error="$errorArchivar"
    />
</div>

@script
<script>
    let botonOrigenArchivar = null;

    $wire.$watch('modalAbierto', (abierto) => {
        if (abierto) {
            botonOrigenArchivar = document.activeElement instanceof HTMLElement ? document.activeElement : null;
            requestAnimationFrame(() => document.getElementById('archivar-curso-cancelar')?.focus());

            return;
        }

        if (botonOrigenArchivar?.isConnected) {
            botonOrigenArchivar.focus();
        }
        botonOrigenArchivar = null;
    });

    document.addEventListener('keydown', (event) => {
        if (! $wire.modalAbierto || event.key !== 'Tab') {
            return;
        }

        const modal = document.getElementById('modal-archivar-curso');
        const botones = [...(modal?.querySelectorAll('button:not([disabled])') ?? [])];
        if (botones.length === 0) {
            return;
        }

        const primero = botones[0];
        const ultimo = botones[botones.length - 1];
        if (event.shiftKey && document.activeElement === primero) {
            event.preventDefault();
            ultimo.focus();
        } else if (! event.shiftKey && document.activeElement === ultimo) {
            event.preventDefault();
            primero.focus();
        }
    });
</script>
@endscript
