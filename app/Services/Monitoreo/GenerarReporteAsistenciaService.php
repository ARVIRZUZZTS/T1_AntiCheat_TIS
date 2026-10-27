<?php

/**
 * @file    GenerarReporteAsistenciaService.php
 *
 * @author  OchoaCesar <cesareduardonick@gmail.com>
 *
 * @created 2026-09-25
 *
 * @updated 2026-10-11
 * @updated 2026-10-26
 *
 * @description
 * Servicio de dominio que genera el reporte de asistencia de un examen.
 * Dado el id de un examen, resuelve el estado de cada estudiante inscrito
 * (`presente` / `rechazado` / `riesgo` / `pendiente`), la incidencia vigente
 * de la central de riesgos, el aula, el docente a cargo, el registrador y
 * hora de ingreso.
 *
 * El estado sale del dato persistido y no de un reloj, para que el monitor
 * muestre lo mismo sin importar cuándo se abra:
 *
 *  - `rechazado`  el estudiante está deshabilitado para el examen. Un rechazo
 *                 no crea fila de asistencia (`RegistroIngresoService`), así
 *                 que la inhabilitación es la única marca que queda.
 *  - `riesgo`     tiene una incidencia en `central_riesgo` para este examen.
 *                 La vista decide si la pinta como tramposo o como sospechoso.
 *  - `presente`   tiene fila en `registro_asistencia`.
 *  - `pendiente`  ninguno de los casos anteriores.
 *
 * El examen se resuelve con sus relaciones en la misma consulta porque la vista
 * del monitor lee cursos, ambientes, materiales permitidos y el tipo: cargarlas
 * por separado eran cuatro viajes más a la base por página, que con la latencia
 * del servidor remoto costaban ~1s.
 *
 * @changelog
 * - 2026-09-25  [OchoaCesar]  feat:  creación inicial del servicio con estados
 *                                     presente/ausente/pendiente, registrador y
 *                                     observaciones.
 * - 2026-09-25  [OchoaCesar]  fix:   el estado deshabilitado dejó de forzar
 *                                     'rechazado' y sigue la lógica temporal;
 *                                     el ingreso rechazado se marca como 'ausente'.
 * - 2026-09-25  [OchoaCesar]  feat:  se agregó hora_ingreso a la respuesta
 *                                     (solo cuando el estado es 'presente').
 * - 2026-10-10  [T1]  fix: devuelve `incidencia` y los estados `rechazado` y
 *   `riesgo`, que `MonitorEnVivo` y su vista ya consumían pero el servicio
 *   nunca entregaba: el monitor solo podía mostrar "Ingresó" o "Pendiente", y
 *   los modales de no habilitado y de central de riesgos no se alcanzaban.
 *   También se deja de comparar contra la hora de inicio: si el registrador
 *   anotó el ingreso, el estudiante entró, aunque se consulte antes de la hora.
 * - 2026-10-10  [T1]  perf: el examen se resuelve con `with(['cursos',
 *   'ambientes', 'materialesPermitidos', 'tipoExamen'])`, y `tipoExamen` para el
 *   subtítulo del header. Cuatro viajes menos a la base por página.
 * - 2026-10-10  [Alex Candia]  refactor: la columna `hora_fin` se quitó de
 *   `examen` y pasó a derivarse. En este servicio esa derivada quedó sin uso
 *   cuando el estado pasó a calcularse desde el dato persistido, así que se
 *   retira de acá; la ventana horaria la resuelve `Examen::getHoraFinAttribute()`.
 * - 2026-10-11  [OchoaCesar]  fix: el estado de la incidencia se lee como enum
 *   (`EstadoIncidencia`) porque el modelo volvió a castear `estado_incidencia`;
 *   el `(string)` anterior sobre el enum lanzaba "Object of class could not be
 *   converted to string" en `incidenciasPorEstudiante()`.
 * - 2026-10-26  [Valery D. Ortuno P]  refactor: el estado sale de
 *   TipoInfraccion::estado(); `estado_incidencia` y el enum
 *   EstadoIncidencia se eliminaron.
 * - 2026-10-26  [OchoaCesar]  feat: se agrega `aula` al reporte, resuelta
 *   desde `estudiante_examen_ambiente` con fallback a los ambientes del
 *   examen: el reporte de asistencia (pantalla y Excel) mostraba "Sin aula"
 *   porque el campo nunca existió en este servicio.
 * - 2026-10-26  [OchoaCesar]  feat: se agrega `docente_cargo` al reporte, el
 *   docente real de la configuración del estudiante (con el creador del
 *   examen como respaldo). El resumen lo usaba desde el registrador y salía
 *   "Sin registro" para todo ingreso aún no confirmado en el monitor.
 *
 * @see  \App\Livewire\Monitoreo\MonitorEnVivo
 * @see  App\Services\Asistencia\RegistroIngresoService
 * @see  EstudianteExamen
 * @see  RegistroAsistencia
 * @see  CentralRiesgo
 * @see  Examen
 */

namespace App\Services\Monitoreo;

