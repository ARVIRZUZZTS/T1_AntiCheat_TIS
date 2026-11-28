<?php

/**
 * @file    ListarFiltrosReporteService.php
 *
 * @author  Alex Candia <alex.leonar.candia@gmail.com>
 *
 * @created 2026-10-21
 *
 * @updated 2026-11-27
 *
 * @description
 * Arma los datos de los dos selectores de la pantalla de reportes: la lista de
 * materias (id_curso => "Curso — Gestion") y los examenes de cada curso
 * (id_curso => [id, etiqueta]). La etiqueta del examen ya viene legible
 * ("Examen final — 20/10/2026"), resuelta con el enum de tipo y la fecha.
 *
 * Solo devuelve las materias y examenes que le corresponden al usuario:
 * - Docente: los cursos donde curso.sis_doc es su id_usuario.
 * - Auxiliar: los cursos asignados en auxiliar_curso.
 * - Con ambos roles: la union de los dos conjuntos.
 * - Sin ninguno de esos roles: nada.
 * - Si se pasa solo el id de docente (compatibilidad con rutas antiguas),
 *   se filtran por ese docente.
 *
 * Ademas, segun el reporte pedido:
 * - Asistencia y horario de ingreso: solo examenes finalizados que tengan
 *   al menos un registro de asistencia.
 * - Intentos no autorizados: solo examenes con filas en central_riesgo para
 *   el curso seleccionado (o con id_curso null, que el reporte muestra en
 *   todos los cursos del examen).
 *
 * Todo se resuelve en pocas consultas agrupadas (una por roles, una por
 * materias, una por examenes): nunca una consulta por curso, aunque la lista de
 * materias crezca.
 *
 * @see  App\Services\Curso\ListarCursosDocenteService
 * @see  App\Services\Curso\ListarMateriasConMetricasService
 * @see  App\Enums\TipoExamen
 * @see  App\Models\Rol
 * @see  resources/views/pages/reportes.blade.php
 *
 * @changelog
 * - 2026-10-21  [Alex Candia]  feat: creacion inicial del servicio.
 * - 2026-10-21  [Alex Candia]  feat: filtrar materias y examenes por el usuario
 *   autenticado (cursos del docente y cursos asignados al auxiliar).
 * - 2026-10-23  [Alex Candia]  fix: compatibilidad con llamada por id_docente
 *   y asegurar retorno array.
 * - 2026-10-26  [Alex Candia]  feat: cada examen incluye tipo y fecha formateada
 *   para que los tres reportes compartan el mismo titulo/subtitulo.
 * - 2026-11-27  [Alex Candia]  fix: los selectores muestran solo cursos/examenes
 *   con datos del reporte y el curso incluye su gestion.
 */

namespace App\Services\Reporte;

