<?php

namespace App\Services\Invitacion;

use App\Enums\InvitacionEstado;
use App\Models\Examen;
use App\Models\InvitacionExamenCompartido;
use App\Models\Rol;
use App\Models\Usuario;
use App\Services\Notificacion\NotificacionService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class EnviarInvitacionExamenService
{
    public const MENSAJE_DUPLICADA = 'Este docente ya fue invitado';

    public function __construct(
        private readonly NotificacionService $notificaciones,
    ) {}

    /**
     * @return array<string, mixed>
     *
     * @throws ValidationException
     */
    public function ejecutar(Usuario $invitador, Examen $examen, int $idDocenteInvitado): array
    {
        Gate::forUser($invitador)->authorize('invitarDocente', $examen);

        $invitado = Usuario::query()->find($idDocenteInvitado);

        if ($invitado === null || ! $this->esDocente($invitado)) {
            throw ValidationException::withMessages([
                'docente_invitado_id' => 'El docente invitado no es válido.',
            ]);
        }

        if ((int) $invitador->id_usuario === (int) $invitado->id_usuario) {
            throw ValidationException::withMessages([
                'docente_invitado_id' => 'No puede invitarse a sí mismo.',
            ]);
        }

        try {
            $invitacion = DB::transaction(function () use ($invitador, $invitado, $examen): InvitacionExamenCompartido {
                $this->bloquearTabla();

                $yaExiste = InvitacionExamenCompartido::query()
                    ->where('id_examen', $examen->id_examen)
                    ->where('id_docente_invitado', $invitado->id_usuario)
                    ->whereIn('estado', [InvitacionEstado::Pendiente->value, InvitacionEstado::Aceptada->value])
                    ->exists();

                if ($yaExiste) {
                    $this->lanzarDuplicada();
                }

                $invitacion = InvitacionExamenCompartido::create([
                    'id_invitacion' => (int) InvitacionExamenCompartido::query()->max('id_invitacion') + 1,
                    'id_docente_invitador' => $invitador->id_usuario,
                    'id_docente_invitado' => $invitado->id_usuario,
                    'id_examen' => $examen->id_examen,
                    'estado' => InvitacionEstado::Pendiente,
                ]);

                $this->notificaciones->notificarInvitacionExamen($invitacion);

                return $invitacion;
            });
        } catch (UniqueConstraintViolationException $excepcion) {
            if (str_contains($excepcion->getMessage(), 'invitacion_examen_activa_unique')) {
                $this->lanzarDuplicada();
            }

            throw $excepcion;
        }

        $invitacion->load([
            'invitador:id_usuario,nombre_usuario,apellido',
            'invitado:id_usuario,nombre_usuario,apellido,correo',
            'examen.tipoExamen',
            'examen.cursos:id_curso,nombre_curso',
        ]);

        return $this->resumen($invitacion);
    }

    private function esDocente(Usuario $usuario): bool
    {
        return $usuario->roles()->where('nombre_rol', Rol::NOMBRE_DOCENTE)->exists();
    }

    /** @throws ValidationException */
    private function lanzarDuplicada(): never
    {
        throw ValidationException::withMessages([
            'docente_invitado_id' => self::MENSAJE_DUPLICADA,
        ]);
    }

    private function bloquearTabla(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('LOCK TABLE invitacion_examen_compartido IN SHARE ROW EXCLUSIVE MODE');
        }
    }

    /** @return array<string, mixed> */
    private function resumen(InvitacionExamenCompartido $invitacion): array
    {
        $examen = $invitacion->examen;

        return [
            'id' => (int) $invitacion->id_invitacion,
            'estado' => $invitacion->estado->value,
            'enviada_en' => $invitacion->created_at?->toISOString(),
            'examen' => [
                'id' => (int) $examen->id_examen,
                'tipo' => $examen->tipoExamen?->nombre_tipo_examen,
                'fecha' => $examen->fecha,
                'materias' => $examen->cursos->pluck('nombre_curso')->values()->all(),
            ],
            'invitador' => [
                'id' => (int) $invitacion->invitador->id_usuario,
                'nombre' => $this->nombreCompleto($invitacion->invitador),
            ],
            'invitado' => [
                'id' => (int) $invitacion->invitado->id_usuario,
                'nombre' => $this->nombreCompleto($invitacion->invitado),
                'correo' => $invitacion->invitado->correo,
            ],
        ];
    }

    private function nombreCompleto(Usuario $usuario): string
    {
        return trim($usuario->nombre_usuario.' '.$usuario->apellido);
    }
}
