<?php

/**
 * @file    GenerarReporteAsistenciaService.php
 *
 * @author  OchoaCesar <cesareduardonick@gmail.com>
 *
 * @created 2026-09-25
 *
 * @updated 2026-10-10
 *
 * @description
 * Servicio de dominio que genera el reporte de asistencia de un examen.
 * Dado el id de un examen, resuelve el estado de cada estudiante inscrito
 * (`presente` / `rechazado` / `riesgo` / `pendiente`), la incidencia vigente
 * de la central de riesgos y el registrador y hora de ingreso.
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
 * - 2026-10-10  [T1]  fix: `with(['cursos', 'ambientes', 'materialesPermitidos'])`
 *   al resolver el examen. La vista del monitor lee esas tres relaciones y las
 *   cargaba por separado: tres viajes de ida y vuelta a la base por página que,
 *   con la latencia del servidor remoto, costaban ~1s.
 * - 2026-10-10  [T1]  feat: `tipoExamen` entra al `with()`, para el subtítulo del
 *   header del monitor (materia · tipo de examen). Sin esto la vista lo cargaría
 *   aparte y sumaría otro viaje a la base (~335ms con la latencia actual).
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
use App\Models\EstudianteExamen;
use App\Models\Examen;
use App\Models\RegistroAsistencia;
use Carbon\Carbon;
use Illuminate\Support\Collection;

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
     * @param  Carbon|null  $ahora  Fecha/hora actual; se conserva por
     *                              compatibilidad con la firma original pero el estado ya no depende de
     *                              ella, sino de lo que hay persistido.
     * @return array<string, mixed>
     */
    public function ejecutar(int $idExamen, ?Carbon $ahora = null): array
    {
        $examen = Examen::query()
            ->with(['cursos', 'ambientes', 'materialesPermitidos', 'tipoExamen'])
            ->findOrFail($idExamen);

        $registros = RegistroAsistencia::query()
            ->where('id_examen', $examen->id_examen)
            ->with('registrador')
            ->get()
            ->keyBy('id_estudiante');

        $incidencias = $this->incidenciasPorEstudiante($examen->id_examen);

        $estudiantes = EstudianteExamen::query()
            ->where('id_examen', $examen->id_examen)
            ->with('estudiante')
            ->get()
            ->map(function (EstudianteExamen $inscripcion) use ($registros, $incidencias) {
                $estudiante = $inscripcion->estudiante;
                $registro = $registros->get($estudiante->sis_estudiante);
                $incidencia = $incidencias->get($estudiante->sis_estudiante);

                $habilitado = $inscripcion->estado === EstadoEstudianteExamen::Habilitado;

                return [
                    'sis' => $estudiante->sis_estudiante,
                    'nombre' => $estudiante->nombre_estudiante,
                    'apellido' => $estudiante->apellido_estudiante,
                    'habilitado' => $habilitado,
                    'registrador' => $registro !== null && $registro->registrador !== null
                        ? trim($registro->registrador->nombre_usuario.' '.$registro->registrador->apellido)
                        : null,
                    'hora_ingreso' => $registro?->hora_ingreso,
                    'incidencia' => $incidencia,
                    'estado_asistencia' => $this->resolverEstado($habilitado, $incidencia, $registro),
                ];
            });

        return [
            'examen' => $examen,
            'estudiantes' => $estudiantes,
        ];
    }

    /**
     * Incidencia vigente de cada estudiante del examen.
     *
     * "Vigente" es la de `id_registro` más alto: si un estudiante acumuló
     * varias, manda la última. Un estudiante puede no tener ninguna, y en ese
     * caso la clave no existe.
     *
     * @param  int  $idExamen  ID del examen.
     */
    private function incidenciasPorEstudiante(int $idExamen): Collection
    {
        return CentralRiesgo::query()
            ->where('id_examen', $idExamen)
            ->orderBy('id_registro')
            ->get()
            ->keyBy('sis_estudiante')
            ->map(fn (CentralRiesgo $riesgo): array => [
                'tipo' => $riesgo->tipo_infraccion->value,
                'fecha' => $riesgo->fecha_registro->format('d/m/Y'),
                'estado' => (string) $riesgo->getAttribute('estado_incidencia'),
                'motivo_etiqueta' => Motivo::tryFrom((string) $riesgo->motivo)?->etiqueta() ?? (string) $riesgo->motivo,
            ]);
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
    private function resolverEstado(bool $habilitado, ?array $incidencia, ?RegistroAsistencia $registro): string
    {
        if (! $habilitado) {
            return 'rechazado';
        }

        if ($incidencia !== null) {
            return 'riesgo';
        }

        if ($registro !== null) {
            return 'presente';
        }

        return 'pendiente';
    }
}
