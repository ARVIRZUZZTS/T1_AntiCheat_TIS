<?php

namespace App\Services\Invitacion;

use App\Enums\CursoEstado;
use App\Enums\EstadoCuenta;
use App\Enums\InvitacionEstado;
use App\Models\Examen;
use App\Models\InvitacionExamenCompartido;
use App\Models\Rol;
use App\Models\Usuario;
use App\Services\Notificacion\NotificacionService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EnviarInvitacionExamenService
{
    public const MENSAJE_DUPLICADA = 'Este docente ya fue invitado';

    public function __construct(
        private readonly NotificacionService $notificaciones,
    ) {}

    /**
     * Registra la invitacion "Pendiente" y avisa al invitado.
     *
     * Cada viaje a la base remota pesa (~400 ms): los permisos y validaciones
     * salen de una sola consulta, y la invitacion y su aviso se insertan con
     * su id calculado en el mismo INSERT (4 viajes en total, antes 13).
     *
     * @return array<string, mixed>
     *
     * @throws AuthorizationException Si no es el anfitrion, no es docente o el
     *                                examen tiene un curso archivado.
     * @throws ValidationException
     */
    public function ejecutar(Usuario $invitador, Examen $examen, int $idDocenteInvitado): array
    {
        // Solo el anfitrion (creador) invita: se sabe sin consultar.
        if ((int) $examen->creador !== (int) $invitador->id_usuario) {
            throw new AuthorizationException;
        }

        if ((int) $invitador->id_usuario === $idDocenteInvitado) {
            throw ValidationException::withMessages([
                'docente_invitado_id' => 'No puede invitarse a sí mismo.',
            ]);
        }

        // Solo se invita a exámenes a futuro: si ya empezó (o no tiene fecha) no
        // tiene sentido sumar un docente. La zona horaria la fija config/app
        // (America/La_Paz), así que now() ya está en hora local.
        $inicio = $this->inicioDelExamen($examen);
        if ($inicio === null || $inicio->lte(now())) {
            throw ValidationException::withMessages([
                'id_examen' => 'No se puede invitar a un examen que ya empezó o pasó.',
            ]);
        }

        $datos = $this->validar($invitador, $examen, $idDocenteInvitado);

        // Mismo criterio que ExamenPolicy::invitarDocente.
        if (! (bool) $datos->solicitante_docente || (bool) $datos->archivado) {
            throw new AuthorizationException;
        }

        // Un docente del registro sin validar (o rechazado) no se invita (HU 26).
        if (! (bool) $datos->invitado_docente || $datos->estado_invitado !== EstadoCuenta::Habilitada->value) {
            throw ValidationException::withMessages([
                'docente_invitado_id' => 'El docente invitado no es válido.',
            ]);
        }

        // Ya invitado (pendiente o aceptado) o ya comparte el examen (tiene uno
        // de sus cursos en el).
        if ((bool) $datos->ya_invitado || (bool) $datos->comparte) {
            $this->lanzarDuplicada();
        }

        try {
            $invitacion = DB::transaction(function () use ($invitador, $examen, $idDocenteInvitado): InvitacionExamenCompartido {
                $this->bloquearTablas();
                $invitacion = $this->insertar($invitador, $examen, $idDocenteInvitado);
                $this->notificaciones->notificarInvitacionExamen($invitacion, tablaBloqueada: true);

                return $invitacion;
            });
        } catch (UniqueConstraintViolationException $excepcion) {
            if (str_contains($excepcion->getMessage(), 'invitacion_examen_activa_unique')) {
                $this->lanzarDuplicada();
            }

            throw $excepcion;
        }

        // Todo lo del resumen ya esta en memoria: no se vuelve a consultar.
        $invitado = (new Usuario)->forceFill([
            'id_usuario' => $idDocenteInvitado,
            'nombre_usuario' => $datos->nombre_invitado,
            'apellido' => $datos->apellido_invitado,
            'correo' => $datos->correo_invitado,
        ]);

        return $this->resumen($invitacion, $invitador, $invitado, $examen, $datos);
    }

    /**
     * Permisos y validaciones del envio en una sola consulta.
     */
    private function validar(Usuario $invitador, Examen $examen, int $idInvitado): object
    {
        $esDocente = 'exists (select 1 from rol_usuario ru join rol r on r.id_rol = ru.id_rol where ru.id_usuario = ? and r.nombre_rol = ?)';
        $materias = DB::getDriverName() === 'pgsql'
            ? "string_agg(c.nombre_curso, '||' order by c.nombre_curso)"
            : "group_concat(c.nombre_curso, '||')";

        return DB::selectOne(
            'select '
            .$esDocente.' as solicitante_docente, '
            .'exists (select 1 from examen_curso ec join curso c on c.id_curso = ec.id_curso where ec.id_examen = ? and c.estado = ?) as archivado, '
            .$esDocente.' as invitado_docente, '
            .'(select u.estado_cuenta from usuario u where u.id_usuario = ?) as estado_invitado, '
            .'(select u.nombre_usuario from usuario u where u.id_usuario = ?) as nombre_invitado, '
            .'(select u.apellido from usuario u where u.id_usuario = ?) as apellido_invitado, '
            .'(select u.correo from usuario u where u.id_usuario = ?) as correo_invitado, '
            .'exists (select 1 from invitacion_examen_compartido i where i.id_examen = ? and i.id_docente_invitado = ? and i.estado in (?, ?)) as ya_invitado, '
            .'exists (select 1 from examen_curso ec join curso c on c.id_curso = ec.id_curso where ec.id_examen = ? and c.sis_doc = ?) as comparte, '
            .'(select t.nombre_tipo_examen from tipo_examen t where t.id_tipo_examen = ?) as tipo, '
            .'(select '.$materias.' from examen_curso ec join curso c on c.id_curso = ec.id_curso where ec.id_examen = ?) as materias',
            [
                $invitador->id_usuario, Rol::NOMBRE_DOCENTE,
                $examen->id_examen, CursoEstado::Archivado->value,
                $idInvitado, Rol::NOMBRE_DOCENTE,
                $idInvitado, $idInvitado, $idInvitado, $idInvitado,
                $examen->id_examen, $idInvitado, InvitacionEstado::Pendiente->value, InvitacionEstado::Aceptada->value,
                $examen->id_examen, $idInvitado,
                $examen->tipo_examen,
                $examen->id_examen,
            ],
        );
    }

    /**
     * Inserta la invitacion con su id (max + 1) en el mismo INSERT.
     */
    private function insertar(Usuario $invitador, Examen $examen, int $idInvitado): InvitacionExamenCompartido
    {
        $ahora = now();
        $fila = DB::selectOne(
            'insert into invitacion_examen_compartido (id_invitacion, id_docente_invitador, id_docente_invitado, id_examen, estado, created_at, updated_at) '
            .'select coalesce(max(id_invitacion), 0) + 1, ?, ?, ?, ?, ?, ? from invitacion_examen_compartido '
            .'returning id_invitacion',
            [$invitador->id_usuario, $idInvitado, $examen->id_examen, InvitacionEstado::Pendiente->value, $ahora, $ahora],
        );

        $invitacion = (new InvitacionExamenCompartido)->forceFill([
            'id_invitacion' => (int) $fila->id_invitacion,
            'id_docente_invitador' => $invitador->id_usuario,
            'id_docente_invitado' => $idInvitado,
            'id_examen' => $examen->id_examen,
            'estado' => InvitacionEstado::Pendiente,
            'created_at' => $ahora,
            'updated_at' => $ahora,
        ]);
        $invitacion->exists = true;

        return $invitacion;
    }

    private function inicioDelExamen(Examen $examen): ?Carbon
    {
        if ($examen->fecha === null) {
            return null;
        }

        return Carbon::parse($examen->fecha.' '.($examen->hora_inicio ?? '00:00:00'));
    }

    private function lanzarDuplicada(): never
    {
        throw ValidationException::withMessages([
            'docente_invitado_id' => self::MENSAJE_DUPLICADA,
        ]);
    }

    /** Invitaciones y avisos en un solo LOCK (los ids salen de max + 1). */
    private function bloquearTablas(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('LOCK TABLE invitacion_examen_compartido, notificacion_docente IN SHARE ROW EXCLUSIVE MODE');
        }
    }

    /** @return array<string, mixed> */
    private function resumen(InvitacionExamenCompartido $invitacion, Usuario $invitador, Usuario $invitado, Examen $examen, object $datos): array
    {
        return [
            'id' => (int) $invitacion->id_invitacion,
            'estado' => $invitacion->estado->value,
            'enviada_en' => $invitacion->created_at->toISOString(),
            'examen' => [
                'id' => (int) $examen->id_examen,
                'tipo' => $datos->tipo,
                'fecha' => $examen->fecha,
                'materias' => $datos->materias ? explode('||', (string) $datos->materias) : [],
            ],
            'invitador' => [
                'id' => (int) $invitador->id_usuario,
                'nombre' => $this->nombreCompleto($invitador),
            ],
            'invitado' => [
                'id' => (int) $invitado->id_usuario,
                'nombre' => $this->nombreCompleto($invitado),
                'correo' => $invitado->correo,
            ],
        ];
    }

    private function nombreCompleto(Usuario $usuario): string
    {
        return trim($usuario->nombre_usuario.' '.$usuario->apellido);
    }
}
