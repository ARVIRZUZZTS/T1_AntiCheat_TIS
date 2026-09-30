<?php

/**
 * @file    ListarAlertasService.php
 *
 * @author  David E. Chavez T. <virzuzz12345@gmail.com>
 *
 * @created 2026-09-29
 *
 * @updated 2026-09-29
 *
 * @description
 * Servicio de dominio que lista las alertas de la central de riesgo.
 * Obtiene las infracciones registradas con el estudiante asociado,
 * el motivo, el tipo de infracción y la fecha de registro.
 *
 * @changelog
 * - 2026-09-29  [David E. Chavez T.]  feat: creación inicial del servicio.
 *
 * @see  CentralRiesgo
 * @see  Estudiante
 */

namespace App\Services\CentralRiesgo;

use App\Models\CentralRiesgo;
use Illuminate\Support\Collection;

class ListarAlertasService
{
    /**
     * Lista las alertas de la central de riesgo.
     *
     * @return Collection<int, object> Colección de alertas con estudiante, motivo y severidad.
     */
    public function ejecutar(): Collection
    {
        return CentralRiesgo::query()
            ->with(['registroAsistencia.estudiante', 'registrador'])
            ->orderByDesc('fecha_registro')
            ->get()
            ->map(function (CentralRiesgo $alerta) {
                return (object) [
                    'id_registro' => $alerta->id_registro,
                    'estudiante_nombre' => $alerta->registroAsistencia?->estudiante
                        ? trim($alerta->registroAsistencia->estudiante->nombre_estudiante.' '.$alerta->registroAsistencia->estudiante->apellido_estudiante)
                        : 'Desconocido',
                    'sis' => $alerta->registroAsistencia?->estudiante?->sis_estudiante,
                    'detalle_motivo' => $alerta->detalle_motivo,
                    'tipo_infraccion' => $alerta->tipo_infraccion->value,
                    'estado_incidencia' => $alerta->estado_incidencia->value,
                    'fecha_registro' => $alerta->fecha_registro,
                    'registrador' => $alerta->registrador
                        ? trim($alerta->registrador->nombre_usuario.' '.$alerta->registrador->apellido)
                        : null,
                ];
            });
    }
}
