{{--
    @file    archivar-restaurar-curso.blade.php
    @author  OchoaCesar
    @created 2026-11-22

    @description
    Página única «Archivar/Restaurar cursos»: dos pestañas con un conmutador
    (wire:click, sin Alpine). Archivar lista los cursos en curso y Restaurar
    los archivados; cada lista confirma su acción en su modal y ambas
    acciones comparten el mismo aviso inline de éxito. El error de la
    restauración se muestra en un banner con role="alert".
--}}

@php
    $tabClases = 'shrink-0 whitespace-nowrap inline-flex items-center justify-center box-border border focus:ring-4 shadow-xs font-medium leading-5 rounded-base focus:outline-none px-4 py-2.5 text-sm';
    $tabActivo = 'text-white bg-brand border-transparent hover:bg-brand-strong focus:ring-brand-medium';
    $tabInactivo = 'text-body bg-neutral-secondary-medium border-default-medium hover:bg-neutral-tertiary-medium hover:text-heading focus:ring-neutral-tertiary';
@endphp

@section('title', 'Archivar / Restaurar cursos')

<div class="px-4 py-4 sm:px-6 sm:py-6 lg:px-8">
    <header class="mx-auto mb-4 flex w-full max-w-2xl flex-col gap-1 sm:mb-5">
        <h1 class="text-xl font-semibold text-heading sm:text-2xl">Archivar / Restaurar cursos</h1>
        <p class="text-sm text-body">Archiva un curso que ya no gestionas o restaura uno archivado.</p>
    </header>

    @if (session('error'))
        <div role="alert" class="mx-auto mb-4 w-full max-w-2xl rounded-base border border-danger bg-danger-soft px-4 py-3 text-sm text-heading">
            {{ session('error') }}
        </div>
    @endif

    <nav class="mx-auto mb-4 flex w-full max-w-2xl min-w-0 gap-2 overflow-x-auto" aria-label="Acciones de cursos archivados">
        <button type="button"
                class="{{ $tabClases }} {{ $pestana === 'archivar' ? $tabActivo : $tabInactivo }}"
                wire:click="cambiarPestana('archivar')"
                @if ($pestana === 'archivar') aria-current="page" @endif>
            Archivar
        </button>
        <button type="button"
                class="{{ $tabClases }} {{ $pestana === 'restaurar' ? $tabActivo : $tabInactivo }}"
                wire:click="cambiarPestana('restaurar')"
                @if ($pestana === 'restaurar') aria-current="page" @endif>
            Restaurar
        </button>
    </nav>

    <div class="mx-auto w-full max-w-2xl overflow-hidden rounded-base border border-default bg-neutral-primary-soft shadow-xs">
        @if ($cursos->isEmpty())
            <div class="flex flex-col items-center gap-3 p-8 text-center">
                <svg class="size-10 text-body-subtle" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5v9a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 16.5v-9m16.5 0A2.25 2.25 0 0 0 18 5.25H6a2.25 2.25 0 0 0-2.25 2.25m16.5 0v-.75A2.25 2.25 0 0 0 18 4.5H6a2.25 2.25 0 0 0-2.25 2.25v.75m7.5 4.5h3" />
                </svg>
                <p class="text-sm text-body">
                    {{ $pestana === 'archivar' ? 'No tienes cursos en curso para archivar.' : 'No tienes cursos archivados' }}
                </p>
            </div>
        @else
            <ul>
                @foreach ($cursos as $curso)
                    <li class="flex flex-col gap-3 border-b border-default p-4 last:border-b-0 sm:flex-row sm:items-center sm:justify-between sm:gap-4 sm:p-5">
                        <p class="min-w-0 font-medium text-heading break-words">{{ $curso->nombre_curso }}</p>

                        @if ($pestana === 'archivar')
                            <x-ui.button
                                type="button"
                                variant="secondary"
                                size="sm"
                                class="w-full min-h-11 shrink-0 sm:w-auto"
                                aria-label="Archivar curso {{ $curso->nombre_curso }}"
                                wire:click="abrirModalArchivar({{ $curso->id_curso }})"
                            >
                                Archivar
                            </x-ui.button>
                        @else
                            <x-ui.button
                                type="button"
                                variant="secondary"
                                size="sm"
                                class="w-full min-h-11 shrink-0 sm:w-auto"
                                aria-label="Restaurar curso {{ $curso->nombre_curso }}"
                                wire:click='abrirModalRestaurar({{ $curso->id_curso }}, {!! \Illuminate\Support\Js::from($curso->nombre_curso) !!})'
                                aria-haspopup="dialog"
                            >
                                Restaurar
                            </x-ui.button>
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

    @if ($pestana === 'archivar')
        <x-ui.modal-archivar-curso
            id="modal-archivar-curso"
            :abierto="$modalArchivarAbierto"
            :nombre="$cursoNombre"
            :archivando="$archivando"
            :error="$errorArchivar"
        />
    @else
        <x-ui.modal-restaurar-curso :curso-id="$modalAbierto ? $cursoId : null" :nombre-curso="$cursoNombre" />
    @endif
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
