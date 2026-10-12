<?php

/**
 * @file    ConsultarDetalleIncidenciaService.php
 *
 * @author  Alisson D. Alvarado <alvaradoalissondalet@gmail.com>
 *
 * @created 2026-10-09
 *
 * @updated 2026-10-11
 *
 * @description
 * Servicio de dominio que devuelve el detalle de una incidencia de la
 * central de riesgos: estudiante, SIS, motivo, materia, quien la reportó,
 * fecha y hora, estado y descripción del hecho. Trabaja contra el esquema
 * actual de `central_riesgo` (ver bd.md): el estudiante y el examen se
 * resuelven por las columnas propias `sis_estudiante` e `id_examen` y sus
 * relaciones directas; `central_riesgo` no tiene enlace a
 * `registro_asistencia` (no existe `id_ingreso`).
 *
 * Los enums casteados por el modelo (`motivo`, `estado_incidencia`) se
 * normalizan a su valor/etiqueta antes de armar el JSON.
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
 * - 2026-10-11  [OchoaCesar]  fix: se quita el eager load `registroAsistencia`,
 *   cuya relación ya no existe en el modelo (el esquema actual no tiene
 *   `id_ingreso` en `central_riesgo`), lo que producía un 500 en
 *   GET /api/incidencias/{id}; el estudiante se toma de la relación directa
 *   `estudiante()`. Además `etiquetaMotivo()` y `estado` aceptan los enums ya
 *   casteados por el modelo (antes `Motivo::tryFrom()` recibía un enum y
 *   lanzaba TypeError). La fecha se formatea como `Y-m-d H:i:s`: con el Carbon
 *   crudo JSON emitía ISO-8601 en UTC (corrigiendo la fecha un día) y el
 *   contrato del endpoint cambiaba respecto a lo que cubre DetalleIncidenciaTest.
 *
 * @see  App\Policies\CentralRiesgoPolicy
 * @see  App\Models\CentralRiesgo
 * @see  App\Livewire\CentralRiesgo\ListaEstudiantes
 */

namespace App\Services\CentralRiesgo;

use App\Enums\EstadoIncidencia;
use App\Enums\Motivo;
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
            'sis_estudiante' => $incidencia->getAttribute('sis_estudiante')
                ?? $estudiante?->sis_estudiante,
            'motivo' => $this->etiquetaMotivo($incidencia),
            'materia' => $incidencia->materia(),
            'reportado_por' => $registrador !== null
                ? trim($registrador->nombre_usuario.' '.$registrador->apellido)
                : null,
            'reportado_por_rol' => $registrador?->roles->first()?->nombre_rol,
            // Formato local plano: el contrato del endpoint es 'Y-m-d H:i:s'
            // (lo que espera el front y DetalleIncidenciaTest); el Carbon crudo
            // se serializa en ISO-8601 con zona UTC y corre la fecha un día.
            'fecha_registro' => $incidencia->fecha_registro->format('Y-m-d H:i:s'),
            'estado' => $this->estado($incidencia),
            'descripcion' => $incidencia->detalle_motivo,
        ];
    }

    private function etiquetaMotivo(CentralRiesgo $incidencia): ?string
    {
        $motivo = $incidencia->getAttribute('motivo');

        if ($motivo instanceof Motivo) {
            return $motivo->etiqueta();
        }

        if (is_string($motivo)) {
            return Motivo::tryFrom($motivo)?->etiqueta() ?? $motivo;
        }

        return null;
    }

    /**
     * Estado de revisión de la incidencia.
     *
     * Sale de `estado_incidencia` cuando la base o el modelo lo tiene. Si no,
     * se deriva del tipo de infracción con la misma regla que usa la
     * migración: `tramposo` es un caso confirmado y `sospechoso` uno pendiente.
     *
     * @param  CentralRiesgo  $incidencia  Incidencia consultada.
     * @return string Valor del estado ('Confirmado', 'Pendiente', etc.).
     */
    private function estado(CentralRiesgo $incidencia): string
    {
        $guardado = $incidencia->getAttribute('estado_incidencia');

        if ($guardado instanceof EstadoIncidencia) {
            return $guardado->value;
        }

        if (is_string($guardado)) {
            $estadoEnum = EstadoIncidencia::tryFrom($guardado);

            return $estadoEnum !== null ? $estadoEnum->value : $guardado;
        }

        return $incidencia->tipo_infraccion === TipoInfraccion::Tramposo
            ? EstadoIncidencia::Confirmado->value
            : EstadoIncidencia::Pendiente->value;
    }
}
