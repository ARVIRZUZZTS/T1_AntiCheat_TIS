<?php

/**
 * @file    ConsultarDetalleIncidenciaService.php
 *
 * @author  Alisson D. Alvarado <alvaradoalissondalet@gmail.com>
 *
 * @created 2026-10-09
 *
 * @updated 2026-10-09
 *
 * @description
 * Servicio de dominio que devuelve el detalle de una incidencia de la
 * central de riesgos: estudiante, SIS, motivo, materia, quien la reportó,
 * fecha y hora, estado y descripción del hecho. Admite los dos esquemas de
 * `central_riesgo`: el de Supabase, que enlaza `id_examen` y `sis_estudiante`
 * directo, y el original, que lo hace a través de `registro_asistencia` (`id_ingreso`).
 *
 * @changelog
 * - 2026-10-09  [Alisson D. Alvarado]  feat: consulta del detalle de una
 *   incidencia, con error controlado si no existe.
 *
 * @see  App\Policies\CentralRiesgoPolicy
 * @see  App\Models\CentralRiesgo
 */

namespace App\Services\CentralRiesgo;

use App\Enums\EstadoIncidencia;
use App\Enums\Motivo;
use App\Models\CentralRiesgo;
use App\Models\Estudiante;
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
            ->with(['registroAsistencia.estudiante', 'registrador.roles'])
            ->findOrFail($idIncidencia);

        $idExamen = $incidencia->getAttribute('id_examen')
            ?? $incidencia->registroAsistencia?->id_examen;

        $estudiante = $incidencia->registroAsistencia->estudiante
            ?? Estudiante::query()->find($incidencia->getAttribute('sis_estudiante'));

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
            'fecha_registro' => $incidencia->fecha_registro,
            'estado' => $estado instanceof EstadoIncidencia ? $estado->value : null,
            'descripcion' => $incidencia->detalle_motivo,
        ];
    }

    private function etiquetaMotivo(CentralRiesgo $incidencia): ?string
    {
        $motivo = $incidencia->getAttribute('motivo');

        if ($motivo === null) {
            return null;
        }

        return Motivo::tryFrom($motivo)?->etiqueta() ?? $motivo;
    }


    private function nombreMateria(?int $idExamen): ?string
    {
        if ($idExamen === null) {
            return null;
        }

        return Examen::query()->with('cursos')->find($idExamen)?->cursos->first()?->nombre_curso;
    }
}