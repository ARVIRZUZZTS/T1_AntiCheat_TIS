<?php

/**
 * @file    ConsultarDetalleIncidenciaService.php
 *
 * @author  Alisson D. Alvarado <alvaradoalissondalet@gmail.com>
 *
 * @created 2026-10-09
 *
 * @updated 2026-10-10
 *
 * @description
 * Servicio de dominio que devuelve el detalle de una incidencia de la
 * central de riesgos: estudiante, SIS, motivo, materia, quien la reportó,
 * fecha y hora, estado y descripción del hecho. El estudiante y el examen son
 * columnas propias de `central_riesgo` (`sis_estudiante`, `id_examen`), así que
 * se leen por las relaciones del modelo.
 *
 * @changelog
 * - 2026-10-09  [Alisson D. Alvarado]  feat: consulta del detalle de una
 *   incidencia, con error controlado si no existe.
 * - 2026-10-10  [Valery D. Ortuno P]  fix: el servicio cargaba la relación
 *   `registroAsistencia`, que el modelo perdió con el esquema #70, y fallaba
 *   con cualquier incidencia. Ahora usa `estudiante`, `curso` y `examen`; el
 *   motivo sale del enum ya casteado y la materia de `CentralRiesgo::materia()`,
 *   la misma fuente que el formulario de registro. El estado se deriva del tipo
 *   de infracción cuando la base no tiene `estado_incidencia`.
 *
 * @see  App\Policies\CentralRiesgoPolicy
 * @see  App\Models\CentralRiesgo
 * @see  App\Livewire\CentralRiesgo\ListaEstudiantes
 */

namespace App\Services\CentralRiesgo;

use App\Enums\EstadoIncidencia;
use App\Enums\TipoInfraccion;
use App\Models\CentralRiesgo;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ConsultarDetalleIncidenciaService
{
    /**
     * Devuelve el detalle completo de la incidencia solicitada.
     *
     * @param  int  $idIncidencia  Identificador de la incidencia (`id_registro`).
     * @return array<string, mixed> Detalle listo para el contrato JSON.
     *
     * @throws ModelNotFoundException Si la incidencia no existe (HTTP 404).
     */
    public function ejecutar(int $idIncidencia): array
    {
        $incidencia = CentralRiesgo::query()
            ->with(['estudiante', 'curso', 'examen.cursos', 'registrador.roles'])
            ->findOrFail($idIncidencia);

        $estudiante = $incidencia->estudiante;
        $registrador = $incidencia->registrador;

        return [
            'id_registro' => $incidencia->id_registro,
            'estudiante_nombre' => $estudiante !== null
                ? trim($estudiante->nombre_estudiante.' '.$estudiante->apellido_estudiante)
                : null,
            'sis_estudiante' => $incidencia->sis_estudiante,
            'motivo' => $incidencia->motivo->etiqueta(),
            'materia' => $incidencia->materia(),
            'reportado_por' => $registrador !== null
                ? trim($registrador->nombre_usuario.' '.$registrador->apellido)
                : null,
            'reportado_por_rol' => $registrador?->roles->first()?->nombre_rol,
            'fecha_registro' => $incidencia->fecha_registro->toDateTimeString(),
            'estado' => $this->estado($incidencia)->value,
            'descripcion' => $incidencia->detalle_motivo,
        ];
    }

    /**
     * Estado de revisión de la incidencia.
     *
     * Sale de `estado_incidencia` cuando la base tiene la columna. Si no la
     * tiene, se deriva del tipo de infracción con la misma regla que usa la
     * migración que la agrega: `tramposo` es un caso confirmado y `sospechoso`
     * uno pendiente de revisión.
     *
     * @param  CentralRiesgo  $incidencia  Incidencia consultada.
     * @return EstadoIncidencia Estado confirmado o pendiente.
     */
    private function estado(CentralRiesgo $incidencia): EstadoIncidencia
    {
        $guardado = $incidencia->getAttribute('estado_incidencia');

        if ($guardado instanceof EstadoIncidencia) {
            return $guardado;
        }

        $estado = is_string($guardado) ? EstadoIncidencia::tryFrom($guardado) : null;

        if ($estado !== null) {
            return $estado;
        }

        return $incidencia->tipo_infraccion === TipoInfraccion::Tramposo
            ? EstadoIncidencia::Confirmado
            : EstadoIncidencia::Pendiente;
    }
}
