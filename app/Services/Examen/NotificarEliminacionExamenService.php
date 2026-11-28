<?php

/**
 * @file    NotificarEliminacionExamenService.php
 *
 * @author  OchoaCesar <cesareduardonick@gmail.com>
 *
 * @created 2026-11-24
 *
 * @description
 * Servicio de la feature Examen que avisa a los usuarios involucrados cuando
 * un docente elimina un examen de uno de sus cursos (desvinculación
 * `examen_curso`).
 *
 * - Auxiliares: los asignados al curso con `auxiliar_curso.estado = Activo`
 *   (los «inscritos por el docente» en ese curso).
 * - Docentes: los que aceptaron compartir el examen
 *   (`invitacion_examen_compartido.estado = aceptado`).
 * - Quien elimina se excluye para no avisarse a sí mismo.
 * - El aviso se crea dentro de la transacción del borrado (mismo patrón
 *   atómico que `NotificarCambiosExamenService` en la edición) y solo cuando
 *   el borrado no viene de eliminar el curso completo (`$forzar`).
 * - Se guarda en `detalles` el nombre del examen, el curso y quien lo
 *   eliminó: el aviso es histórico y no debe desincronizarse si luego el
 *   examen cambia de nombre.
 *
 * @see  App\Services\Notificacion\NotificacionService
 * @see  App\Services\Examen\EliminarExamenCursoService
 *
 * @changelog
 * - 2026-11-24  [OchoaCesar]  feat: aviso «Examen eliminado» para auxiliares
 *   activos del curso y docentes que comparten el examen.
 */

namespace App\Services\Examen;

use App\Enums\InvitacionEstado;
use App\Models\Curso;
use App\Models\Examen;
use App\Models\NotificacionDocente;
use App\Models\Rol;
use App\Models\Usuario;
use App\Services\Auxiliar\AccesoAuxiliarService;
use App\Services\Notificacion\NotificacionService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class NotificarEliminacionExamenService
{
    public function __construct(
        private readonly NotificacionService $notificaciones,
    ) {}

    /**
     * Crea el aviso «Examen eliminado» para los auxiliares activos del curso
     * y los docentes que aceptaron compartir el examen, excluyendo a quien
     * elimina. Si no hay destinatarios, no escribe nada.
     *
     * @param  Examen  $examen  Examen desvinculado del curso.
     * @param  Curso  $curso  Curso del que se eliminó el examen.
     * @param  Usuario  $docente  Docente que ejecuta la eliminación.
     * @return array<int, NotificacionDocente> Avisos creados.
     *
     * @author OchoaCesar <cesareduardonick@gmail.com>
     *
     * @since  2026-11-24
     */
    public function notificar(Examen $examen, Curso $curso, Usuario $docente): array
    {
        $destinatarios = $this->destinatarios($examen, $curso, (int) $docente->id_usuario);

        if ($destinatarios->isEmpty()) {
            return [];
        }

        $detalles = [
            'examen_id' => (int) $examen->id_examen,
            'examen' => $examen->tipoExamen?->nombre_tipo_examen ?: $examen->nombre_examen ?: 'Examen',
            'curso_id' => (int) $curso->id_curso,
            'curso' => $curso->nombre_curso,
            'eliminado_por' => $this->nombreCompleto($docente),
        ];

        return $this->notificaciones->notificarExamenEliminado($destinatarios, $detalles, now());
    }

    /**
     * Auxiliares activos del curso y docentes invitados que aceptaron, sin
     * repetidos y sin incluir a quien elimina.
     *
     * @return Collection<int, Usuario>
     */
    private function destinatarios(Examen $examen, Curso $curso, int $idDocente): Collection
    {
        $ids = $this->idsAuxiliares($curso)
            ->merge($this->idsDocentesCompartidos($examen))
            ->reject(fn (int $id): bool => $id === $idDocente)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return collect();
        }

        return Usuario::query()
            ->whereIn('id_usuario', $ids->all())
            ->orderBy('apellido')
            ->orderBy('nombre_usuario')
            ->get();
    }

    /** @return Collection<int, int> */
    private function idsAuxiliares(Curso $curso): Collection
    {
        return DB::table('auxiliar_curso as ac')
            ->join('rol_usuario as ru', 'ru.id_usuario', '=', 'ac.id_auxiliar')
            ->join('rol as r', 'r.id_rol', '=', 'ru.id_rol')
            ->where('ac.id_curso', $curso->id_curso)
            ->where('ac.estado', AccesoAuxiliarService::ESTADO_ACTIVO)
            ->where('r.nombre_rol', Rol::NOMBRE_AUXILIAR)
            ->pluck('ac.id_auxiliar')
            ->map(fn ($id): int => (int) $id);
    }

    /** @return Collection<int, int> */
    private function idsDocentesCompartidos(Examen $examen): Collection
    {
        return DB::table('invitacion_examen_compartido')
            ->where('id_examen', $examen->id_examen)
            ->where('estado', InvitacionEstado::Aceptada->value)
            ->pluck('id_docente_invitado')
            ->map(fn ($id): int => (int) $id);
    }

    private function nombreCompleto(Usuario $usuario): string
    {
        return trim($usuario->nombre_usuario.' '.$usuario->apellido);
    }
}
