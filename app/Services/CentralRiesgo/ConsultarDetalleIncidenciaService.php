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
 */

namespace App\Services\CentralRiesgo;

use App\Enums\EstadoIncidencia;
use App\Enums\Motivo;
use App\Models\CentralRiesgo;
use App\Models\Examen;
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
            ->with(['estudiante', 'registrador.roles'])
            ->findOrFail($idIncidencia);

        $idExamen = $incidencia->getAttribute('id_examen');

        $estudiante = $incidencia->estudiante;

        $estado = $incidencia->getAttribute('estado_incidencia');

        return [
            'id_registro' => $incidencia->id_registro,
            'estudiante_nombre' => $estudiante !== null
                ? trim($estudiante->nombre_estudiante.' '.$estudiante->apellido_estudiante)
                : null,
            'sis_estudiante' => $incidencia->getAttribute('sis_estudiante')
                ?? $estudiante?->sis_estudiante,
            'motivo' => $this->etiquetaMotivo($incidencia),
            'materia' => $this->nombreMateria($idExamen),
            'reportado_por' => $incidencia->registrador !== null
                ? trim($incidencia->registrador->nombre_usuario.' '.$incidencia->registrador->apellido)
                : null,
            'reportado_por_rol' => $incidencia->registrador?->roles->first()?->nombre_rol,
            // Formato local plano: el contrato del endpoint es 'Y-m-d H:i:s'
            // (lo que espera el front y DetalleIncidenciaTest); el Carbon crudo
            // se serializa en ISO-8601 con zona UTC y corre la fecha un día.
            'fecha_registro' => $incidencia->fecha_registro->format('Y-m-d H:i:s'),
            'estado' => $estado instanceof EstadoIncidencia
                ? $estado->value
                : (is_string($estado) ? $estado : null),
            'descripcion' => $incidencia->detalle_motivo,
        ];
    }

    private function etiquetaMotivo(CentralRiesgo $incidencia): ?string
    {
        $motivo = $incidencia->getAttribute('motivo');

        if ($motivo instanceof Motivo) {
            return $motivo->etiqueta();
        }

        if ($motivo === null) {
            return null;
        }

        return Motivo::tryFrom((string) $motivo)?->etiqueta() ?? (string) $motivo;
    }

    private function nombreMateria(?int $idExamen): ?string
    {
        if ($idExamen === null) {
            return null;
        }

        return Examen::query()->with('cursos')->find($idExamen)?->cursos->first()?->nombre_curso;
    }
}
