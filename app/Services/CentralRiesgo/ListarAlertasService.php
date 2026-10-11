<?php

/**
 * @file    ListarAlertasService.php
 *
 * @author  David E. Chavez T. <virzuzz12345@gmail.com>
 *
 * @created 2026-09-29
 *
 * @updated 2026-10-09
 *
 * @description
 * Servicio de dominio que lista las alertas de la central de riesgo.
 * Obtiene las infracciones registradas con el estudiante asociado,
 * el motivo, el tipo de infracción y la fecha de registro.
 *
 * @changelog
 * - 2026-09-29  [David E. Chavez T.]  feat: creación inicial del servicio.
 * - 2026-10-09  [T1]  fix: adaptar al esquema #70 de `central_riesgo`: el
 *   estudiante se resuelve por la relación directa `estudiante` y desaparece
 *   `estado_incidencia`, que ya no existe en la base.
 *
 * @see  CentralRiesgo
 * @see  Estudiante
 */

namespace App\Services\CentralRiesgo;

use App\Models\CentralRiesgo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ListarAlertasService
{
    /**
     * Lista las alertas de la central de riesgo.
     *
     * @return Collection<int, object{id_registro: int, estudiante_nombre: string, sis: string|null, detalle_motivo: string|null, tipo_infraccion: 'aula equivocada'|'pendiente'|'sospechoso'|'tramposo', fecha_registro: Carbon, registrador: string|null}&\stdClass> Colección de alertas con estudiante, motivo y severidad.
     */
    public function ejecutar(): Collection
    {
        return CentralRiesgo::query()
            ->with(['estudiante', 'registrador'])
            ->orderByDesc('fecha_registro')
            ->get()
            ->map(function (CentralRiesgo $alerta) {
                return (object) [
                    'id_registro' => $alerta->id_registro,
                    'estudiante_nombre' => $alerta->estudiante
                        ? trim($alerta->estudiante->nombre_estudiante.' '.$alerta->estudiante->apellido_estudiante)
                        : 'Desconocido',
                    'sis' => $alerta->estudiante?->sis_estudiante,
                    'detalle_motivo' => $alerta->detalle_motivo,
                    'tipo_infraccion' => $alerta->tipo_infraccion->value,
                    'fecha_registro' => $alerta->fecha_registro,
                    'registrador' => $alerta->registrador
                        ? trim($alerta->registrador->nombre_usuario.' '.$alerta->registrador->apellido)
                        : null,
                ];
            });
    }
}
