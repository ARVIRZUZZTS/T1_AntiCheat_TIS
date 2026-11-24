<?php

/**
 * @file    ListarExamenesCursoService.php
 *
 * @author  Alex Candia <alex.leonar.candia@gmail.com>
 *
 * @created 2026-10-05
 *
 * @updated 2026-10-09
 * @updated 2026-11-14
 * @updated 2026-11-16
 * @updated 2026-11-23
 * @updated 2026-11-24
 *
 * @description
 * Servicio de la feature Examen que lista los exámenes de un curso con la
 * información que la vista necesita para cada uno: tipo, fecha, ventana horaria,
 * duración, ambientes y cuántos estudiantes están inscritos o ingresaron.
 * El estado (programado / en
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
 * @see  resources/views/partials/examenes-curso.blade.php
 *
 * @changelog
 * - 2026-10-05  [Alex Candia]  feat: creación inicial del servicio.
 * - 2026-10-05  [Alex Candia]  fix: el nombre del tipo llegaba siempre null.
 *   El join con `tipo_examen` no sobrevivía al `withCount()` sobre la
 *   BelongsToMany (este fija el select y `get([...])` descarta sus columnas),
 *   así que el tipo ahora viene de la relación `tipoExamen()`.
 * - 2026-10-05  [Alex Candia]  feat: `tipo` sale ya con el nombre legible
 *   ("Examen parcial" en vez del código crudo de la base), resuelto por
 *   TipoExamen::etiquetaDe().
 * - 2026-10-10  [Alex Candia]  fix: el nombre legible cambia con el enum: la base
 *   ahora guarda `examen parcial`/`examen final`/`segunda instancia` en vez de
 *   los códigos PP/SP/FINAL/SI.
 * - 2026-10-09  [Diego Tejerina]  feat: ambientes e ingresos para el API #138;
 *   ordenar por fecha y hora descendentes, dejando los finalizados al final.
 * - 2026-11-14  [Alex Candia]  feat: el mapeo de exámenes se comparte con el
 *   listado global del docente e incluye los cursos asociados y su nombre
 *   concatenado para mostrar el curso junto al tipo.
 * - 2026-11-16  [OchoaCesar]  feat: el payload suma `incidencias` (conteo de
 *   central_riesgo del examen) para que la vista bloquee la eliminacion sin
 *   un viaje extra al servidor.
 * - 2026-11-23  [Valery D. Ortuno P]  fix: `curso` (la etiqueta del examen) lleva
 *   solo los cursos del docente que mira; en un examen compartido ya no salen
 *   los cursos del otro docente. `cursos` sigue trayendo todos.
 * - 2026-11-24  [Valery D. Ortuno P]  fix/perf: `listar()` trae los examenes en
 *   una sola consulta (antes cinco) y solo los que estan en un curso propio
 *   del docente (un invitado que acepto sin asociar curso veia el examen con
 *   los cursos de los otros docentes). Con un curso elegido, la etiqueta es
 *   ese curso.
 */

namespace App\Services\Examen;

