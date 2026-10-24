<?php

/**
 * @file    ConsultarDetalleIncidenciaService.php
 *
 * @author  Alisson D. Alvarado <alvaradoalissondalet@gmail.com>
 *
 * @created 2026-10-09
 *
 * @updated 2026-10-11
 * @updated 2026-10-24
 *
 * @description
 * Servicio de dominio que devuelve el detalle de una incidencia de la
 * central de riesgos: estudiante, SIS, motivo, curso (materia y grupo), quien la reportó,
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
 * - 2026-10-11  [Valery D. Ortuno P]  perf: el detalle pasa de seis consultas a
 *   una sola: el estudiante, el registrador, su rol y la materia se resuelven
 *   con joins y subconsultas dentro de la consulta base, en vez de eager loads
 *   contra un PostgreSQL remoto (~380 ms por viaje de red). El contrato de
 *   salida no cambia y DetalleIncidenciaTest lo verifica.
 * - 2026-10-24  [Valery D. Ortuno P]  refactor: el curso sale solo de
 *   `central_riesgo.id_curso` (sin deducirlo del examen) y el detalle trae
 *   `curso`, `materia` y `grupo`; el estado se deriva del tipo de infracción
 *   (tramposo = Confirmado, sospechoso = Pendiente) en vez de
 *   `estado_incidencia`, que se dejará de usar.
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
use App\Support\NombreCurso;
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
        // Una sola consulta: contra un PostgreSQL remoto cada viaje de red pesa
        // (~380 ms), así que el estudiante, el curso, el registrador y su rol
        // se traen con joins y subconsultas en lugar de eager loads.
        $incidencia = CentralRiesgo::query()
            ->leftJoin('estudiante', 'estudiante.sis_estudiante', '=', 'central_riesgo.sis_estudiante')
            ->leftJoin('usuario', 'usuario.id_usuario', '=', 'central_riesgo.id_registrador')
            // El curso es el que quedó registrado en la incidencia, igual que en
            // la lista de la central.
            ->leftJoin('curso', 'curso.id_curso', '=', 'central_riesgo.id_curso')
            ->where('central_riesgo.id_registro', $idIncidencia)
            ->select('central_riesgo.*')
            ->selectRaw('estudiante.nombre_estudiante as det_est_nombre')
            ->selectRaw('estudiante.apellido_estudiante as det_est_apellido')
            ->selectRaw('estudiante.sis_estudiante as det_est_sis')
            ->selectRaw('usuario.nombre_usuario as det_usr_nombre')
            ->selectRaw('usuario.apellido as det_usr_apellido')
            ->selectRaw('curso.nombre_curso as det_curso')
            // Rol (docente/auxiliar) del registrador, sin segunda consulta.
            ->selectRaw(
                '(select r.nombre_rol from rol_usuario ru'
                .' join rol r on r.id_rol = ru.id_rol'
                .' where ru.id_usuario = central_riesgo.id_registrador'
                .' order by r.id_rol limit 1) as det_rol'
            )
            ->first();

        if ($incidencia === null) {
            throw (new ModelNotFoundException)->setModel(CentralRiesgo::class, [$idIncidencia]);
        }

        $nombreEstudiante = $incidencia->getAttribute('det_est_nombre');
        $apellidoEstudiante = $incidencia->getAttribute('det_est_apellido');
        $nombreRegistrador = $incidencia->getAttribute('det_usr_nombre');
        $apellidoRegistrador = $incidencia->getAttribute('det_usr_apellido');
        $curso = $incidencia->getAttribute('det_curso');

        return [
            'id_registro' => $incidencia->id_registro,
            'estudiante_nombre' => $nombreEstudiante !== null
                ? trim($nombreEstudiante.' '.(string) $apellidoEstudiante)
                : null,
            'sis_estudiante' => $incidencia->getAttribute('sis_estudiante')
                ?? $incidencia->getAttribute('det_est_sis'),
            'motivo' => $this->etiquetaMotivo($incidencia),
            // "Calculo I - Grupo 2" completo, y por separado materia y grupo.
            'curso' => $curso,
            'materia' => $curso !== null ? NombreCurso::materia($curso) : null,
            'grupo' => $curso !== null ? NombreCurso::grupo($curso) : null,
            'reportado_por' => $nombreRegistrador !== null
                ? trim($nombreRegistrador.' '.(string) $apellidoRegistrador)
                : null,
            'reportado_por_rol' => $incidencia->getAttribute('det_rol'),
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
     * Estado de revisión de la incidencia, según su tipo de infracción:
     * `tramposo` es un caso confirmado y `sospechoso` uno pendiente.
     * `estado_incidencia` ya no se lee porque se va a dejar de usar.
     *
     * @param  CentralRiesgo  $incidencia  Incidencia consultada.
     * @return string Valor del estado ('Confirmado' o 'Pendiente').
     */
    private function estado(CentralRiesgo $incidencia): string
    {
        return $incidencia->tipo_infraccion === TipoInfraccion::Tramposo
            ? EstadoIncidencia::Confirmado->value
            : EstadoIncidencia::Pendiente->value;
    }
}
