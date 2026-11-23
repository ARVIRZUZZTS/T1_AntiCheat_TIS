<?php

/**
 * @file    ListarInvitacionesRecibidasService.php
 *
 * @author  David E. Chavez T. <virzuzz12345@gmail.com>
 *
 * @created 2026-11-08
 *
 * @updated 2026-11-08
 * @updated 2026-11-22
 *
 * @description
 * Servicio de dominio que lista las invitaciones a exámenes recibidas por un
 * docente, filtrando por estado pendiente para la vista de recepción.
 *
 * @changelog
 * - 2026-11-08  [David E. Chavez T.]  feat: creación inicial del servicio.
 * - 2026-11-22  [Valery D. Ortuno P]  perf: la lista sale en una sola consulta
 *   (antes cinco) y cada invitacion trae la fecha y hora del examen.
 *
 * @see  App\Models\InvitacionExamenCompartido
 */

namespace App\Services\Invitacion;

use App\Enums\TipoExamen;
use App\Models\InvitacionExamenCompartido;
use App\Models\Usuario;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ListarInvitacionesRecibidasService
{
    /**
     * Invitaciones pendientes recibidas por el docente.
     *
     * @param  Usuario  $docente  Docente autenticado.
     * @return Collection<int, array{id: int, estado: string, enviada_en: string|null, examen: array{id: int, nombre: string}, invitador: array{id: int, nombre: string}}>
     *
     * @author David E. Chavez T. <virzuzz12345@gmail.com>
     *
     * @since  2026-11-08
     */
    public function ejecutar(Usuario $docente): Collection
    {
        // Una sola consulta: antes eran cinco (invitaciones + examen, tipo,
        // cursos e invitador por separado) y cada viaje a la base pesa.
        return DB::table('invitacion_examen_compartido as i')
            ->join('examen as e', 'e.id_examen', '=', 'i.id_examen')
            ->leftJoin('tipo_examen as t', 't.id_tipo_examen', '=', 'e.tipo_examen')
            ->join('usuario as u', 'u.id_usuario', '=', 'i.id_docente_invitador')
            ->where('i.id_docente_invitado', $docente->id_usuario)
            ->where('i.estado', 'pendiente')
            ->orderByDesc('i.created_at')
            ->selectRaw(
                'i.id_invitacion, i.estado, i.created_at, e.id_examen, e.nombre_examen, e.fecha, e.hora_inicio,'
                .' t.nombre_tipo_examen, i.id_docente_invitador, u.nombre_usuario, u.apellido,'
                .' (select c.nombre_curso from examen_curso ec join curso c on c.id_curso = ec.id_curso'
                .' where ec.id_examen = e.id_examen order by c.id_curso limit 1) as curso'
            )
            ->get()
            ->map(fn (object $fila): array => [
                'id' => (int) $fila->id_invitacion,
                'estado' => (string) $fila->estado,
                'enviada_en' => $fila->created_at ? Carbon::parse($fila->created_at)->toISOString() : null,
                'curso' => $fila->curso,
                'examen' => [
                    'id' => (int) $fila->id_examen,
                    'nombre' => (string) ($fila->nombre_examen
                        ?? TipoExamen::etiquetaDe($fila->nombre_tipo_examen)
                        ?? ('Examen #'.$fila->id_examen)),
                    'fecha' => $fila->fecha ? Carbon::parse($fila->fecha)->format('d/m/Y') : null,
                    'hora_inicio' => $fila->hora_inicio ? substr((string) $fila->hora_inicio, 0, 5) : null,
                ],
                'invitador' => [
                    'id' => (int) $fila->id_docente_invitador,
                    'nombre' => trim($fila->nombre_usuario.' '.$fila->apellido),
                ],
            ]);
    }

    /**
     * Cantidad de invitaciones pendientes recibidas.
     *
     * @param  Usuario  $docente  Docente autenticado.
     *
     * @author David E. Chavez T. <virzuzz12345@gmail.com>
     *
     * @since  2026-11-08
     */
    public function contarPendientes(Usuario $docente): int
    {
        return InvitacionExamenCompartido::query()
            ->where('id_docente_invitado', $docente->id_usuario)
            ->where('estado', 'pendiente')
            ->count();
    }
}
