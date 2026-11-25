<?php

/**
 * @file    NotificarCambiosExamenService.php
 *
 * @author  T1 - Tech One SRL
 *
 * @created 2026-11-24
 *
 * @description
 * Servicio de la feature Examen que avisa a los usuarios involucrados cuando se
 * editan los ambientes, la fecha o la hora de un examen ya programado.
 *
 * - Un examen no compartido avisa solo a los auxiliares asignados (por curso o
 *   directamente al examen).
 * - Un examen compartido también avisa a los docentes que participan: el
 *   creador, los invitados que aceptaron y los dueños de los cursos vinculados.
 *   Quien edita se excluye para no avisarse a sí mismo.
 * - Se guarda una copia del texto de cada cambio en `detalles`, porque el aviso
 *   es histórico: si el examen se vuelve a editar, la notificación antigua debe
 *   seguir diciendo lo que pasó en su momento.
 *
 * @see  App\Services\Notificacion\NotificacionService
 * @see  App\Services\Examen\ActualizarExamenService
 *
 * @changelog
 * - 2026-11-24  [Equipo T1]  feat: avisos de cambios de ambientes, fecha y hora
 *   para auxiliares asignados y docentes de exámenes compartidos.
 */

namespace App\Services\Examen;

use App\Enums\InvitacionEstado;
use App\Models\Examen;
use App\Models\NotificacionDocente;
use App\Models\Rol;
use App\Models\Usuario;
use App\Services\Auxiliar\AccesoAuxiliarService;
use App\Services\Notificacion\NotificacionService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class NotificarCambiosExamenService
{
    public function __construct(
        private readonly NotificacionService $notificaciones,
    ) {}

    /**
     * Estado del examen antes de guardar, para compararlo después. Se llama
     * dentro de la transacción de edición, antes de tocar `$examen`.
     *
     * @return array{fecha: string, hora_inicio: string, ambientes: list<string>}
     */
    public function capturar(Examen $examen): array
    {
        return [
            'fecha' => $this->fechaLegible($examen->fecha),
            'hora_inicio' => $this->horaLegible($examen->hora_inicio),
            'ambientes' => $this->nombresAmbientes($examen),
        ];
    }

    /**
     * Compara el estado capturado con el ya guardado y crea los avisos. Si no
     * cambiaron ambientes, fecha ni hora, no escribe nada.
     *
     * @param  array{fecha: string, hora_inicio: string, ambientes: list<string>}  $antes
     * @return array<int, NotificacionDocente> Avisos creados.
     */
    public function notificar(Examen $examen, array $antes, Usuario $editor): array
    {
        $despues = $this->capturar($examen);
        $cambios = $this->cambios($antes, $despues);

        if ($cambios === []) {
            return [];
        }

        $destinatarios = $this->destinatarios($examen, (int) $editor->id_usuario);

        if ($destinatarios->isEmpty()) {
            return [];
        }

        $detalles = [
            'examen_id' => (int) $examen->id_examen,
            'examen' => $examen->tipoExamen?->nombre_tipo_examen ?: $examen->nombre_examen ?: 'Examen',
            'materias' => $examen->cursos->pluck('nombre_curso')->filter()->values()->all(),
            'editor' => $this->nombreCompleto($editor),
            'cambios' => $cambios,
            'compartido' => $this->esCompartido($examen),
        ];

        return $this->notificaciones->notificarCambioExamen($destinatarios, $detalles, now());
    }

    /**
     * @param  array{fecha: string, hora_inicio: string, ambientes: list<string>}  $antes
     * @param  array{fecha: string, hora_inicio: string, ambientes: list<string>}  $despues
     * @return list<string> Textos ya legibles de cada cambio.
     */
    private function cambios(array $antes, array $despues): array
    {
        $cambios = [];

        if ($antes['fecha'] !== $despues['fecha']) {
            $cambios[] = sprintf('fecha %s → %s', $antes['fecha'], $despues['fecha']);
        }

        if ($antes['hora_inicio'] !== $despues['hora_inicio']) {
            $cambios[] = sprintf('hora de inicio %s → %s', $antes['hora_inicio'], $despues['hora_inicio']);
        }

        if ($antes['ambientes'] !== $despues['ambientes']) {
            $cambios[] = sprintf(
                'ambientes %s → %s',
                $this->lista($antes['ambientes']),
                $this->lista($despues['ambientes']),
            );
        }

        return $cambios;
    }

    /** @param list<string> $nombres */
    private function lista(array $nombres): string
    {
        $texto = implode(', ', array_filter($nombres));

        return $texto !== '' ? $texto : 'sin ambientes';
    }

    private function fechaLegible(mixed $valor): string
    {
        if ($valor === null || $valor === '') {
            return '';
        }

        return Carbon::parse((string) $valor)->format('d/m/Y');
    }

    private function horaLegible(mixed $valor): string
    {
        if ($valor === null || $valor === '') {
            return '';
        }

        return Carbon::parse('2000-01-01 '.(string) $valor)->format('H:i');
    }

    /** @return list<string> */
    private function nombresAmbientes(Examen $examen): array
    {
        return $examen->ambientes()
            ->orderBy('nombre_ambiente')
            ->pluck('nombre_ambiente')
            ->filter()
            ->values()
            ->all();
    }

    /** @return Collection<int, Usuario> */
    private function destinatarios(Examen $examen, int $idEditor): Collection
    {
        $ids = collect([])
            ->merge($this->idsDocentes($examen))
            ->merge($this->idsAuxiliares($examen))
            ->reject(fn (int $id): bool => $id === $idEditor)
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
    private function idsDocentes(Examen $examen): Collection
    {
        if (! $this->esCompartido($examen)) {
            return collect();
        }

        $idExamen = (int) $examen->id_examen;

        $porCurso = DB::table('examen_curso as ec')
            ->join('curso as c', 'c.id_curso', '=', 'ec.id_curso')
            ->where('ec.id_examen', $idExamen)
            ->pluck('c.sis_doc');

        $invitados = DB::table('invitacion_examen_compartido')
            ->where('id_examen', $idExamen)
            ->where('estado', InvitacionEstado::Aceptada->value)
            ->pluck('id_docente_invitado');

        return collect([$examen->creador])
            ->merge($porCurso)
            ->merge($invitados)
            ->map(fn ($id): int => (int) $id);
    }

    /** @return Collection<int, int> */
    private function idsAuxiliares(Examen $examen): Collection
    {
        $idExamen = (int) $examen->id_examen;

        $porCurso = DB::table('auxiliar_curso as ac')
            ->join('examen_curso as ec', 'ec.id_curso', '=', 'ac.id_curso')
            ->join('rol_usuario as ru', 'ru.id_usuario', '=', 'ac.id_auxiliar')
            ->join('rol as r', 'r.id_rol', '=', 'ru.id_rol')
            ->where('ec.id_examen', $idExamen)
            ->where('ac.estado', AccesoAuxiliarService::ESTADO_ACTIVO)
            ->where('r.nombre_rol', Rol::NOMBRE_AUXILIAR)
            ->pluck('ac.id_auxiliar');

        $porExamen = DB::table('auxiliar_examen as ae')
            ->join('rol_usuario as ru', 'ru.id_usuario', '=', 'ae.id_auxiliar')
            ->join('rol as r', 'r.id_rol', '=', 'ru.id_rol')
            ->where('ae.id_examen', $idExamen)
            ->where('r.nombre_rol', Rol::NOMBRE_AUXILIAR)
            ->pluck('ae.id_auxiliar');

        return $porCurso
            ->merge($porExamen)
            ->map(fn ($id): int => (int) $id);
    }

    private function esCompartido(Examen $examen): bool
    {
        $idExamen = (int) $examen->id_examen;

        $hayInvitados = DB::table('invitacion_examen_compartido')
            ->where('id_examen', $idExamen)
            ->where('estado', InvitacionEstado::Aceptada->value)
            ->exists();

        if ($hayInvitados) {
            return true;
        }

        $docentes = collect([$examen->creador])
            ->merge(DB::table('examen_curso as ec')
                ->join('curso as c', 'c.id_curso', '=', 'ec.id_curso')
                ->where('ec.id_examen', $idExamen)
                ->pluck('c.sis_doc'))
            ->unique();

        return $docentes->count() > 1;
    }

    private function nombreCompleto(Usuario $usuario): string
    {
        return trim($usuario->nombre_usuario.' '.$usuario->apellido);
    }
}
