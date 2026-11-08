<?php

/**
 * @file    ValidarRegistroDocenteService.php
 *
 * @author  Valery D. Ortuno P <valerydariana98@gmail.com>
 *
 * @created 2026-11-08
 *
 * @description
 * Un docente habilitado aprueba o desaprueba el registro de un nuevo docente
 * (HU 26).
 *
 *  - aprobar(): suma una verificación. Con la segunda la cuenta queda
 *    habilitada: recién ahí puede ingresar. El rol docente lo recibe al
 *    registrarse, porque el registro solo crea docentes.
 *  - desaprobar(): exige un motivo y deja la cuenta rechazada (no puede
 *    ingresar y sale de la lista).
 *
 * Cada docente decide una sola vez por solicitud. Todo corre en una
 * transacción con la fila del solicitante bloqueada, así dos docentes que
 * aprueban a la vez no se pisan el conteo.
 *
 * @changelog
 * - 2026-11-08  [Valery D. Ortuno P]  feat: creación inicial (HU 26).
 *
 * @see  App\Enums\EstadoCuenta
 * @see  App\Services\Docente\ListarSolicitudesRegistroService
 */

namespace App\Services\Docente;

use App\Enums\EstadoCuenta;
use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ValidarRegistroDocenteService
{
    /** Aprobaciones que necesita una cuenta para quedar habilitada. */
    public const APROBACIONES_REQUERIDAS = 2;

    public const MOTIVO_MAXIMO = 300;

    public const MENSAJE_SIN_PERMISO = 'Solo un docente habilitado puede validar registros.';

    public const MENSAJE_RESUELTA = 'Esta solicitud ya fue resuelta.';

    public const MENSAJE_YA_APROBO = 'Ya aprobaste esta solicitud.';

    public const MENSAJE_MOTIVO_REQUERIDO = 'El motivo es obligatorio.';

    public const MENSAJE_MOTIVO_LARGO = 'El motivo admite hasta 300 caracteres.';

    /**
     * @return array{nombre: string, aprobaciones: int, habilitada: bool}
     *
     * @throws InvalidArgumentException Si el docente no puede validar o la solicitud ya no está pendiente.
     */
    public function aprobar(int $idSolicitante, int $idDocente): array
    {
        return $this->resolver($idSolicitante, $idDocente, function (object $solicitud) use ($idDocente): array {
            $this->guardarDecision($solicitud->id_usuario, $idDocente, 'aprobado');

            $aprobaciones = (int) $solicitud->aprobaciones + 1;
            $habilitada = $aprobaciones >= self::APROBACIONES_REQUERIDAS;

            if ($habilitada) {
                // El rol docente ya lo tiene desde el registro.
                DB::table('usuario')->where('id_usuario', $solicitud->id_usuario)
                    ->update(['estado_cuenta' => EstadoCuenta::Habilitada->value]);
            }

            return [
                'nombre' => $this->nombre($solicitud),
                'aprobaciones' => $aprobaciones,
                'habilitada' => $habilitada,
            ];
        });
    }

    /**
     * @return array{nombre: string}
     *
     * @throws InvalidArgumentException Si falta el motivo, el docente no puede validar o la solicitud ya no está pendiente.
     */
    public function desaprobar(int $idSolicitante, int $idDocente, ?string $motivo): array
    {
        $motivo = trim((string) $motivo);

        if ($motivo === '') {
            throw new InvalidArgumentException(self::MENSAJE_MOTIVO_REQUERIDO);
        }

        if (mb_strlen($motivo) > self::MOTIVO_MAXIMO) {
            throw new InvalidArgumentException(self::MENSAJE_MOTIVO_LARGO);
        }

        return $this->resolver($idSolicitante, $idDocente, function (object $solicitud) use ($idDocente, $motivo): array {
            $this->guardarDecision($solicitud->id_usuario, $idDocente, 'desaprobado', $motivo);

            DB::table('usuario')->where('id_usuario', $solicitud->id_usuario)
                ->update(['estado_cuenta' => EstadoCuenta::Rechazada->value]);

            return ['nombre' => $this->nombre($solicitud)];
        });
    }

    /**
     * Valida al docente y la solicitud y corre la decisión en una transacción.
     *
     * Lo que hay que revisar (si el docente puede validar, si ya decidió y
     * cuántas aprobaciones lleva la solicitud) llega en la misma consulta que
     * bloquea la fila del solicitante: un solo viaje a la base.
     *
     * @template T
     *
     * @param  callable(object): T  $decision
     * @return T
     */
    private function resolver(int $idSolicitante, int $idDocente, callable $decision): mixed
    {
        $resultado = DB::transaction(function () use ($idSolicitante, $idDocente, $decision) {
            $solicitud = DB::table('usuario as u')
                ->where('u.id_usuario', $idSolicitante)
                ->select(['u.id_usuario', 'u.nombre', 'u.apellido', 'u.nombre_usuario', 'u.estado_cuenta'])
                ->selectRaw('(select count(*) from usuario d join rol_usuario ru on ru.id_usuario = d.id_usuario
                    join rol r on r.id_rol = ru.id_rol
                    where d.id_usuario = ? and d.estado_cuenta = ? and r.nombre_rol = ?) as puede_validar',
                    [$idDocente, EstadoCuenta::Habilitada->value, Rol::NOMBRE_DOCENTE])
                ->selectRaw('(select count(*) from validacion_registro v
                    where v.id_solicitante = u.id_usuario and v.id_validador = ?) as ya_decidio', [$idDocente])
                ->selectRaw("(select count(*) from validacion_registro v
                    where v.id_solicitante = u.id_usuario and v.decision = 'aprobado') as aprobaciones")
                // Bloquea la solicitud: la segunda aprobación cuenta las anteriores.
                ->lockForUpdate()
                ->first();

            // Sin solicitud igual se revisa primero el permiso del docente.
            $puedeValidar = $solicitud !== null
                ? (int) $solicitud->puede_validar > 0
                : $this->esDocenteHabilitado($idDocente);

            if (! $puedeValidar) {
                throw new InvalidArgumentException(self::MENSAJE_SIN_PERMISO);
            }

            if ($solicitud === null || $solicitud->estado_cuenta !== EstadoCuenta::Pendiente->value) {
                throw new InvalidArgumentException(self::MENSAJE_RESUELTA);
            }

            if ((int) $solicitud->ya_decidio > 0) {
                throw new InvalidArgumentException(self::MENSAJE_YA_APROBO);
            }

            return $decision($solicitud);
        });

        ListarSolicitudesRegistroService::olvidarPendientes();

        return $resultado;
    }

    /** Docente con rol docente y cuenta habilitada. */
    private function esDocenteHabilitado(int $idDocente): bool
    {
        return Usuario::query()
            ->whereKey($idDocente)
            ->where('estado_cuenta', EstadoCuenta::Habilitada->value)
            ->whereHas('roles', fn ($q) => $q->where('nombre_rol', Rol::NOMBRE_DOCENTE))
            ->exists();
    }

    private function guardarDecision(int $idSolicitante, int $idDocente, string $decision, ?string $motivo = null): void
    {
        DB::table('validacion_registro')->insert([
            'id_solicitante' => $idSolicitante,
            'id_validador' => $idDocente,
            'decision' => $decision,
            'motivo' => $motivo,
            'fecha' => now(),
        ]);
    }

    private function nombre(object $usuario): string
    {
        return trim(($usuario->nombre ?: $usuario->nombre_usuario).' '.$usuario->apellido);
    }
}