use App\Enums\EstadoEstudianteExamen;
use App\Enums\Motivo;
use App\Models\CentralRiesgo;
use App\Models\ConfiguracionExamenDocente;
use App\Models\EstudianteExamen;
use App\Models\Examen;
use App\Models\RegistroAsistencia;
use App\Services\Asistencia\RegistroIngresoService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * @author  OchoaCesar <cesareduardonick@gmail.com>
 *
 * @since   2026-09-25
 */
class GenerarReporteAsistenciaService
{
    /**
     * Genera el reporte de asistencia de un examen.
     *
     * @param  int  $idExamen  ID del examen
     * @param  Carbon|null  $ahora  Se conserva por compatibilidad con la firma
     *                              original; el estado ya no depende de la hora, sino de lo persistido.
     * @return array{examen: Examen, estudiantes: Collection}
     */
    public function ejecutar(int $idExamen, ?Carbon $ahora = null, ?int $idDocente = null): array
    {
        $examen = Examen::query()
            ->with([
                'cursos' => fn ($query) => $query->when(
                    $idDocente !== null,
                    fn ($cursos) => $cursos->where('curso.sis_doc', $idDocente)
                ),
                'ambientes',
                'materialesPermitidos',
                'normas',
                'materialesPersonalizados',
                'normasPersonalizadas',
                'tipoExamen',
            ])
            ->findOrFail($idExamen);

        $configuracion = $this->configuracion($examen, $idDocente);
        if ($configuracion !== null) {
            $examen->setRelation('materialesPermitidos', $configuracion->materialesPermitidos);
            $examen->setRelation('normas', $configuracion->normas);
            $examen->setRelation('materialesPersonalizados', $configuracion->materialesPersonalizados);
            $examen->setRelation('normasPersonalizadas', $configuracion->normasPersonalizadas);
        }

        $registros = RegistroAsistencia::query()
            ->where('id_examen', $examen->id_examen)
            ->when($idDocente !== null, fn ($query) => $query->delDocente($idDocente))
            ->with('registrador')
            ->get()
            ->keyBy(fn (RegistroAsistencia $registro): string => $this->clave(
                $registro->id_estudiante_examen,
                $registro->id_estudiante,
            ));

        $incidencias = $this->incidenciasPorEstudiante($examen->id_examen, $idDocente);

        // El aula asignada sale de la puente `estudiante_examen_ambiente` (una
        // inscripción puede tener varias, que quedan unidas por coma). Sin
        // asignación se cae a los ambientes del examen y, si el examen no
        // tiene ninguno, a "Sin aula".
        $aulasPorInscripcion = $this->aulasPorInscripcion($examen->id_examen);
        $aulasDelExamen = $examen->ambientes->pluck('nombre_ambiente')->unique()->implode(', ');

        // Docente real del estudiante: el de su configuración de examen. Si la
        // inscripción no tiene (datos antiguos) se cae al creador del examen.
        $docentesPorInscripcion = $this->docentesPorInscripcion($examen->id_examen);
        $creador = DB::table('usuario')->where('id_usuario', $examen->creador)->first();
        $creadorDelExamen = $creador !== null
            ? trim($creador->nombre_usuario.' '.$creador->apellido)
            : null;

        $estudiantes = EstudianteExamen::query()
            ->where('id_examen', $examen->id_examen)
            ->when($idDocente !== null, fn ($query) => $query->delDocente($idDocente))
            ->with('estudiante')
            ->get()
            ->map(function (EstudianteExamen $inscripcion) use ($registros, $incidencias, $aulasPorInscripcion, $aulasDelExamen, $docentesPorInscripcion, $creadorDelExamen) {
                $estudiante = $inscripcion->estudiante;
                $clave = $this->clave($inscripcion->id_estudiante_examen, $estudiante->sis_estudiante);
                $registro = $registros->get($clave);
                $incidencia = $incidencias->get($estudiante->sis_estudiante);

                $habilitado = $inscripcion->estado === EstadoEstudianteExamen::Habilitado;

                return [
                    'sis' => $estudiante->sis_estudiante,
                    'id_inscripcion' => (int) $inscripcion->id_estudiante_examen,
                    'nombre' => $estudiante->nombre_estudiante,
                    'apellido' => $estudiante->apellido_estudiante,
                    'aula' => $aulasPorInscripcion->get($inscripcion->id_estudiante_examen, $aulasDelExamen)
                        ?: 'Sin aula',
                    'docente_cargo' => $docentesPorInscripcion->get($inscripcion->id_estudiante_examen, $creadorDelExamen)
                        ?? 'Sin docente',
                    'habilitado' => $habilitado,
                    'registrador' => $registro !== null && $registro->registrador !== null
                        ? trim($registro->registrador->nombre_usuario.' '.$registro->registrador->apellido)
                        : null,
                    'hora_ingreso' => $registro?->hora_ingreso,
                    'incidencia' => $incidencia,
                    'estado_asistencia' => $this->resolverEstado($habilitado, $incidencia, $registro, $inscripcion->motivo),
                ];
            });

        return [
            'examen' => $examen,
            'estudiantes' => $estudiantes,
        ];
    }

