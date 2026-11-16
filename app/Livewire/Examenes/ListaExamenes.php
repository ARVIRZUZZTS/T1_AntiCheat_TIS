<?php

/**
 * @file    ListaExamenes.php
 * @author  Alex Candia <alex.leonar.candia@gmail.com>
 * @author  Diego Tejerina <josediegotejerinamolina@gmail.com>
 * @created 2026-11-15
 *
 * @description
 * Componente Livewire de la subopción "Ver lista de exámenes": lista plana de
 * los exámenes del docente (propios + los que le compartieron por invitación
 * aceptada), con buscador dinámico y filtros rápidos por propiedad
 * (Propios / Invitados) y por fecha (Próximos / Finalizados). A diferencia de
 * la página anterior, NO tiene selector de materia: muestra todos los exámenes
 * juntos. El mapeo de cada examen reutiliza la misma idea que
 * ListarExamenesCursoService (Alex Candia), pero en una consulta global.
 *
 * @changelog
 * - 2026-11-15  [Diego Tejerina]  feat: lista plana de exámenes con buscador y
 *   filtros Propios/Invitados y Próximos/Finalizados (reemplaza el selector de
 *   materia).
 *
 * @see  App\Services\Examen\ListarExamenesCursoService
 */

namespace App\Livewire\Examenes;

use App\Enums\InvitacionEstado;
use App\Enums\TipoExamen;
use App\Models\Examen;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class ListaExamenes extends Component
{
    public string $busqueda = '';

    /** Filtro de propiedad: todos, propio o invitado. */
    public string $propiedad = 'todos';

    /** Filtro por fecha: todos, proximos o finalizados. */
    public string $fecha = 'todos';

    public function filtrarPropiedad(string $valor): void
    {
        $this->propiedad = in_array($valor, ['propio', 'invitado'], true) ? $valor : 'todos';
    }

    public function filtrarFecha(string $valor): void
    {
        $this->fecha = in_array($valor, ['proximos', 'finalizados'], true) ? $valor : 'todos';
    }

    /**
     * Exámenes del docente (propios + invitados aceptados), ya mapeados y
     * filtrados por el buscador y los botones.
     *
     * @return list<array<string, mixed>>
     */
    public function examenes(): array
    {
        $idDocente = (int) auth()->id();

        $invitados = DB::table('invitacion_examen_compartido')
            ->where('id_docente_invitado', $idDocente)
            ->where('estado', InvitacionEstado::Aceptada->value)
            ->pluck('id_examen')
            ->map(fn (mixed $id): int => (int) $id)
            ->all();

        $examenes = Examen::query()
            ->with(['cursos', 'tipoExamen', 'ambientes'])
            ->where(function ($consulta) use ($idDocente, $invitados): void {
                $consulta->where('creador', $idDocente);
                if ($invitados !== []) {
                    $consulta->orWhereIn('id_examen', $invitados);
                }
            })
            ->orderByDesc('fecha')
            ->orderByDesc('hora_inicio')
            ->orderByDesc('id_examen')
            ->get();

        $ahora = Carbon::now();

        return $examenes
            ->map(fn (Examen $examen): array => $this->mapear($examen, $idDocente, $invitados, $ahora))
            ->filter(fn (array $examen): bool => $this->coincide($examen))
            ->values()
            ->all();
    }

    public function render(): View
    {
        return view('livewire.examenes.lista-examenes')->extends('layouts.app');
    }

    /**
     * @param  list<int>  $invitados
     * @return array<string, mixed>
     */
    private function mapear(Examen $examen, int $idDocente, array $invitados, Carbon $ahora): array
    {
        $inicio = Carbon::parse($examen->fecha.' '.$examen->hora_inicio);
        $fin = $examen->hora_fin
            ? Carbon::parse($examen->fecha.' '.$examen->hora_fin)
            : $inicio->copy()->addMinutes((int) $examen->duracion);

        $etiquetaTipo = TipoExamen::etiquetaDe($examen->tipoExamen?->nombre_tipo_examen);

        return [
            'id' => (int) $examen->id_examen,
            'nombre' => $examen->nombre_examen ?? $etiquetaTipo ?? 'Examen #'.$examen->id_examen,
            'tipo' => $etiquetaTipo ?? '—',
            'materias' => $examen->cursos->pluck('nombre_curso')->implode(', '),
            'fecha' => $examen->fecha,
            'hora_inicio' => substr((string) $examen->hora_inicio, 0, 5),
            'aulas' => $examen->ambientes->count(),
            'es_propio' => (int) $examen->creador === $idDocente,
            'finalizado' => $ahora->greaterThan($fin),
        ];
    }

    /**
     * @param  array<string, mixed>  $examen
     */
    private function coincide(array $examen): bool
    {
        $termino = trim(mb_strtolower($this->busqueda));
        if ($termino !== '') {
            $texto = mb_strtolower($examen['nombre'].' '.$examen['tipo'].' '.$examen['materias']);
            if (! str_contains($texto, $termino)) {
                return false;
            }
        }

        if ($this->propiedad === 'propio' && ! $examen['es_propio']) {
            return false;
        }
        if ($this->propiedad === 'invitado' && $examen['es_propio']) {
            return false;
        }

        if ($this->fecha === 'proximos' && $examen['finalizado']) {
            return false;
        }
        if ($this->fecha === 'finalizados' && ! $examen['finalizado']) {
            return false;
        }

        return true;
    }
}
