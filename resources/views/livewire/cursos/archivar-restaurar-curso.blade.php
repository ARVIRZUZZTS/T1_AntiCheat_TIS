{{--
    @file    archivar-restaurar-curso.blade.php
    @author  OchoaCesar
    @created 2026-11-22
    @updated 2026-11-23

    @description
    Página única «Archivar/Restaurar cursos»: una sola lista con los cursos en
    curso primero y los archivados al final. Cada tarjeta muestra su acción
    como texto: «Archivar» en rojo para los activos y «Restaurar» en gris para
    los archivados; la confirman sus modales y comparten el aviso inline de
    éxito. El error de la restauración se muestra en un banner role="alert".
    Bajo el título de cada curso se muestra su gestión con año, igual que en
    «Ver cursos».
--}}

@section('title', 'Archivar / Restaurar cursos')

<div class="px-4 py-4 sm:px-6 sm:py-6 lg:px-8">
    <header class="mx-auto mb-4 flex w-full max-w-2xl flex-col gap-1 sm:mb-5">
        <p class="text-sm text-body">Archiva un curso que ya no gestionas o restaura uno archivado.</p>
    </header>

    @if (session('error'))
        <div role="alert" class="mx-auto mb-4 w-full max-w-2xl rounded-base border border-danger bg-danger-soft px-4 py-3 text-sm text-heading">
            {{ session('error') }}
        </div>
    @endif

    <div class="mx-auto w-full max-w-2xl overflow-hidden rounded-base border border-default bg-neutral-primary-soft shadow-xs">
        @if ($cursos->isEmpty())
            <div class="flex flex-col items-center gap-3 p-8 text-center">
                <svg class="size-10 text-body-subtle" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5v9a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 16.5v-9m16.5 0A2.25 2.25 0 0 0 18 5.25H6a2.25 2.25 0 0 0-2.25 2.25m16.5 0v-.75A2.25 2.25 0 0 0 18 4.5H6a2.25 2.25 0 0 0-2.25 2.25v.75m7.5 4.5h3" />
                </svg>
                <p class="text-sm text-body">
                    No tienes cursos para archivar ni restaurar.
                </p>
            </div>
        @else
            <ul>
                @foreach ($cursos as $curso)
                    <li class="flex flex-col gap-3 border-b border-default p-4 last:border-b-0 sm:flex-row sm:items-center sm:justify-between sm:gap-4 sm:p-5">
                        <div class="min-w-0">
                            <p class="font-medium text-heading break-words">{{ $curso->nombre_curso }}</p>
                            <p class="mt-1 text-xs text-body">{{ $gestiones->get($curso->id_curso) ?? 'Gestion no registrada' }}</p>
                        </div>

                        @if ($curso->estado === \App\Enums\CursoEstado::Archivado->value)
                            <button
                                type="button"
                                class="inline-flex min-h-11 shrink-0 items-center px-2 text-sm font-medium text-body-subtle underline-offset-2 hover:text-heading hover:underline focus:rounded-base focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-medium"
                                aria-label="Restaurar curso {{ $curso->nombre_curso }}"
                                aria-haspopup="dialog"
                                wire:click='abrirModalRestaurar({{ $curso->id_curso }}, {!! \Illuminate\Support\Js::from($curso->nombre_curso) !!})'
                            >
                                Restaurar
                            </button>
                        @else
                            <button
                                type="button"
                                class="inline-flex min-h-11 shrink-0 items-center px-2 text-sm font-medium text-fg-danger-strong underline-offset-2 hover:underline focus:rounded-base focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-medium"
                                aria-label="Archivar curso {{ $curso->nombre_curso }}"
                                aria-haspopup="dialog"
                                wire:click="abrirModalArchivar({{ $curso->id_curso }})"
                            >
                                Archivar
                            </button>
                        @endif
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
                    wire:click="cerrarAviso"
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
        :abierto="$modalArchivarAbierto"
        :nombre="$cursoNombre"
        :archivando="$archivando"
        :error="$errorArchivar"
    />

    <x-ui.modal-restaurar-curso :curso-id="$modalAbierto ? $cursoId : null" :nombre-curso="$cursoNombre" />
</div>

@script
<script>
    let botonOrigenArchivar = null;

    $wire.$watch('modalArchivarAbierto', (abierto) => {
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
        if (! $wire.modalArchivarAbierto || event.key !== 'Tab') {
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
