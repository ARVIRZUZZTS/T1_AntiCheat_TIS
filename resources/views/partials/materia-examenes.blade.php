{{--
    @file    materia-examenes.blade.php
    @author  Alex Candia <alex.leonar.candia@gmail.com>
    @created 2026-10-05
    @updated 2026-10-05

    @description
    Sección "Exámenes" de la vista de detalle de una materia: lista los exámenes
    del curso (tipo, estado, fecha, ventana horaria, duración e inscritos) y
    muestra el botón "Crear examen", que abre el modal de alta. Es un bloque
    presentacional —sin wire:, el estado vive en Alpine dentro del modal—, así
    que va como parcial y no como componente Livewire.

    Los datos llegan por @include desde pages/materia-estudiantes.blade.php, que
    los recibe ya resueltos por App\Services\Examen\ListarExamenesCursoService:
    la vista no consulta la base ni decide el estado de cada examen.

    @changelog
    - 2026-10-05  [Alex Candia]  feat: creación inicial de la sección.

    @see  App\Services\Examen\ListarExamenesCursoService
    @see  resources/views/components/ui/modal-crear-examen.blade.php
    @see  resources/views/pages/materia-estudiantes.blade.php
--}}

@php
    use App\Services\Examen\ListarExamenesCursoService;

    $estados = [
        ListarExamenesCursoService::ESTADO_PROGRAMADO => ['brand', 'Programado'],
        ListarExamenesCursoService::ESTADO_EN_CURSO => ['status-en-revision', 'En curso'],
        ListarExamenesCursoService::ESTADO_FINALIZADO => ['gray', 'Finalizado'],
    ];

    $total = count($examenes);
    $resumen = $total === 1 ? '1 examen programado.' : $total.' exámenes programados.';
@endphp

<div class="flex flex-col gap-[2vh]">

    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="min-w-0">
            <h2 class="text-xl font-semibold text-heading">Exámenes</h2>
            <p class="text-sm text-body">{{ $resumen }}</p>
        </div>

        <x-ui.button variant="default" class="shrink-0" @click="$dispatch('abrir-modal-crear-examen')">
            <svg class="w-4 h-4 me-1.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-width="2" d="M12 5v14m7-7H5"/></svg>
            Crear examen
        </x-ui.button>
    </div>

    <ul class="flex flex-col gap-[2vh]">
        @forelse ($examenes as $examen)
            @php
                [$tipoEstado, $etiquetaEstado] = $estados[$examen['estado']] ?? $estados[ListarExamenesCursoService::ESTADO_PROGRAMADO];
                $horario = collect([$examen['hora_inicio'], $examen['hora_fin']])->filter()->implode(' – ');
                $duracion = $examen['duracion'] === null ? '—' : $examen['duracion'].' min';
            @endphp

            <li class="p-4 sm:p-6 bg-neutral-primary-soft border border-default rounded-base shadow-xs">
                <div class="flex flex-wrap items-center gap-2">
                    <h3 class="text-lg font-semibold text-heading">{{ $examen['tipo'] ?? 'Sin tipo' }}</h3>
                    <x-ui.badge :type="$tipoEstado">{{ $etiquetaEstado }}</x-ui.badge>
                </div>

                <dl class="mt-4 grid grid-cols-2 gap-3 sm:flex sm:flex-wrap sm:gap-x-8 text-sm">
                    <div class="min-w-0">
                        <dt class="text-xs text-muted">Fecha</dt>
                        <dd class="text-heading">
                            @if ($examen['fecha'])
                                <time datetime="{{ $examen['fecha'] }}">{{ $examen['fecha'] }}</time>
                            @else
                                —
                            @endif
                        </dd>
                    </div>

                    <div class="min-w-0">
                        <dt class="text-xs text-muted">Horario</dt>
                        <dd class="text-heading">{{ $horario === '' ? '—' : $horario }}</dd>
                    </div>

                    <div class="min-w-0">
                        <dt class="text-xs text-muted">Duración</dt>
                        <dd class="text-heading">{{ $duracion }}</dd>
                    </div>

                    <div class="min-w-0">
                        <dt class="text-xs text-muted">Inscritos</dt>
                        <dd class="text-heading">{{ $examen['inscritos'] }}</dd>
                    </div>
                </dl>
            </li>
        @empty
            <li class="flex flex-col items-center gap-2 p-8 text-center bg-neutral-primary-soft border border-default rounded-base shadow-xs">
                <svg class="w-6 h-6 text-fg-disabled" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-width="2" d="M4 10h16m-8-3V4M7 7V4m10 3V4M5 20h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2Zm3-7h.01v.01H8V13Zm4 0h.01v.01H12V13Zm4 0h.01v.01H16V13Zm-8 4h.01v.01H8V17Zm4 0h.01v.01H12V17Zm4 0h.01v.01H16V17Z"/></svg>
                <p class="text-sm text-body">Esta materia todavía no tiene exámenes programados.</p>
            </li>
        @endforelse
    </ul>

    <x-ui.modal-crear-examen :materia="$curso->nombre_curso" />
</div>
