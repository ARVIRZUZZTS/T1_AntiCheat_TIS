<?php

/**
 * @file    ResolverIncidenciaService.php
 *
 * @author  Diego Tejerina <josediegotejerinamolina@gmail.com>
 *
 * @created 2026-10-09
 *
 * @updated 2026-10-09
 *
 * @description Confirma o descarta incidencias pendientes dentro de una transacción.
 *
 * @changelog
 * - 2026-10-09  [Diego Tejerina]  feat: resolver incidencias sin duplicados (#142).
 * - 2026-10-11  [David E. Chavez T.]  feat: al confirmar se avisa a los
 *   docentes de las materias activas del estudiante (HU 6 · T1).
 *
 * @see App\Policies\CentralRiesgoPolicy
 */

namespace App\Services\CentralRiesgo;

use App\Enums\EstadoIncidencia;
use App\Models\CentralRiesgo;
use App\Models\Usuario;
use App\Services\Notificacion\NotificacionService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;

class ResolverIncidenciaService
{
    /**
     * @param  int  $idIncidencia  Identificador de una incidencia existente.
     * @param  Usuario  $docente  Usuario autenticado que realiza la revisión.
     * @return CentralRiesgo La misma incidencia, confirmada, sin insertar otra fila.
     *
     * @throws ModelNotFoundException Si la incidencia no existe.
     * @throws AuthorizationException Si el docente no está autorizado.
     * @throws InvalidArgumentException Si ya no está pendiente.
     */
    public function confirmar(int $idIncidencia, Usuario $docente): CentralRiesgo
    {
        return DB::transaction(function () use ($idIncidencia, $docente): CentralRiesgo {
            $incidencia = $this->obtenerPendiente($idIncidencia, $docente);
            $incidencia->estado_incidencia = EstadoIncidencia::Confirmado;
            $incidencia->id_confirmador = $docente->id_usuario;
            $incidencia->save();

            // HU 6 · T1: confirmar es el momento en que el caso del auxiliar
            // ingresa efectivamente a la central de riesgos, así que recién ahí
            // se avisa a cada docente con materia activa del estudiante. El
            // caso sigue con `tipo_infraccion = sospechoso`, por eso el aviso
            // se fuerza desde la confirmación.
            app(NotificacionService::class)->notificarCentralRiesgo($incidencia, porConfirmacion: true);

            return $incidencia;
        });
    }

    /**
     * @param  int  $idIncidencia  Identificador de la incidencia a descartar.
     * @param  Usuario  $docente  Usuario autenticado que realiza la revisión.
     *
     * @throws ModelNotFoundException Si la incidencia no existe.
     * @throws AuthorizationException Si el docente no está autorizado.
     * @throws InvalidArgumentException Si ya no está pendiente.
     */
    public function rechazar(int $idIncidencia, Usuario $docente): void
    {
        DB::transaction(function () use ($idIncidencia, $docente): void {
            $incidencia = $this->obtenerPendiente($idIncidencia, $docente);

            // La FK impide descartar la incidencia mientras tenga avisos asociados.
            DB::table('notificacion_docente')->where('id_central_riesgo', $idIncidencia)->delete();
            $incidencia->delete();
        });
    }

    /** La fila bloqueada evita que confirmar y rechazar resuelvan simultáneamente el mismo caso. */
    private function obtenerPendiente(int $idIncidencia, Usuario $docente): CentralRiesgo
    {
        $incidencia = CentralRiesgo::query()->lockForUpdate()->findOrFail($idIncidencia);
        Gate::forUser($docente)->authorize('resolver', $incidencia);

        if ($incidencia->estado_incidencia !== EstadoIncidencia::Pendiente) {
            throw new InvalidArgumentException('Solo se pueden resolver incidencias pendientes de revisión.');
        }

        return $incidencia;
    }
}