use App\Enums\TipoExamen;
use App\Models\Curso;
use App\Models\Rol;
use App\Models\Usuario;
use App\Services\Curso\ListarCursosDocenteService;
use App\Services\Curso\ListarMateriasConMetricasService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ListarFiltrosReporteService
{
    public const REPORTE_ASISTENCIA = 'asistencia';

    public const REPORTE_HORARIOS = 'horarios';

    public const REPORTE_INGRESOS = 'ingresos';

    private const HORA_INICIO_POR_DEFECTO = '00:00:00';

    private const HORA_FIN_POR_DEFECTO = '23:59:59';

    public function __construct(
        private readonly ListarMateriasConMetricasService $listarMaterias,
        private readonly ListarCursosDocenteService $listarCursosDocente,
    ) {}

    /**
     * Datos listos para los selectores de la vista de reportes.
     *
     * @return array{
     *     opcionesMaterias: array<int, string>,
     *     examenesPorCurso: array<int, list<array{
     *         id: int,
     *         etiqueta: string,
     *         tipo: string,
     *         fecha: ?string,
     *         fecha_formateada: ?string
     *     }>>
     * }
     */
    public function ejecutar(Usuario|int|null $usuario = null, string $reporte = self::REPORTE_ASISTENCIA): array
    {
        if (is_int($usuario)) {
            $usuario = Usuario::query()->find($usuario);
        }

        $roles = $usuario === null
            ? collect()
            : $usuario->roles()->pluck('nombre_rol');
        $idsCursos = $usuario === null
            ? collect()
            : $this->idsCursosDelUsuario($usuario, $roles);

        $examenesPorCurso = $this->examenesPorCurso(
            $idsCursos,
            ($usuario instanceof Usuario && $roles->contains(Rol::NOMBRE_DOCENTE)) ? $usuario : null,
            $reporte,
        );
        $idsCursosConReporte = $idsCursos
            ->filter(fn (int $idCurso): bool => isset($examenesPorCurso[$idCurso]))
            ->values();

        return [
            'opcionesMaterias' => $this->opcionesMaterias(
                $idsCursosConReporte,
                $this->listarCursosDocente->gestionesPorCurso($idsCursosConReporte->all()),
            ),
            'examenesPorCurso' => $examenesPorCurso,
        ];
    }

    /**
     * IDs de los cursos que le corresponden al usuario segun sus roles.
     *
     * @param  Collection<int, string>  $roles
     * @return Collection<int, int>
     */
    private function idsCursosDelUsuario(Usuario $usuario, Collection $roles): Collection
    {
        $ids = collect();

        if ($roles->contains(Rol::NOMBRE_DOCENTE)) {
            $ids = $ids->merge(
                Curso::query()
                    ->where('sis_doc', $usuario->id_usuario)
                    ->pluck('id_curso')
            );
        }

        if ($roles->contains(Rol::NOMBRE_AUXILIAR)) {
            $ids = $ids->merge(
                DB::table('auxiliar_curso')
                    ->where('id_auxiliar', $usuario->id_usuario)
                    ->pluck('id_curso')
            );
        }

        return $ids->map(fn ($id): int => (int) $id)->unique()->values();
    }

    /**
     * Materias en el formato que espera x-ui.select (id => "Curso — Gestion").
     *
     * @param  Collection<int, int>  $idsCursos
     * @param  Collection<int, non-falsy-string|null>  $gestiones
     * @return array<int, string>
     */
    private function opcionesMaterias(Collection $idsCursos, Collection $gestiones): array
    {
        if ($idsCursos->isEmpty()) {
            return [];
        }

        $todas = $this->listarMaterias->ejecutar($idsCursos->all());

        return $todas
            ->mapWithKeys(function (array $fila) use ($gestiones): array {
                $idCurso = (int) $fila['curso']->id_curso;
                $gestion = $gestiones->get($idCurso);
                $etiqueta = $fila['curso']->nombre_curso.($gestion !== null ? ' — '.$gestion : '');

                return [$idCurso => $etiqueta];
            })
            ->all();
    }

    /**
     * Examenes de los cursos del usuario en una sola consulta, agrupados por curso.
     *
     * @param  Collection<int, int>  $idsCursos
     * @param  string  $reporte  Uno de los valores REPORTE_*; define el filtro de datos.
     * @return array<int, list<array{
     *     id: int,
     *     etiqueta: string,
     *     tipo: string,
     *     fecha: ?string,
     *     fecha_formateada: ?string
     * }>>
     */
    private function examenesPorCurso(Collection $idsCursos, ?Usuario $docente, string $reporte): array
    {
        if ($idsCursos->isEmpty()) {
            return [];
        }

        $consulta = DB::table('examen')
            ->join('examen_curso', 'examen_curso.id_examen', '=', 'examen.id_examen')
            ->leftJoin('tipo_examen', 'tipo_examen.id_tipo_examen', '=', 'examen.tipo_examen')
            ->whereIn('examen_curso.id_curso', $idsCursos->all())
            ->when($docente !== null, fn ($query) => $query->where(
                fn ($acceso) => $acceso
                    ->where('examen.creador', $docente->id_usuario)
                    ->orWhereExists(fn ($invitacion) => $invitacion
                        ->select(DB::raw('1'))
                        ->from('invitacion_examen_compartido')
                        ->whereColumn('invitacion_examen_compartido.id_examen', 'examen.id_examen')
                        ->where('invitacion_examen_compartido.id_docente_invitado', $docente->id_usuario)
                        ->where('invitacion_examen_compartido.estado', 'aceptado'))
            ))
            ->when(
                $reporte === self::REPORTE_ASISTENCIA || $reporte === self::REPORTE_HORARIOS,
                fn ($query) => $query
                    ->whereExists(fn ($ingreso) => $ingreso
                        ->select(DB::raw('1'))
                        ->from('registro_asistencia as ingreso')
                        ->whereColumn('ingreso.id_examen', 'examen.id_examen'))
            )
            ->when(
                $reporte === self::REPORTE_INGRESOS,
                fn ($query) => $query->whereExists(fn ($riesgo) => $riesgo
                    ->select(DB::raw('1'))
                    ->from('central_riesgo as riesgo')
                    ->whereColumn('riesgo.id_examen', 'examen.id_examen')
                    ->where(fn ($curso) => $curso
                        ->whereColumn('riesgo.id_curso', 'examen_curso.id_curso')
                        ->orWhereNull('riesgo.id_curso')))
            )
            ->select(
                'examen_curso.id_curso',
                'examen.id_examen',
                'examen.fecha',
                'examen.hora_inicio',
                'examen.duracion',
                'tipo_examen.nombre_tipo_examen',
            );

        $examenes = $consulta->orderByDesc('examen.fecha')->get();

        if ($reporte === self::REPORTE_ASISTENCIA || $reporte === self::REPORTE_HORARIOS) {
            $ahora = Carbon::now();
            $examenes = $examenes->filter(fn (object $examen): bool => $this->examenFinalizado($examen, $ahora));
        }

        return $examenes
            ->groupBy('id_curso')
            ->map(fn ($examenesDelCurso): array => $examenesDelCurso
                ->map(fn (object $examen): array => [
                    'id' => (int) $examen->id_examen,
                    'etiqueta' => $this->etiquetaExamen($examen),
                    'tipo' => $this->tipoExamen($examen),
                    'fecha' => $examen->fecha,
                    'fecha_formateada' => $this->fechaFormateada($examen),
                ])
                ->values()
                ->all())
            ->all();
    }

    /**
     * Mismo criterio que ListarExamenesCursoService::resolverEstado(): la fecha
     * mas la hora de inicio y la duracion, con 23:59:59 si no hay duracion.
     *
     * @param  object{fecha: ?string, hora_inicio: ?string, duracion: int|string|null}  $examen
     */
    private function examenFinalizado(object $examen, Carbon $ahora): bool
    {
        if ($examen->fecha === null) {
            return false;
        }

        $inicio = Carbon::parse($examen->fecha.' '.($examen->hora_inicio ?? self::HORA_INICIO_POR_DEFECTO));
        $fin = $examen->duracion === null
            ? Carbon::parse($examen->fecha.' '.self::HORA_FIN_POR_DEFECTO)
            : $inicio->copy()->addMinutes((int) $examen->duracion);

        return $ahora->gt($fin);
    }

    /**
     * Etiqueta legible del examen.
     *
     * @param  object{nombre_tipo_examen: ?string, fecha: ?string}  $examen
     */
    private function etiquetaExamen(object $examen): string
    {
        return $this->tipoExamen($examen).' — '.($this->fechaFormateada($examen) ?? 'Sin fecha');
    }

    /**
     * Tipo de examen legible para el subtítulo de los reportes.
     *
     * @param  object{nombre_tipo_examen: ?string}  $examen
     */
    private function tipoExamen(object $examen): string
    {
        return TipoExamen::etiquetaDe($examen->nombre_tipo_examen) ?? 'Sin tipo';
    }

    /**
     * Fecha del examen en dd/mm/aaaa, o null si no tiene fecha.
     *
     * @param  object{fecha: ?string}  $examen
     */
    private function fechaFormateada(object $examen): ?string
    {
        return $examen->fecha === null
            ? null
            : Carbon::parse($examen->fecha)->format('d/m/Y');
    }
}
