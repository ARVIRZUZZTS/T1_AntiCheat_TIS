<?php

/**
 * @file    ListarExamenesCursoService.php
 *
 * @author  Alex Candia <alex.leonar.candia@gmail.com>
 *
 * @created 2026-10-05
 *
 * @updated 2026-10-05
 *
 * @description
 * Servicio de la feature Examen que lista los exámenes de un curso con la
 * información que la vista necesita para cada uno: tipo, fecha, ventana horaria,
 * duración y cuántos estudiantes están inscritos. El estado (programado / en
 * curso / finalizado) se resuelve comparando la ventana horaria del examen con
 * el momento actual, igual que hace GenerarReporteAsistenciaService para la
 * asistencia: la regla vive acá y no en la vista.
 *
 * Es de solo lectura: no crea ni modifica exámenes.
 *
 * @see  App\Services\Monitoreo\GenerarReporteAsistenciaService
 * @see  App\Enums\TipoExamen
 * @see  App\Models\Curso
 * @see  App\Models\TipoExamen
 * @see  resources/views/partials/materia-examenes.blade.php
 *
 * @changelog
 * - 2026-10-05  [Alex Candia]  feat: creación inicial del servicio.
 * - 2026-10-05  [Alex Candia]  fix: el nombre del tipo llegaba siempre null.
 *   El join con `tipo_examen` no sobrevivía al `withCount()` sobre la
 *   BelongsToMany (este fija el select y `get([...])` descarta sus columnas),
 *   así que el tipo ahora viene de la relación `tipoExamen()`.
 * - 2026-10-05  [Alex Candia]  feat: `tipo` sale ya con el nombre legible
 *   ("Primer parcial" en vez de "PP"), resuelto por TipoExamen::etiquetaDe().
 */

namespace App\Services\Examen;

use App\Enums\TipoExamen;
use App\Models\Curso;
use App\Models\Examen;
use Carbon\Carbon;

class ListarExamenesCursoService
{
    public const ESTADO_PROGRAMADO = 'programado';

    public const ESTADO_EN_CURSO = 'en_curso';

    public const ESTADO_FINALIZADO = 'finalizado';

    /**
     * Horarios con los que se cierra la ventana de un examen que no los tiene
     * informedos, para que la comparación con el momento actual nunca falle.
     */
    private const HORA_INICIO_POR_DEFECTO = '00:00:00';

    private const HORA_FIN_POR_DEFECTO = '23:59:59';

    /**
     * Exámenes del curso, del más reciente al más antiguo.
     *
     * @param  int  $idCurso  ID del curso.
     * @param  Carbon|null  $ahora  Momento de referencia (para testing).
     * @return list<array{
     *     id: int,
     *     tipo: string|null,
     *     fecha: string|null,
     *     hora_inicio: string|null,
     *     hora_fin: string|null,
     *     duracion: int|null,
     *     inscritos: int,
     *     estado: string
     * }> Exámenes listos para la vista. `tipo` ya viene con el nombre legible
     *             del tipo, no con el código de la base.
     */
    public function ejecutar(int $idCurso, ?Carbon $ahora = null): array
    {
        $curso = Curso::findOrFail($idCurso);
        $ahora ??= Carbon::now();

        return $curso->examenes()
            ->with('tipoExamen')
            ->withCount('estudianteExamenes')
            ->orderByDesc('examen.fecha')
            ->orderByDesc('examen.id_examen')
            ->get()
            ->map(fn (Examen $examen): array => $this->mapearExamen($examen, $ahora))
            ->all();
    }

    /**
     * Convierte un examen en el arreglo de datos que consume la vista.
     *
     * @param  Examen  $examen  Examen con el tipo y el conteo de inscritos ya
     *                           cargados por la consulta.
     * @param  Carbon  $ahora  Momento de referencia para el estado.
     * @return array<string, mixed>
     */
    private function mapearExamen(Examen $examen, Carbon $ahora): array
    {
        return [
            'id' => $examen->id_examen,
            'tipo' => TipoExamen::etiquetaDe($examen->tipoExamen?->nombre_tipo_examen),
            'fecha' => $examen->fecha,
            'hora_inicio' => $this->recortarHora($examen->hora_inicio),
            'hora_fin' => $this->recortarHora($examen->hora_fin),
            'duracion' => $examen->duracion,
            'inscritos' => (int) $examen->getAttribute('estudiante_examenes_count'),
            'estado' => $this->resolverEstado($examen, $ahora),
        ];
    }

    /**
     * Estado del examen según su ventana horaria (fecha + hora_inicio/hora_fin).
     *
     * @param  Examen  $examen  Examen a clasificar.
     * @param  Carbon  $ahora  Momento de referencia.
     * @return string ESTADO_PROGRAMADO, ESTADO_EN_CURSO o ESTADO_FINALIZADO.
     */
    private function resolverEstado(Examen $examen, Carbon $ahora): string
    {
        if ($examen->fecha === null) {
            return self::ESTADO_PROGRAMADO;
        }

        $inicio = Carbon::parse($examen->fecha.' '.($examen->hora_inicio ?? self::HORA_INICIO_POR_DEFECTO));
        $fin = Carbon::parse($examen->fecha.' '.($examen->hora_fin ?? self::HORA_FIN_POR_DEFECTO));

        if ($ahora->gt($fin)) {
            return self::ESTADO_FINALIZADO;
        }

        return $ahora->gte($inicio) ? self::ESTADO_EN_CURSO : self::ESTADO_PROGRAMADO;
    }

    /**
     * Deja la hora en formato H:i (la base la guarda como H:i:s).
     *
     * @param  ?string  $hora  Hora de la base.
     * @return ?string Hora sin segundos, o null si el examen no la tiene.
     */
    private function recortarHora(?string $hora): ?string
    {
        return $hora === null ? null : substr($hora, 0, 5);
    }
}
