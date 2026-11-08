<?php

/**
 * @file    EstadoCuenta.php
 *
 * @author  Valery D. Ortuno P <valerydariana98@gmail.com>
 *
 * @created 2026-11-08
 *
 * @description
 * Estado de la cuenta de un usuario (`usuario.estado_cuenta`). Una cuenta
 * creada desde el registro nace pendiente y no puede ingresar hasta que dos
 * docentes la aprueben (queda habilitada); con una desaprobación queda
 * rechazada. Las cuentas que crea el sistema (docentes iniciales, auxiliares)
 * nacen habilitadas.
 *
 * @changelog
 * - 2026-11-08  [Valery D. Ortuno P]  feat: creación inicial (HU 26).
 *
 * @see  App\Services\Docente\ValidarRegistroDocenteService
 * @see  database/migrations/2026_11_08_000001_validacion_registro_docente.php
 */

namespace App\Enums;

enum EstadoCuenta: string
{
    case Pendiente = 'pendiente';
    case Habilitada = 'habilitada';
    case Rechazada = 'rechazada';

    /**
     * Mensaje del login para una cuenta que no puede ingresar, o null si puede.
     *
     * @param  self|string|null  $estado  Estado de la cuenta (null: cuenta anterior a la columna).
     */
    public static function mensajeAcceso(self|string|null $estado): ?string
    {
        $estado = $estado instanceof self ? $estado : self::tryFrom((string) $estado);

        return match ($estado) {
            self::Pendiente => 'Tu cuenta aun esta en proceso de validacion. Podras ingresar cuando dos docentes la aprueben.',
            self::Rechazada => 'Tu solicitud de registro fue rechazada.',
            default => null,
        };
    }
}