    /**
     * Aula asignada a cada inscripción del examen, en una sola consulta.
     *
     * @param  int  $idExamen  ID del examen.
     * @return Collection<int|string, string> id de inscripción => aulas unidas por coma.
     */
    private function aulasPorInscripcion(int $idExamen): Collection
    {
        return DB::table('estudiante_examen_ambiente as eea')
            ->join('estudiante_examen as ee', 'ee.id_estudiante_examen', '=', 'eea.id_ee')
            ->join('ambiente as a', 'a.id_ambiente', '=', 'eea.id_ambiente')
            ->where('ee.id_examen', $idExamen)
            ->get(['eea.id_ee', 'a.nombre_ambiente'])
            ->groupBy('id_ee')
            ->map(fn (Collection $filas): string => $filas->pluck('nombre_ambiente')->unique()->implode(', '));
    }

    /**
     * Docente a cargo de cada inscripción, en una sola consulta.
     *
     * Sale de la configuración del estudiante (`configuracion_examen_docente`
     * → `usuario`); una inscripción sin configuración no tiene entrada aquí y
     * en `ejecutar()` se resuelve con el creador del examen.
     *
     * @param  int  $idExamen  ID del examen.
     * @return Collection<int|string, string> id de inscripción => nombre del docente.
     */
    private function docentesPorInscripcion(int $idExamen): Collection
    {
        return DB::table('estudiante_examen as ee')
            ->join('configuracion_examen_docente as c', 'c.id_configuracion_examen', '=', 'ee.id_configuracion_examen')
            ->join('usuario as u', 'u.id_usuario', '=', 'c.id_docente')
            ->where('ee.id_examen', $idExamen)
            ->get(['ee.id_estudiante_examen', 'u.nombre_usuario', 'u.apellido'])
            ->groupBy('id_estudiante_examen')
            ->map(fn (Collection $filas): string => trim($filas->first()->nombre_usuario.' '.$filas->first()->apellido));
    }

    /**
     * Incidencia vigente de cada estudiante del examen.
     *
     * "Vigente" es la de `id_registro` más alto: si un estudiante acumuló
     * varias, manda la última. Un estudiante puede no tener ninguna, y en ese
     * caso la clave no existe.
     *
     * @param  int  $idExamen  ID del examen.
     * @return Collection<string, array{tipo: string, fecha: string, estado: string, motivo_etiqueta: string}>
     */
    private function incidenciasPorEstudiante(int $idExamen, ?int $idDocente): Collection
    {
        return CentralRiesgo::query()
            ->where('id_examen', $idExamen)
            ->orderBy('id_registro')
            ->get()
            ->keyBy(fn (CentralRiesgo $riesgo): string => $riesgo->sis_estudiante)
            ->map(fn (CentralRiesgo $riesgo): array => [
                'tipo' => (string) $riesgo->tipo_infraccion?->value,
                'examen_id' => (int) $riesgo->id_examen,
                'motivo' => $riesgo->motivo instanceof Motivo
                    ? $riesgo->motivo->value
                    : (string) $riesgo->motivo,
                'fecha' => $riesgo->fecha_registro?->format('d/m/Y') ?? '',
                // El estado de revisión sale del tipo (tramposo = Confirmado).
                'estado' => (string) $riesgo->tipo_infraccion?->estado(),
                // `motivo` llega casteado a Motivo desde el modelo, pero se
                // acepta también el texto plano por si el cast se retira.
                'motivo_etiqueta' => $riesgo->motivo instanceof Motivo
                    ? $riesgo->motivo->etiqueta()
                    : (Motivo::tryFrom((string) $riesgo->motivo)?->etiqueta() ?? (string) $riesgo->motivo),
            ]);
    }

    private function configuracion(Examen $examen, ?int $idDocente): ?ConfiguracionExamenDocente
    {
        if ($idDocente === null) {
            return null;
        }

        return ConfiguracionExamenDocente::query()
            ->where('id_examen', $examen->id_examen)
            ->delDocente($idDocente)
            ->with(['normas', 'materialesPermitidos', 'normasPersonalizadas', 'materialesPersonalizados'])
            ->firstOrFail();
    }

    private function clave(mixed $idInscripcion, mixed $sis): string
    {
        return $idInscripcion === null ? 'sis:'.$sis : 'inscripcion:'.$idInscripcion;
    }

    /**
     * Estado del estudiante en el monitor.
     *
     * El orden importa: un deshabilitado está rechazado aunque tenga ingreso
     * viejo, y una incidencia pesa más que el ingreso porque es lo que el
     * docente tiene que atender.
     *
     * @param  bool  $habilitado  Si la inscripción está habilitada.
     * @param  array|null  $incidencia  Incidencia vigente, si tiene.
     * @param  RegistroAsistencia|null  $registro  Fila de asistencia, si entró.
     * @return string presente | rechazado | riesgo | pendiente
     */
    private function resolverEstado(bool $habilitado, ?array $incidencia, ?RegistroAsistencia $registro, ?string $motivo = null): string
    {
        if ($registro !== null) {
            return 'presente';
        }

        if (! $habilitado && $motivo === RegistroIngresoService::MOTIVO_RECHAZO) {
            return 'rechazado';
        }

        return 'pendiente';
    }
}
