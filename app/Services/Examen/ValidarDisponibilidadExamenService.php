<?php

namespace App\Services\Examen;

use App\Models\Examen;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Valida que la ventana horaria de un examen no se cruce con otra reserva del
 * mismo ambiente, curso o docente. La hora final siempre se deriva de la
 * duracion, igual que en Examen::getHoraFinAttribute().
 */
class ValidarDisponibilidadExamenService
{
    /**
     * Serializa altas y ediciones para que la comprobacion de disponibilidad y
     * su escritura no puedan intercalarse entre dos solicitudes concurrentes.
     */
    public function bloquearProgramacion(): void
    {
        DB::statement('LOCK TABLE examen IN SHARE ROW EXCLUSIVE MODE');
    }

    /**
     * @param  list<int>  $ambientes
     * @param  list<int>  $cursos
     *
     * @throws ValidationException
     */
    public function asegurarDisponible(
        Carbon $inicio,
        int $duracion,
        array $ambientes,
        array $cursos,
        int $idDocente,
        ?int $idExamenExcluido = null,
    ): void {
        $errores = $this->errores(
            $inicio,
            $duracion,
            $ambientes,
            $cursos,
            $idDocente,
            $idExamenExcluido,
        );

        if ($errores !== []) {
            throw ValidationException::withMessages($errores);
        }
    }

    /**
     * @param  list<int>  $ambientes
     * @param  list<int>  $cursos
     * @return array<string, list<string>>
     */
    public function errores(
        Carbon $inicio,
        int $duracion,
        array $ambientes,
        array $cursos,
        int $idDocente,
        ?int $idExamenExcluido = null,
    ): array {
        $ambientes = $this->idsUnicos($ambientes);
        $cursos = $this->idsUnicos($cursos);
        $fin = $inicio->copy()->addMinutes($duracion);

        $candidatos = Examen::query()
            ->whereNotNull('fecha')
            ->whereNotNull('hora_inicio')
            ->whereNotNull('duracion')
            // Un examen que cruza la medianoche puede comenzar el dia previo.
            ->where('fecha', '<=', $fin->toDateString())
            ->when(
                $idExamenExcluido !== null,
                fn (Builder $consulta): Builder => $consulta->where('id_examen', '!=', $idExamenExcluido),
            )
            ->where(function (Builder $consulta) use ($ambientes, $cursos, $idDocente): void {
                $tieneCondicion = false;

                if ($ambientes !== []) {
                    $consulta->whereHas(
                        'ambientes',
                        fn (Builder $ambientesExamen): Builder => $ambientesExamen
                            ->whereIn('ambiente.id_ambiente', $ambientes),
                    );
                    $tieneCondicion = true;
                }

                if ($cursos !== []) {
                    $metodo = $tieneCondicion ? 'orWhereHas' : 'whereHas';
                    $consulta->{$metodo}(
                        'cursos',
                        fn (Builder $cursosExamen): Builder => $cursosExamen
                            ->whereIn('curso.id_curso', $cursos),
                    );
                    $tieneCondicion = true;
                }

                if ($idDocente > 0) {
                    $metodo = $tieneCondicion ? 'orWhere' : 'where';
                    $consulta->{$metodo}('creador', $idDocente);
                }
            })
            ->with([
                'ambientes:id_ambiente,nombre_ambiente',
                'cursos:id_curso',
            ])
            ->get()
            ->filter(fn (Examen $examen): bool => $this->seSuperpone($inicio, $fin, $examen));

        $ambientesOcupados = [];
        $cursoOcupado = false;
        $docenteOcupado = false;

        foreach ($candidatos as $examen) {
            $ambientesOcupados = array_merge(
                $ambientesOcupados,
                $examen->ambientes
                    ->filter(fn ($ambiente): bool => in_array((int) $ambiente->id_ambiente, $ambientes, true))
                    ->pluck('nombre_ambiente')
                    ->all(),
            );

            $cursoOcupado = $cursoOcupado || $examen->cursos
                ->pluck('id_curso')
                ->map(fn ($id): int => (int) $id)
                ->intersect($cursos)
                ->isNotEmpty();

            $docenteOcupado = $docenteOcupado || (int) $examen->creador === $idDocente;
        }

        $errores = [];
        if ($ambientesOcupados !== []) {
            $nombres = implode(', ', array_values(array_unique($ambientesOcupados)));
            $errores['ambientes'] = [
                'El ambiente '.$nombres.' ya tiene un examen programado en ese horario.',
            ];
        }

        if ($cursoOcupado) {
            $errores['hora_inicio'] = ['Este curso ya tiene un examen programado en ese horario.'];
        } elseif ($docenteOcupado) {
            $errores['hora_inicio'] = ['El docente ya tiene un examen programado en ese horario.'];
        }

        return $errores;
    }

    /** @param list<int> $ids @return list<int> */
    private function idsUnicos(array $ids): array
    {
        return array_values(array_unique(array_map(fn ($id): int => (int) $id, $ids)));
    }

    private function seSuperpone(Carbon $inicio, Carbon $fin, Examen $examen): bool
    {
        try {
            $inicioExistente = Carbon::parse($examen->fecha.' '.$examen->hora_inicio);
            $finExistente = $inicioExistente->copy()->addMinutes((int) $examen->duracion);
        } catch (\Throwable) {
            // Los examenes historicos incompletos no representan una reserva util.
            return false;
        }

        // Intervalos semiabiertos: uno puede comenzar exactamente al terminar otro.
        return $inicio->lt($finExistente) && $fin->gt($inicioExistente);
    }
}