use App\Enums\TipoExamen;
use App\Models\Ambiente;
use App\Models\Curso;
use App\Models\Examen;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

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
     * Exámenes por fecha y hora descendentes, con los finalizados al final.
     *
     * @param  int  $idCurso  ID del curso.
     * @param  Carbon|null  $ahora  Momento de referencia (para testing).
     * @return list<array{
     *     id: int,
     *     nombre: string,
     *     tipo: string|null,
     *     fecha: string|null,
     *     hora_inicio: string|null,
     *     hora_fin: string|null,
     *     duracion: int|null,
     *     inscritos: int,
     *     ingresados: int,
     *     incidencias: int,
     *     ambientes: list<array{id: int, nombre: string}>,
     *     estado: string
     * }> Exámenes listos para la vista. `tipo` ya viene con el nombre legible
     *             del tipo, no con el texto crudo de la base.
     *
     * @throws ModelNotFoundException Si el curso no existe.
     */
    public function ejecutar(int $idCurso, ?Carbon $ahora = null): array
    {
        $ahora ??= Carbon::now();
        $idDocente = (int) (auth()->id() ?? 0);

        $examenes = $this->listar($idDocente === 0 ? null : $idDocente, $idCurso, $ahora);

        // Sin examenes se confirma que el curso exista (404 si no); con
        // examenes ya se sabe que existe y se ahorra la consulta.
        if ($examenes === []) {
            Curso::findOrFail($idCurso);
        }

        return collect($examenes)
            ->sortBy([
                fn (array $primero, array $segundo): int => ($primero['estado'] === self::ESTADO_FINALIZADO)
                    <=> ($segundo['estado'] === self::ESTADO_FINALIZADO),
                ['fecha', 'desc'],
                ['hora_inicio', 'desc'],
                ['id', 'desc'],
            ])
            ->values()
            ->all();
    }

    /**
     * Examenes con todo lo que muestran las listas (tipo, cursos, ambientes,
     * inscritos, ingresos, incidencias y si el docente invito a otros) en UNA
     * sola consulta: cada viaje a la base remota pesa (~380 ms) y antes eran
     * cinco (examenes, tipos, ambientes, cursos e invitaciones).
     *
     * Con `$idDocente` solo salen los examenes que estan en un curso propio
     * del docente (sea anfitrion o invitado): un examen compartido aparece en
     * el curso de cada uno. Con `$idCurso`, solo los de ese curso, y la
     * etiqueta del examen es ese curso.
     *
     * @return list<array<string, mixed>> Sin ordenar.
     */
    public function listar(?int $idDocente, ?int $idCurso, Carbon $ahora): array
    {
        $agrupar = fn (string $expresion): string => DB::getDriverName() === 'pgsql'
            ? "string_agg({$expresion}, '§')"
            : "group_concat({$expresion}, '§')";

        $filas = DB::table('examen as e')
            ->leftJoin('tipo_examen as t', 't.id_tipo_examen', '=', 'e.tipo_examen')
            ->when($idDocente !== null || $idCurso !== null, fn ($consulta) => $consulta->whereExists(
                fn ($curso) => $curso->from('examen_curso as ec')
                    ->join('curso as c', 'c.id_curso', '=', 'ec.id_curso')
                    ->whereColumn('ec.id_examen', 'e.id_examen')
                    ->when($idDocente !== null, fn ($propio) => $propio->where('c.sis_doc', $idDocente))
                    ->when($idCurso !== null, fn ($elegido) => $elegido->where('ec.id_curso', $idCurso))
                    ->selectRaw('1')
            ))
            ->select('e.*', 't.nombre_tipo_examen')
            ->selectRaw('(select count(*) from estudiante_examen ee where ee.id_examen = e.id_examen) as estudiante_examenes_count')
            // Un ingreso duplicado no representa un estudiante adicional.
            ->selectRaw('(select count(distinct ra.id_estudiante) from registro_asistencia ra where ra.id_examen = e.id_examen) as ingresados')
            ->selectRaw('(select count(*) from central_riesgo cr where cr.id_examen = e.id_examen) as incidencias_count')
            ->selectRaw(
                'exists (select 1 from invitacion_examen_compartido i where i.id_examen = e.id_examen and i.id_docente_invitador = ?) as invito',
                [(int) ($idDocente ?? 0)],
            )
            ->selectRaw('(select '.$agrupar("c.id_curso || '|' || coalesce(c.sis_doc, 0) || '|' || c.nombre_curso").' from examen_curso ec join curso c on c.id_curso = ec.id_curso where ec.id_examen = e.id_examen) as cursos_texto')
            ->selectRaw('(select '.$agrupar("a.id_ambiente || '|' || a.nombre_ambiente").' from examen_ambiente ea join ambiente a on a.id_ambiente = ea.id_ambiente where ea.id_examen = e.id_examen) as ambientes_texto')
            ->get();

        $invitados = [];
        $examenes = [];

        foreach ($filas as $fila) {
            $datos = (array) $fila;
            if ((bool) $datos['invito']) {
                $invitados[] = (int) $datos['id_examen'];
            }

            $examen = (new Examen)->newFromBuilder(array_diff_key($datos, array_flip(['nombre_tipo_examen', 'invito', 'cursos_texto', 'ambientes_texto'])));
            $examen->setRelation('tipoExamen', $datos['nombre_tipo_examen'] === null ? null
                : (new \App\Models\TipoExamen)->newFromBuilder(['id_tipo_examen' => $datos['tipo_examen'], 'nombre_tipo_examen' => $datos['nombre_tipo_examen']]));
            $examen->setRelation('cursos', (new Curso)->newCollection(array_map(
                fn (array $partes): Curso => (new Curso)->newFromBuilder(['id_curso' => (int) $partes[0], 'sis_doc' => (int) $partes[1], 'nombre_curso' => $partes[2]]),
                $this->partes($datos['cursos_texto'], 3),
            )));
            $examen->setRelation('ambientes', (new Ambiente)->newCollection(array_map(
                fn (array $partes): Ambiente => (new Ambiente)->newFromBuilder(['id_ambiente' => (int) $partes[0], 'nombre_ambiente' => $partes[1]]),
                $this->partes($datos['ambientes_texto'], 2),
            )));
            $examenes[] = $examen;
        }

        return array_map(fn (Examen $examen): array => $this->mapearExamen($examen, $ahora, $invitados, $idCurso), $examenes);
    }

    /**
     * Separa el texto agrupado ("id|...§id|...") en sus filas.
     *
     * @return list<list<string>>
     */
    private function partes(?string $texto, int $campos): array
    {
        if ($texto === null || $texto === '') {
            return [];
        }

        return array_map(fn (string $fila): array => explode('|', $fila, $campos), explode('§', $texto));
    }

    /**
     * Convierte un examen en el arreglo de datos que consume la vista.
     *
     * @param  Examen  $examen  Examen con el tipo, los cursos y el conteo de
     *                          inscritos ya cargados por la consulta.
     * @param  Carbon  $ahora  Momento de referencia para el estado.
     * @param  list<int>  $examenesInvitados  Exámenes donde el docente actual es
     *                                        el invitador.
     * @return array<string, mixed>
     */
    public function mapearExamen(Examen $examen, Carbon $ahora, array $examenesInvitados = [], ?int $idCursoContexto = null): array
    {
        $tipo = TipoExamen::etiquetaDe($examen->tipoExamen?->nombre_tipo_examen);
        $nombre = $examen->nombre_examen ?? $tipo ?? 'Examen #'.$examen->id_examen;
        $cursos = $examen->relationLoaded('cursos')
            ? $examen->cursos
                ->sortBy('id_curso')
                ->map(fn (Curso $curso): array => [
                    'id' => (int) $curso->id_curso,
                    'nombre' => $curso->nombre_curso,
                ])
                ->values()
                ->all()
            : [];

        return [
            'id' => $examen->id_examen,
            'es_propio' => (int) $examen->creador === (int) (auth()->id() ?? 0),
            'es_anfitrion' => in_array((int) $examen->id_examen, $examenesInvitados, true),
            'nombre' => $nombre,
            // Compatibilidad con las pantallas actuales, que todavia leen
            // `tipo`: para los examenes de HU-16 se muestra su nombre.
            'tipo' => $examen->nombre_examen ?? $tipo,
            'fecha' => $examen->fecha,
            'hora_inicio' => $this->recortarHora($examen->hora_inicio),
            'hora_fin' => $this->recortarHora($examen->hora_fin),
            'duracion' => $examen->duracion,
            'cursos' => $cursos,
            // Etiqueta del examen para quien mira: el curso elegido en el
            // selector, o solo sus propios cursos (en un examen compartido el
            // otro docente tiene los suyos). Sin sesion, todos los del examen.
            'curso' => $this->cursosPropios($examen, $idCursoContexto) ?? implode(', ', array_column($cursos, 'nombre')),
            'inscritos' => (int) $examen->getAttribute('estudiante_examenes_count'),
            'ingresados' => (int) $examen->getAttribute('ingresados'),
            'incidencias' => (int) $examen->getAttribute('incidencias_count'),
            'ambientes' => $examen->ambientes
                ->sortBy('id_ambiente')
                ->map(fn (Ambiente $ambiente): array => [
                    'id' => $ambiente->id_ambiente,
                    'nombre' => $ambiente->nombre_ambiente,
                ])
                ->values()
                ->all(),
            'estado' => $this->resolverEstado($examen, $ahora),
        ];
    }

    /**
     * Nombres de los cursos del examen que son del docente autenticado, o
     * null si no hay sesion o ninguno es suyo.
     */
    private function cursosPropios(Examen $examen, ?int $idCursoContexto = null): ?string
    {
        if ($idCursoContexto !== null && $examen->relationLoaded('cursos')) {
            $elegido = $examen->cursos->firstWhere('id_curso', $idCursoContexto);
            if ($elegido !== null) {
                return $elegido->nombre_curso;
            }
        }

        $idDocente = (int) (auth()->id() ?? 0);

        if ($idDocente === 0 || ! $examen->relationLoaded('cursos')) {
            return null;
        }

        $propios = $examen->cursos
            ->filter(fn (Curso $curso): bool => (int) $curso->sis_doc === $idDocente)
            ->sortBy('nombre_curso')
            ->pluck('nombre_curso');

        return $propios->isEmpty() ? null : $propios->implode(', ');
    }

    /**
     * Estado del examen según su ventana horaria (fecha + hora_inicio y la
     * duración). La hora de fin se resuelve acá sumando la duración, así que un
     * examen que cruza la medianoche termina al día siguiente y no se adelanta.
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
        $fin = $examen->duracion === null
            ? Carbon::parse($examen->fecha.' '.self::HORA_FIN_POR_DEFECTO)
            : $inicio->copy()->addMinutes((int) $examen->duracion);

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
